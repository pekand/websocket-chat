<?php

set_time_limit(0);

spl_autoload_register(function ($class_name) {
    require_once dirname(__FILE__).DIRECTORY_SEPARATOR.str_replace("\\", "/", $class_name) . '.php';
});

use WebSocketServer\WebSocketServer;
use Logic\Log;
use Logic\ChatsStorage;
use Logic\ServerLogic;

define("ROOT", dirname(__FILE__));
define("STORAGE", ROOT.DIRECTORY_SEPARATOR.'storage');

Log::setAllowdSeverity([
    'INFO', 
    'ERROR', 
    //'DEBUG'
]);

Log::write("WEBSOCKET SERVER START");

ServerLogic::init();

$server = new WebSocketServer([
    'port' => 8080
]);

$server->afterServerError(function($code, $message) {
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
     $server->sendMessage($clientUid, json_encode(['action'=>'ping']));
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
    $server->sendMessage($clientUid, json_encode(['action'=>'pong']));      
});

$server->addAction('getUid', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Request Uid");
    $server->sendMessage($clientUid, json_encode(['action'=>'uid', 'uid'=>$clientUid]));    
});

/* ACCESS */

$server->addAction('shutdown', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Request server shutdown");
    $server->shutdown();
});

$server->addAction('close', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client call close action on self");
    
    $chatStorage = ServerLogic::getChatStorage();
    
    if(ServerLogic::isOperator($clientUid)){
        
        $chatsWithoutOperator = $chatStorage->removeOperatorFromAllChats($clientUid);
        
        if(count($chatsWithoutOperator) == 0) {
            foreach ($chatsWithoutOperator as $chatUid) { 
                foreach ($chatStorage->getChatClients() as $client) { 
                    Log::write("({$clientUid}) All operators disconected from chat {$chatUid}");
                    $server->sendMessage($client , json_encode(['action'=>'operatorsDisconected', 'chatUid'=>$chatUid])); 
                }
            }
        }
    
        ServerLogic::removeOperator($clientUid);
        ServerLogic::removeClient($clientUid);
        
        if(count(ServerLogic::getOperators()) == 0 && count(ServerLogic::getClients()) > 0){
            foreach (ServerLogic::getClients() as $uid => $value) { 
                Log::write("({$clientUid}) All operators disconected, notification to client {$uid}");
                $server->sendMessage($uid , json_encode(['action'=>'operatorsDisconected'])); 
            }
        }
    } else if(ServerLogic::isClient($clientUid)) {
        
        $chatsWithoutClients = $chatStorage->removeClientFromAllChats($clientUid);
        if(count($chatsWithoutClients) > 0) {
            foreach ($chatsWithoutClients as $chatUid) {
                foreach (ServerLogic::getOperators() as $operatorUid => $operator) { 
                    Log::write("({$clientUid}) All clients disconected from chat {$chatUid}");
                    $server->sendMessage($operatorUid , json_encode(['action'=>'allClientsDisconectedFromChat', 'chatUid'=>$chatUid])); 
                    
                    $chatStorage->closeChat($chatUid);
                    
                    $server->sendMessage($operatorUid , json_encode(['action'=>'chatClosed', 'chatUid'=>$chatUid])); 
                }
            }
        }
    
        ServerLogic::removeClient($clientUid);
        
        if(count(ServerLogic::getOperators()) > 0){
            foreach (ServerLogic::getOperators() as $uid => $value) { 
                Log::write("({$clientUid}) Client disconected notification to operator {$uid}");
                $server->sendMessage($uid , json_encode(['action'=>'clientDisconected', 'clientUid'=> $clientUid])); 
            }
        }
    }
    
    $server->closeClient($clientUid);
});

$server->addAction('login', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client attempt login as operator");
    
    
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
        
        $server->sendMessage($clientUid, json_encode([
            'action'=>'loginSuccess',
            'token'=> ServerLogic::getToken()
        ])); 
          
        if($noActiveOperator){
            foreach (ServerLogic::getClients() as $uid => $value) { 
                Log::write("({$clientUid}) Operator login notification {$uid}");
                $server->sendMessage($uid , json_encode([
                    'action'=>'operatorConnected', 
                    'operatorUid'=> $clientUid                    
                ])); 
            }    
        }    
    } else {
        Log::write("({$clientUid}) Operator rejected");
        $server->sendMessage($clientUid, json_encode(['action'=>'loginFailed']));   
    }
});

$server->addAction('loginWithToken', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client attempt login with token as operator");
    
    if(ServerLogic::isValidToken($data['token'])) { 
        Log::write("({$clientUid}) Operator accepted by token");
            
        $noActiveOperator = true;
        if(count(ServerLogic::getOperators()) > 0){
            $noActiveOperator = false;
        }
        
        ServerLogic::addOperator($clientUid);  
        
        $chatStorage = ServerLogic::getChatStorage();
        $chatStorage->addOperatorToAllChats($clientUid);
        
        $server->sendMessage($clientUid, json_encode([
            'action'=>'loginWithTokenSuccess'            
        ])); 
          
        if($noActiveOperator){
            foreach (ServerLogic::getClients() as $uid => $value) { 
                Log::write("({$clientUid}) Operator login notification {$uid}");
                $server->sendMessage($uid , json_encode([
                    'action'=>'operatorConnected', 
                    'operatorUid'=> $clientUid                    
                ])); 
            }    
        }    
    } else {
        Log::write("({$clientUid}) Operator rejected token");
        $server->sendMessage($clientUid, json_encode([
            'action'=>'loginWithTokenFailed'
        ]));   
    }
});

$server->addAction('logout', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client attempt logout as operator");
    if(ServerLogic::isOperator($clientUid)) { 
        Log::write("({$clientUid}) Operator logout operator");
        ServerLogic::removeOperator($clientUid);
        
        $server->sendMessage($clientUid, json_encode(['action'=>'logoutSuccess'])); 
        
        foreach (ServerLogic::getOperators() as $operatorUid => $value) { 
            Log::write("({$clientUid}) Operator logout {$operatorUid}");
            $server->sendMessage($operatorUid , json_encode(['action'=>'operatorLogout', 'operator'=> $clientUid])); 
        } 

    } else {
        $server->sendMessage($clientUid, json_encode(['action'=>'accessDenied', 'forbidden'=>'logout']));   
    }   
});

$server->addAction('isOperatorLogged', function($server, $clientUid, $data){   
    Log::write("({$clientUid}) Check if operator is logged");
    if(count(ServerLogic::getOperators()) == 0) {
        $server->sendMessage($clientUid , json_encode(['action'=>'operatorConnected'])); 
    } else {
        $server->sendMessage($clientUid , json_encode(['action'=>'operatorsDisconected']));
    }
});

/* MESSAGES */

$server->addAction('sendMessage', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client send message to: ".$data['to']." message".$data['message']);
    $toUid = $data['to'];
    if($server->isClient($toUid)){        
        Log::write("({$clientUid}) Message to {$toUid} : {$data['message']}");
        $server->sendMessage($toUid, json_encode(['action'=>'message', 'from'=>$clientUid, 'message'=>$data['message'] ]));   
    }
});

$server->addAction('sendMessageToOperator', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client send message to all active operators: message".$data['message']);
    foreach (ServerLogic::getOperators() as $operatorUid => $value) { 
        Log::write("({$clientUid}) Client Send Message To Operator {$operatorUid}: {$data['message']}");
        $server->sendMessage($operatorUid , json_encode(['action'=>'messageFromClient', 'from'=> $clientUid, 'message'=>$data['message']])); 
    }  
});

$server->addAction('getClients', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client request list of clients");
    if(ServerLogic::isOperator($clientUid)) {
        $clients = [];
        foreach (ServerLogic::getClients() as $uid => $value) { 
            $clients[] = $uid;
        }
        $server->sendMessage($clientUid, json_encode(['action'=>'clients', 'clients'=>$clients]));   
    }  else {
        $server->sendMessage($clientUid, json_encode(['action'=>'accessDenied', 'forbidden'=>'getClients']));   
    }
});


$server->addAction('broadcast', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client broadcast message");    
    if(ServerLogic::isOperator($clientUid) && isset($data['message'])) { 
        Log::write("({$clientUid}) Operator broadcast");
        foreach (ServerLogic::getClients() as $uid => $value) {                
            Log::write("({$clientUid}) Addmin broadcast to {$uid}: {$data['message']}");
            $server->sendMessage($uid, json_encode(['action'=>'operatorBroadcastMessage', 'operator'=>$clientUid, 'message'=>$data['message']])); 
        }
    } else {
        $server->sendMessage($clientUid, json_encode(['action'=>'accessDenied', 'forbidden'=>'broadcast']));   
    }   
});

/* CHATS */

$server->addAction('openChat', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client open chat");   
    
    $chatStorage = ServerLogic::getChatStorage();
        
    $isChatAllreadyOpen = false;
    if($data['chatUid'] != '' && $chatStorage->isChatOpen($data['chatUid'])){
        $isChatAllreadyOpen = true;
    }
    
    $chatUid = $chatStorage->openChat($data['chatUid']);
 
    if(!$isChatAllreadyOpen) {
        foreach (ServerLogic::getOperators() as $operatorUid => $value) { 
            Log::write("({$clientUid}) Client open chat {$chatUid}");
            $server->sendMessage($operatorUid , json_encode([
                'action'=>'chatOpen', 
                'chatUid'=> $chatUid,
                'chatHistory' => $chatStorage->getChatHistory($chatUid)
            ])); 
        }  
    }

    if(!$chatStorage->isClientInChat($data['chatUid'], $clientUid)){
        $chatStorage->addClientToChat($data['chatUid'], $clientUid);
        
        $server->sendMessage($clientUid, json_encode(
                [
                    'action'=>'chatUid', 
                    'chatUid'=> $chatUid,
                    'operatorStatus' => count(ServerLogic::getOperators()) > 0 ? 'online' :'offline',
                    'chatHistory' => $chatStorage->getChatHistory($chatUid)
                ]
            )
        );
    }
});

$server->addAction('getAllOpenChats', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client request list of opened chats");   
    if(ServerLogic::isOperator($clientUid)) {
        $chatStorage = ServerLogic::getChatStorage();
        $chatStorage->addOperatorToAllChats($clientUid);
        $server->sendMessage($clientUid, json_encode(['action'=>'allOpenChats', 'chats'=>$chatStorage->getChats()]));               
    }  else {
        $server->sendMessage($clientUid, json_encode(['action'=>'accessDenied', 'forbidden'=>'getChats']));   
    }
});


$server->addAction('getChatHistory', function($server, $clientUid, $data) {
    Log::write("({$clientUid}) Client request chat history");   
    $chatStorage = ServerLogic::getChatStorage();
    $chatHsitory = $chatStorage->getChatHistory($data['chatUid']);
    
    $server->sendMessage($clientUid, json_encode(
            [
                'action'=>'chatHistory', 
                'chatUid'=> $data['chatUid'],
                'chatHistory' => $chatHsitory,
            ]
        )
    );  
});

$server->addAction('addClientMessageToChat', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Client try add mesage to chatUid: ".$data['chatUid']);   
    $chatStorage = ServerLogic::getChatStorage();        
    $chatStorage->addClientMessage($data['chatUid'], $clientUid, $data['message']);
    $chatStorage->saveChat($data['chatUid']);     
    
    $chat = $chatStorage->getChat($data['chatUid']);
   
    foreach ($chat['participants']['clients'] as $participantUid) {
        if($participantUid == $clientUid) {
            continue;
        }

        $server->sendMessage($participantUid, json_encode([
            'action'=>'clientAddMessageToChat', 
            'chatUid'=>$data['chatUid'], 
            'clientUid'=> $clientUid,
            'message'=>$data['message'],
        ]));   
    }   
    
    foreach (ServerLogic::getOperators() as $operatorUid => $operator) {
        if($operatorUid == $clientUid) {
            continue;
        }

        $server->sendMessage($operatorUid, json_encode([
            'action'=>'clientAddMessageToChat', 
            'chatUid'=>$data['chatUid'], 
            'clientUid'=> $clientUid,
            'message'=>$data['message'],
        ]));   
    }   
});

$server->addAction('addOperatorMessageToChat', function($server, $clientUid, $data){
    Log::write("({$clientUid}) Operator try add mesage to chatUid:".$data['chatUid']);   
    $chatStorage = ServerLogic::getChatStorage();  
    $chatStorage->addOperatorMessage($data['chatUid'], $clientUid, $data['message']);
    $chatStorage->saveChat($data['chatUid']); 
    
    $chat = $chatStorage->getChat($data['chatUid']);
    
    foreach ($chat['participants']['clients'] as $participantUid) {
        if($participantUid == $clientUid) {
            continue;
        }
        
        $server->sendMessage($participantUid, json_encode([
            'action'=>'operatorAddMessageToChat', 
            'chatUid'=>$data['chatUid'], 
            'operatorUid'=> $clientUid,
            'message'=>$data['message'],
        ]));   
    }
    
    foreach (ServerLogic::getOperators() as $operatorUid => $operator) {
        if($operatorUid == $clientUid) {
            continue;
        }
        
        $server->sendMessage($operatorUid, json_encode([
            'action'=>'operatorAddMessageToChat', 
            'chatUid'=>$data['chatUid'], 
            'operatorUid'=> $clientUid,
            'message'=>$data['message'],
        ]));   
    }
});

$server->listen();

Log::write("WEBSOCKET SERVER FINISHED");
