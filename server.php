<?php

set_time_limit(0);

spl_autoload_register(function ($class_name) {
    require_once dirname(__FILE__).DIRECTORY_SEPARATOR.str_replace("\\", "/", $class_name) . '.php';
});

use WebSocketServer\WebSocketServer;
use Logic\Log;
use Logic\ChatsStorage;
use Logic\ServerLogic;
use Logic\Validator;

define("ROOT", dirname(__FILE__));
define("STORAGE", ROOT.DIRECTORY_SEPARATOR.'storage');

ServerLogic::init();

Log::setAllowdSeverity([
    'INFO', 
    'ERROR', 
    //'DEBUG'
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

$server->clientConnected(function($server, $clientUid) {
    Log::write("({$clientUid}) CLIENT CONNECTED");
    ServerLogic::addClient($clientUid);      
    return true;
});

$server->clientDisconnected(function($server, $clientUid, $reason) {
    Log::write("({$clientUid}) CLIENT DISCONNECTED: {$reason}");
    $server->callAction('callAction', $clientUid, []);
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

$server->addAction('ping', function($server, $clientUid, $data){
    
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
    
    $chatStorage = ServerLogic::getChatStorage();
    
    if(ServerLogic::isOperator($clientUid)){
        
        $chatsWithoutOperator = $chatStorage->removeOperatorFromAllChats($clientUid);
        
        if(count($chatsWithoutOperator) == 0) {
            foreach ($chatsWithoutOperator as $chatUid) { 
                foreach ($chatStorage->getChatClients() as $client) { 
                    Log::write("({$clientUid}) All operators disconected from chat {$chatUid}");
                    $server->send($client , ['action'=>'operatorsDisconected', 'chatUid'=>$chatUid]); 
                }
            }
        }
    
        ServerLogic::removeOperator($clientUid);
        ServerLogic::removeClient($clientUid);
        
        if(count(ServerLogic::getOperators()) == 0 && count(ServerLogic::getClients()) > 0){
            foreach (ServerLogic::getClients() as $uid => $value) { 
                Log::write("({$clientUid}) All operators disconected, notification to client {$uid}");
                $server->send($uid , ['action'=>'operatorsDisconected']); 
            }
        }
    } else if(ServerLogic::isClient($clientUid)) {
        
        $chatsWithoutClients = $chatStorage->removeClientFromAllChats($clientUid);
        if(count($chatsWithoutClients) > 0) {
            foreach ($chatsWithoutClients as $chatUid) {
                foreach (ServerLogic::getOperators() as $operatorUid => $operator) { 
                    Log::write("({$clientUid}) All clients disconected from chat {$chatUid}");
                    $server->send($operatorUid , ['action'=>'allClientsDisconectedFromChat', 'chatUid'=>$chatUid]); 
                    
                    $chatStorage->closeChat($chatUid);
                    
                    $server->send($operatorUid , ['action'=>'chatClosed', 'chatUid'=>$chatUid]); 
                }
            }
        }
    
        ServerLogic::removeClient($clientUid);
        
        if(count(ServerLogic::getOperators()) > 0){
            foreach (ServerLogic::getOperators() as $uid => $value) { 
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
    
    $userStorage = ServerLogic::getUsersStorage();

    if($userStorage->isValidUser($data['username'], $data['password'])) { 
        Log::write("({$clientUid}) Operator accepted");
            
        $noActiveOperator = true;
        if(count(ServerLogic::getOperators()) > 0){
            $noActiveOperator = false;
        }
        
        ServerLogic::addOperator($clientUid);  
        
        $chatStorage = ServerLogic::getChatStorage();
        $chatStorage->addOperatorToAllChats($clientUid);
        
        $server->send($clientUid, [
            'action'=>'loginSuccess',
            'token'=> ServerLogic::getToken()
        ]); 
          
        if($noActiveOperator){
            foreach (ServerLogic::getClients() as $uid => $value) { 
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
    
    if(ServerLogic::isValidToken($data['token'])) { 
        Log::write("({$clientUid}) Operator accepted by token");
            
        $noActiveOperator = true;
        if(count(ServerLogic::getOperators()) > 0){
            $noActiveOperator = false;
        }
        
        ServerLogic::addOperator($clientUid);  
        
        $chatStorage = ServerLogic::getChatStorage();
        $chatStorage->addOperatorToAllChats($clientUid);
        
        $server->send($clientUid, [
            'action'=>'loginWithTokenSuccess'            
        ]); 
          
        if($noActiveOperator){
            foreach (ServerLogic::getClients() as $uid => $value) { 
                Log::write("({$clientUid}) Operator login notification {$uid}");
                $server->send($uid , [
                    'action'=>'operatorConnected', 
                    'operatorUid'=> $clientUid                    
                ]); 
            }    
        }    
    } else {
        Log::write("({$clientUid}) Operator rejected token");
        $server->send($clientUid, [
            'action'=>'loginWithTokenFailed'
        ]);   
    }
});

$server->addAction('logout', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client attempt logout as operator");
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    if(ServerLogic::isOperator($clientUid)) { 
        Log::write("({$clientUid}) Operator logout operator");
        ServerLogic::removeOperator($clientUid);
        
        $server->send($clientUid, ['action'=>'logoutSuccess']); 
        
        foreach (ServerLogic::getOperators() as $operatorUid => $value) { 
            Log::write("({$clientUid}) Operator logout {$operatorUid}");
            $server->send($operatorUid , ['action'=>'operatorLogout', 'operator'=> $clientUid]); 
        } 

    } else {
        $server->send($clientUid, ['action'=>'accessDenied', 'forbidden'=>'logout']);   
    }   
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
    
    if(count(ServerLogic::getOperators()) == 0) {
        $server->send($clientUid , ['action'=>'operatorConnected']); 
    } else {
        $server->send($clientUid , ['action'=>'operatorsDisconected']);
    }
});

/* MESSAGES */

$server->addAction('sendMessage', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client send message to: ".$data['to']." message".$data['message']);
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
        'to' => ['type'=>'string', 'length' => ['min'=>1,'max'=>100],],
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
    
    foreach (ServerLogic::getOperators() as $operatorUid => $value) { 
        Log::write("({$clientUid}) Client Send Message To Operator {$operatorUid}: {$data['message']}");
        $server->send($operatorUid , ['action'=>'messageFromClient', 'from'=> $clientUid, 'message'=>$data['message']]); 
    }  
});

$server->addAction('getClients', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client request list of clients");
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    if(ServerLogic::isOperator($clientUid)) {
        $clients = [];
        foreach (ServerLogic::getClients() as $uid => $value) { 
            $clients[] = $uid;
        }
        $server->send($clientUid, ['action'=>'clients', 'clients'=>$clients]);   
    }  else {
        $server->send($clientUid, ['action'=>'accessDenied', 'forbidden'=>'getClients']);   
    }
});


$server->addAction('broadcast', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client broadcast message"); 
       
    $validator = new Validator();
    $validator->rules([
        'action' => [],
        'message' => ['type'=>'string', 'length' => ['min'=>1,'max'=>10000],],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    if(ServerLogic::isOperator($clientUid) && isset($data['message'])) { 
        Log::write("({$clientUid}) Operator broadcast");
        foreach (ServerLogic::getClients() as $uid => $value) {                
            Log::write("({$clientUid}) Addmin broadcast to {$uid}: {$data['message']}");
            $server->send($uid, ['action'=>'operatorBroadcastMessage', 'operator'=>$clientUid, 'message'=>$data['message']]); 
        }
    } else {
        $server->send($clientUid, ['action'=>'accessDenied', 'forbidden'=>'broadcast']);   
    }   
});

/* CHATS */

$server->addAction('openChat', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client open chat");   
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],
        'chatUid' => ['null'=>true, 'type'=>'string', 'length' => ['min'=>0,'max'=>100],],
    ]);
    
    
    if(!$validator->isValid($data)){
        var_dump($validator->getErrors());die();
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);
         return;  
    }
    
    $chatStorage = ServerLogic::getChatStorage();
        
    $isChatAllreadyOpen = false;
    if($data['chatUid'] != '' && $chatStorage->isChatOpen($data['chatUid'])){
        $isChatAllreadyOpen = true;
    }
    
    $chatUid = $chatStorage->openChat($data['chatUid']);
 
    if(!$isChatAllreadyOpen) {
        foreach (ServerLogic::getOperators() as $operatorUid => $value) { 
            Log::write("({$clientUid}) Client open chat {$chatUid}");
            $server->send($operatorUid , [
                'action'=>'chatOpen', 
                'chatUid'=> $chatUid,
                'chatHistory' => $chatStorage->getChatHistory($chatUid)
            ]); 
        }  
    }

    if(!$chatStorage->isClientInChat($data['chatUid'], $clientUid)){
        $chatStorage->addClientToChat($data['chatUid'], $clientUid);
        
        $server->send($clientUid, [
            'action'=>'chatUid', 
            'chatUid'=> $chatUid,
            'operatorStatus' => count(ServerLogic::getOperators()) > 0 ? 'online' :'offline',
            'chatHistory' => $chatStorage->getChatHistory($chatUid)
        ]);
    }
});

$server->addAction('getAllOpenChats', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client request list of opened chats");  
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],        
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
     
    if(ServerLogic::isOperator($clientUid)) {
        $chatStorage = ServerLogic::getChatStorage();
        $chatStorage->addOperatorToAllChats($clientUid);
        $server->send($clientUid, ['action'=>'allOpenChats', 'chats'=>$chatStorage->getChats()]);               
    }  else {
        $server->send($clientUid, ['action'=>'accessDenied', 'forbidden'=>'getChats']);   
    }
});


$server->addAction('getChatHistory', function($server, $clientUid, $data) {
    Log::write("({$clientUid}) Client request chat history");   
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],      
        'chatUid' => ['type'=>'string', 'length' => ['min'=>1,'max'=>100],],  
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $chatStorage = ServerLogic::getChatStorage();
    $chatHsitory = $chatStorage->getChatHistory($data['chatUid']);
    
    $server->send($clientUid, [
        'action'=>'chatHistory', 
        'chatUid'=> $data['chatUid'],
        'chatHistory' => $chatHsitory,
    ]);  
});

$server->addAction('addClientMessageToChat', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client try add mesage to chatUid: ".$data['chatUid']);   
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],      
        'chatUid' => ['type'=>'string', 'length' => ['min'=>1,'max'=>100],],  
        'message' => ['type'=>'string', 'length' => ['min'=>1,'max'=>10000],],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $chatStorage = ServerLogic::getChatStorage();        
    $chatStorage->addClientMessage($data['chatUid'], $clientUid, $data['message']);
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
    
    foreach (ServerLogic::getOperators() as $operatorUid => $operator) {
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
    
    $validator = new Validator();
    $validator->rules([
        'action' => [],      
        'chatUid' => ['type'=>'string', 'length' => ['min'=>1,'max'=>100],],  
        'message' => ['type'=>'string', 'length' => ['min'=>1,'max'=>10000],],
    ]);
    
    if(!$validator->isValid($data)){
         $server->send($clientUid, ['action'=>'invalidRequest', 'errors'=>$validator->getErrors()]);        
         return;  
    }
    
    $chatStorage = ServerLogic::getChatStorage();  
    $chatStorage->addOperatorMessage($data['chatUid'], $clientUid, $data['message']);
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
    
    foreach (ServerLogic::getOperators() as $operatorUid => $operator) {
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

$server->addWorker(['delay'=>10.0, 'repeat'=>60.0], function($server){
    Log::write("Worker informations: ".json_encode(ServerLogic::getInfo()));   
});


$server->listen();

Log::write("WEBSOCKET SERVER FINISHED");
