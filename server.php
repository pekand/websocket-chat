<?php

set_time_limit(0);

define("ROOT_PATH", dirname(__FILE__));
require_once(ROOT_PATH.'/config.php');
require_once(ROOT_PATH.'/vendor/autoload.php');

use pekand\WebSocketServer\WebSocketServer;
use pekand\Log\Log;
use pekand\Chat\Services;
use pekand\Chat\Validator;

define("ROOT", dirname(__FILE__));
define("STORAGE", ROOT.DIRECTORY_SEPARATOR.'storage');

Services::init();

Log::setAllowedSeverity([
    'INFO', 
    'ERROR', 
    \Config::DEBUG_MODE ? 'DEBUG' : 'PRODUCTION',
]);

Log::write("WEBSOCKET SERVER START");

$server = new WebSocketServer([
    'port' => 8080
]);

$server->afterServerError(function($server, $code, $message) {
     Log::write("SERVER ERROR [$code]: $message", "ERROR");
});

$server->afterClientError(function($server, $clientUid, $code, $message) {
    Log::write("({$clientUid}) [$code]: $message", "ERROR");
});

$server->afterShutdown(function($server) {
    Log::write("SERVER SHUTDOWN");
});

$server->afterDataRecived(function($server, $clientUid, $data, $frames) {
    $dataDump = base64_encode($data);
    $frameDump = print_r($frames, true);
    Log::write("DATA: {$dataDump} ", 'DEBUG');
    Log::write("FRAMES: {$frameDump} ", 'DEBUG');
});

$server->clientConnected(function($server, $clientUid) {
    Log::write("({$clientUid}) CLIENT CONNECTED");
    $userStorage = Services::getUsersStorage();
    $userStorage->addClient($clientUid);
    return true;
});

$server->clientDisconnected(function($server, $clientUid, $reason) {
    Log::write("({$clientUid}) CLIENT DISCONNECTED: {$reason}");
    $server->callAction('close', $clientUid, []);
});

$server->buildPing(function($server, $clientUid) {     
     $server->send($clientUid, ['action'=>'ping']);
});

$server->beforeSendMessage(function($server, $clientUid, $message) {
    $data = json_decode($message, true);
    
    $severity = 'INFO';
    if (isset($data['action']) && ($data['action'] == 'ping' || $data['action'] == 'pong')) {
        $severity = 'DEBUG';
    }
        
    Log::write("MESSAGE TO CLIENT ({$clientUid}): {$message}", $severity);
});

// display header
$server->addListener(function($server, $clientUid, $request) {   
    $requestFromClient = $request;
    if(strlen($requestFromClient)>1000) {
        $requestFromClient = substr($request, 0, 100)."...";
    }     
         
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    $severity = 'INFO';
    if ($data['action'] == 'ping' || $data['action'] == 'pong') {
        $severity = 'DEBUG';
    }
    
    Log::write("({$clientUid}) MESSAGE FROM CLIENT (LEN:".strlen($request)."): ".$requestFromClient, $severity);
});

/* TOOLS */

$server->addAction('ping', function($server, $clientUid, $data) {
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);
         return;  
    }
    
    $server->send($clientUid, ['action'=>'pong']);      
});

$server->addAction('getUid', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Request Uid");   
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
     
    $server->send($clientUid, ['action'=>'uid', 'uid'=>$clientUid]);    
});

/* ACCESS */

$server->addAction('shutdown', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Request server shutdown");

    $userStorage = Services::getUsersStorage();

    if(!$userStorage->isOperator($clientUid)) {
        $server->send($clientUid, ['action'=>'accessDenied', 'errors'=>['clientUid' => "user is not operator"]]);
        return;
    }

    $validator = new Validator();
    $validator->rules([
        'action' => [],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $server->shutdown();
});

$server->addAction('close', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client call close action on self");
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $chatStorage = Services::getChatStorage();
    $userStorage = Services::getUsersStorage();
    
    if($userStorage->isOperator($clientUid)){
        
        $chatsWithoutOperator = $chatStorage->removeOperatorFromAllChats($clientUid);
        
        if(count($chatsWithoutOperator) == 0) {
            foreach ($chatsWithoutOperator as $chatUid) { 
                foreach ($chatStorage->getChatClients() as $client) { 
                    Log::write("({$clientUid}) All operators disconected from chat {$chatUid}");
                    $server->send($client , ['action'=>'operatorsDisconected', 'chatUid'=>$chatUid]); 
                }
            }
        }
    
        $userStorage->removeOperator($clientUid);
        $userStorage->removeClient($clientUid);
        
        if(count($userStorage->getOperators()) == 0 && count($userStorage->getClients()) > 0){
            foreach ($userStorage->getClients() as $uid => $value) { 
                Log::write("({$clientUid}) All operators disconected, notification to client {$uid}");
                $server->send($uid , ['action'=>'operatorsDisconected']); 
            }
        }
    } else if($userStorage->isClient($clientUid)) {
        
        $chatsWithoutClients = $chatStorage->removeClientFromAllChats($clientUid);
        if(count($chatsWithoutClients) > 0) {
            foreach ($chatsWithoutClients as $chatUid) {
                foreach ($userStorage->getOperators() as $operatorUid => $operator) { 
                    Log::write("({$clientUid}) All clients disconected from chat {$chatUid}");
                    $server->send($operatorUid , ['action'=>'allClientsDisconectedFromChat', 'chatUid'=>$chatUid]); 
                    
                    $chatStorage->closeChat($chatUid);
                    
                    $server->send($operatorUid , ['action'=>'chatClosed', 'chatUid'=>$chatUid]); 
                }
            }
        }
    
        $userStorage->removeClient($clientUid);
        
        if(count($userStorage->getOperators()) > 0){
            foreach ($userStorage->getOperators() as $uid => $value) { 
                Log::write("({$clientUid}) Client disconected notification to operator {$uid}");
                $server->send($uid , ['action'=>'clientDisconected', 'clientUid'=> $clientUid]); 
            }
        }
    }
    
    $server->closeClient($clientUid);
});

$server->addAction('login', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client attempt login as operator");
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
        'username' => ['type'=>'string', 'length' => ['min'=>1,'max'=>100],],
        'password' => ['type'=>'string', 'length' => ['min'=>1,'max'=>100],],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $userStorage = Services::getUsersStorage();

    if($userStorage->isValidOperator($data['username'], $data['password'])) { 
        Log::write("({$clientUid}) Operator accepted");
            
        $noActiveOperator = true;
        if(count($userStorage->getOperators()) > 0){
            $noActiveOperator = false;
        }
        
        $userStorage->addOperator($clientUid);  
        
        $chatStorage = Services::getChatStorage();
        $chatStorage->addOperatorToAllChats($clientUid);
        
        $server->send($clientUid, [
            'action'=>'loginSuccess',
            'token'=> $userStorage->getToken()
        ]); 
          
        if($noActiveOperator){
            foreach ($userStorage->getClients() as $uid => $value) { 
                Log::write("({$clientUid}) Operator login notification {$uid}");
                $server->send($uid , [
                    'action'=>'operatorConnected', 
                    'operatorUid'=> $clientUid                    
                ]); 
            }    
        }    
    } else {
        Log::write("({$clientUid}) Operator rejected");
        $server->send($clientUid, ['action'=>'loginFailed']);   
    }
});

$server->addAction('loginWithToken', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client attempt login with token as operator");
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
        'token' => ['type'=>'string', 'length' => ['min'=>1,'max'=>100],],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $userStorage = Services::getUsersStorage();
    
    if(!$userStorage->isValidToken($data['token'])) {
        Log::write("({$clientUid}) Operator rejected token");
        $server->send($clientUid, [
            'action'=>'loginWithTokenFailed'
        ]);  
        return;
    } 
    Log::write("({$clientUid}) Operator accepted by token");
        
    $noActiveOperator = true;
    if(count($userStorage->getOperators()) > 0){
        $noActiveOperator = false;
    }
    
    $userStorage->addOperator($clientUid);  
    
    $chatStorage = Services::getChatStorage();
    $chatStorage->addOperatorToAllChats($clientUid);
    
    $server->send($clientUid, [
        'action'=>'loginWithTokenSuccess'            
    ]); 
      
    if($noActiveOperator){
        foreach ($userStorage->getClients() as $uid => $value) { 
            Log::write("({$clientUid}) Operator login notification {$uid}");
            $server->send($uid , [
                'action'=>'operatorConnected', 
                'operatorUid'=> $clientUid                    
            ]); 
        }    
    }    
});

$server->addAction('logout', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client attempt logout as operator");

    $userStorage = Services::getUsersStorage();

    if(!$userStorage->isOperator($clientUid)) {
        $server->send($clientUid, ['action'=>'accessDenied', 'errors'=>['clientUid' => "user is not operator"]]);
        return;
    }

    $validator = new Validator();
    $validator->rules([
        'action' => [],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    Log::write("({$clientUid}) Operator logout operator");

    $oerators = $userStorage->getOperators();

    if(count($oerators) > 1) {
        foreach ($userStorage->getOperators() as $operatorUid => $value) {
            Log::write("({$clientUid}) Operator logout {$operatorUid}");
            $server->send($operatorUid, ['action' => 'operatorLogout', 'operator' => $clientUid]);
        }
    }

    if(count($oerators) == 1) {
        foreach ($userStorage->getClients() as $operatorUid => $value) {
            Log::write("({$clientUid}) Operator logout {$operatorUid}");
            $server->send($operatorUid, ['action' => 'operatorLeft', 'operator' => $clientUid]);
        }
    }

    $userStorage->removeOperator($clientUid);
    $server->send($clientUid, ['action'=>'logoutSuccess']);
});

$server->addAction('isOperatorLogged', function($server, $clientUid, $data){   
    Log::write("({$clientUid}) Check if operator is logged");

    $validator = new Validator();
    $validator->rules([
        'action' => [],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $userStorage = Services::getUsersStorage();
    if(count($userStorage->getOperators()) > 0) {
        $server->send($clientUid , ['action'=>'operatorConnected']); 
    } else {
        $server->send($clientUid , ['action'=>'operatorsDisconected']);
    }
});

$server->addAction('getClients', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client request list of clients");

    $userStorage = Services::getUsersStorage();

    if(!$userStorage->isOperator($clientUid)) {
        $server->send($clientUid, ['action'=>'accessDenied', 'errors'=>['clientUid' => "user is not operator"]]);
        return;
    }

    $validator = new Validator();
    $validator->rules([
        'action' => [],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }

    $clients = [];
    foreach ($userStorage->getClients() as $uid => $value) {
        $clients[] = $uid;
    }
    $server->send($clientUid, ['action'=>'clients', 'clients'=>$clients]);

});

/* CHATS */

$server->addAction('openChat', function($server, $clientUid, $data) {
    Log::write("({$clientUid}) Client open chat");   
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
        'chatUid' => ['type'=>'uid', 'allowEmpty'=>true],
    ]);
    
    
    if(!$validator->isValid($data)){
        $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);
        return;  
    }
    
    $chatStorage = Services::getChatStorage();

    $chatUid = $data['chatUid'];

    $userStorage = Services::getUsersStorage();

    if(!$chatStorage->isChatOpen($chatUid)) {
        $chatUid = $chatStorage->openChat($chatUid);

        foreach ($userStorage->getOperators() as $operatorUid => $value) {
            Log::write("({$clientUid}) Client open chat {$chatUid}");
            $server->send($operatorUid , [
                'action'=>'chatOpen',
                'chatUid'=> $chatUid,
                'chatHistory' => $chatStorage->getChatHistory($chatUid)
            ]);
        }
    }

    if(!$chatStorage->isClientInChat($chatUid, $clientUid)){
        $chatStorage->addClientToChat($chatUid, $clientUid);
        
        $server->send($clientUid, [
            'action'=>'chatUid', 
            'chatUid'=> $chatUid,
            'operatorStatus' => count($userStorage->getOperators()) > 0 ? 'online' :'offline',
            'chatHistory' => $chatStorage->getChatHistory($chatUid)
        ]);
    }
});

$server->addAction('getAllOpenChats', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client request list of opened chats");

    $userStorage = Services::getUsersStorage();

    if(!$userStorage->isOperator($clientUid)) {
        $server->send($clientUid, ['action'=>'accessDenied', 'errors'=>['clientUid' => "user is not operator"]]);
        return;
    }

    $validator = new Validator();
    $validator->rules([
        'action' => [],        
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
     
    if($userStorage->isOperator($clientUid)) {
        $chatStorage = Services::getChatStorage();
        $chatStorage->addOperatorToAllChats($clientUid);
        $server->send($clientUid, ['action'=>'allOpenChats', 'chats'=>$chatStorage->getChats()]);               
    }  else {
        $server->send($clientUid, ['action'=>'accessDenied', 'errors'=>['clientUid' => "user is not operator"]]);   
    }
});


$server->addAction('getChatHistory', function($server, $clientUid, $data) {
    Log::write("({$clientUid}) Client request chat history");   
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],      
        'chatUid' => ['type'=>'uid'],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }

    $chatStorage = Services::getChatStorage();

    if(!$chatStorage->isChatOpen($data['chatUid'])) {
        $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>['chatUid' => 'invalid chat uid']]);
        return;
    }

    $chatHsitory = $chatStorage->getChatHistory($data['chatUid']);
    
    $server->send($clientUid, [
        'action'=>'chatHistory', 
        'chatUid'=> $data['chatUid'],
        'chatHistory' => $chatHsitory,
    ]);  
});

/* MESSAGES */

$server->addAction('broadcast', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client broadcast message");

    $userStorage = Services::getUsersStorage();

    if(!$userStorage->isOperator($clientUid)) {
        $server->send($clientUid, ['action'=>'accessDenied', 'errors'=>['clientUid' => "user is not operator"]]);
        return;
    }

    $validator = new Validator();
    $validator->rules([
        'action' => [],
        'message' => ['type'=>'string', 'length' => ['min'=>1,'max'=>10000],],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    $userStorage = Services::getUsersStorage();

    Log::write("({$clientUid}) Operator broadcast");
    foreach ($userStorage->getClients() as $uid => $value) {
        Log::write("({$clientUid}) Addmin broadcast to {$uid}: {$data['message']}");
        $server->send($uid, ['action'=>'operatorBroadcastMessage', 'operator'=>$clientUid, 'message'=>$data['message']]);
    }

});

$server->addAction('sendMessage', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client send message to: ".$data['to']." message".$data['message']);
    
    $userStorage = Services::getUsersStorage();

    if(!$userStorage->isOperator($clientUid)) {
        $server->send($clientUid, ['action'=>'accessDenied', 'errors'=>['clientUid' => "user is not operator"]]);
        return;
    }

    $validator = new Validator();
    $validator->rules([
        'action' => [],
        'to' => ['type'=>'uid',],
        'message' => ['type'=>'string', 'length' => ['min'=>1,'max'=>10000],],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $toUid = $data['to'];

    if($server->isClient($toUid)){        
        Log::write("({$clientUid}) Message to {$toUid} : {$data['message']}");
        $server->send($toUid, ['action'=>'message', 'from'=>$clientUid, 'message'=>$data['message'] ]);   
    }
});

$server->addAction('sendMessageToOperator', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client send message to all active operators: message".$data['message']);
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
        'message' => ['type'=>'string', 'length' => ['min'=>1,'max'=>10000],],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $userStorage = Services::getUsersStorage();
    
    foreach ($userStorage->getOperators() as $operatorUid => $value) { 
        Log::write("({$clientUid}) Client Send Message To Operator {$operatorUid}: {$data['message']}");
        $server->send($operatorUid , ['action'=>'messageFromClient', 'from'=> $clientUid, 'message'=>$data['message']]); 
    }  
});

$server->addAction('addClientMessageToChat', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client try add mesage to chatUid: ".$data['chatUid']);

    $userStorage = Services::getUsersStorage();

    if(!$userStorage->isClient($clientUid)) {
        $server->send($clientUid, ['action'=>'accessDenied', 'errors'=>['clientUid' => "user is not operator"]]);
        return;
    }

    $validator = new Validator();
    $validator->rules([
        'action' => [],      
        'chatUid' => ['type'=>'uid'],
        'message' => ['type'=>'string', 'length' => ['min'=>1,'max'=>10000],],
        'type' => ['type'=>'message_type'],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    $chatStorage = Services::getChatStorage();

    if(!$chatStorage->isChatOpen($data['chatUid'])) {
        $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>['chatUid' =>'invalid chat uid']]);
        return;
    }

    $chatStorage->addClientMessage($data['chatUid'], $clientUid, $data['message'], $data['type']);
    $chatStorage->saveChat($data['chatUid']);     
    
    $chat = $chatStorage->getChat($data['chatUid']);
   
    foreach ($chat['participants']['clients'] as $participantUid) {
        if($participantUid == $clientUid) {
            continue;
        }

        $server->send($participantUid, [
            'action'=>'clientAddMessageToChat', 
            'chatUid'=>$data['chatUid'], 
            'clientUid'=> $clientUid,
            'message'=>$data['message'],
        ]);   
    }   
    
    foreach ($userStorage->getOperators() as $operatorUid => $operator) {
        if($operatorUid == $clientUid) {
            continue;
        }

        $server->send($operatorUid, [
            'action'=>'clientAddMessageToChat', 
            'chatUid'=>$data['chatUid'], 
            'clientUid'=> $clientUid,
            'message'=>$data['message'],
        ]);   
    }   
});

$server->addAction('addOperatorMessageToChat', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Operator try add mesage to chatUid:".$data['chatUid']);

    $userStorage = Services::getUsersStorage();

    if(!$userStorage->isOperator($clientUid)) {
        $server->send($clientUid, ['action'=>'accessDenied', 'errors'=>['clientUid' => "user is not operator"]]);
        return;
    }

    $validator = new Validator();
    $validator->rules([
        'action' => [],      
        'chatUid' => ['type'=>'uid', 'length' => ['min'=>1,'max'=>100],],
        'message' => ['type'=>'string', 'length' => ['min'=>1,'max'=>10000],],
        'type' => ['type'=>'message_type'],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $chatStorage = Services::getChatStorage();

    if(!$chatStorage->isChatOpen($data['chatUid'])) {
        $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>['chatUid' =>'invalid chat uid']]);
        return;
    }

    $chatStorage->addOperatorMessage($data['chatUid'], $clientUid, $data['message'], $data['type']);
    $chatStorage->saveChat($data['chatUid']); 
    
    $chat = $chatStorage->getChat($data['chatUid']);
    
    foreach ($chat['participants']['clients'] as $participantUid) {
        if($participantUid == $clientUid) {
            continue;
        }
        
        $server->send($participantUid, [
            'action'=>'operatorAddMessageToChat', 
            'chatUid'=>$data['chatUid'], 
            'operatorUid'=> $clientUid,
            'message'=>$data['message'],
        ]);   
    }

    
    foreach ($userStorage->getOperators() as $operatorUid => $operator) {
        if($operatorUid == $clientUid) {
            continue;
        }
        
        $server->send($operatorUid, [
            'action'=>'operatorAddMessageToChat', 
            'chatUid'=>$data['chatUid'], 
            'operatorUid'=> $clientUid,
            'message'=>$data['message'],
        ]);   
    }
});

$server->addWorker(['delay'=>10.0, 'repeat'=>\Config::USAGE_INFO_INTERVAL], function($server){
    $userStorage = Services::getUsersStorage();    
    Log::write("Worker informations: ".json_encode($userStorage->getInfo()));   
});

$server->listen();

Log::write("WEBSOCKET SERVER FINISHED");
