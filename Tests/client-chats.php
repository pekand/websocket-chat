<?php

use WebSocketServer\WebSocketClient;
use WebSocketServer\WebSocketPool;

$client1 = new WebSocketClient();
$client2 = new WebSocketClient();
$operator1 = new WebSocketClient();
$operator2 = new WebSocketClient();

$state = [];

/* CLIENT1 */

$client1->afterConnect(function ($client) {
    echo "C1 After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$client1->addAction('ping', function ($client, $data) {   
    $client->sendMessage(json_encode(['action'=>'pong']));          
});

$client1->addAction('uid', function ($client, $data) {   
    global $state;
    echo "C1 Server return clientUid: ".$data['uid']."\n"; 
    $state['client1']['uid'] = $data['uid'];    
});

$client1->addAction('chatUid', function ($client, $data) {   
    global $state;
    echo "C1 Server return chatUid: ".$data['chatUid']."\n";
    $state['client1']['chatUid'] = $data['chatUid'];
});

$client1->addAction('operatorAddMessageToChat', function ($client, $data) {   
    echo "C1 Operator (".$data['operatorUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";
});

$client1->addAction('clientAddMessageToChat', function ($client, $data) {   
    echo "C1 Client (".$data['clientUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";
});

$client1->addAction('operatorsDisconected', function ($client, $data) {   
    echo "C1 No operator left\n";
});

$client1->addAction('operatorConnected', function ($client, $data) {   
    echo "C1 New operator is available \n";
});

/* CLIENT2 */

$client2->afterConnect(function ($client) {
    echo "C2 After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$client2->addAction('ping', function ($client, $data) {   
    $client->sendMessage(json_encode(['action'=>'pong']));          
});

$client2->addAction('uid', function ($client, $data) {   
    global $state;
    echo "C2 Server return clientUid: ".$data['uid']."\n"; 
    $state['client1']['uid'] = $data['uid'];    
});

$client2->addAction('chatUid', function ($client, $data) {   
    global $state;
    echo "C2 Server return chatUid: ".$data['chatUid']."\n";
    $state['client1']['chatUid'] = $data['chatUid'];
});

$client2->addAction('operatorAddMessageToChat', function ($client, $data) {   
    echo "C2 Operator (".$data['operatorUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";
});

$client2->addAction('clientAddMessageToChat', function ($client, $data) {   
    echo "C2 Client (".$data['clientUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";
});

$client2->addAction('operatorsDisconected', function ($client, $data) {   
    echo "C2 No operator left\n";
});

$client2->addAction('operatorConnected', function ($client, $data) {   
    echo "C2 New operator is available \n";
});

/* operator1 */

$operator1->afterConnect(function ($client) {
    echo "O1 After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$operator1->addAction('ping', function ($client, $data) {    
    $client->sendMessage(json_encode(['action'=>'pong']));
});

$operator1->addAction('uid', function ($client, $data) {    
    global $state;
    echo "O1 Server return clientUid: ".$data['uid']."\n"; 
    $state['operator1']['uid'] = $data['uid'];
});

$operator1->addAction('loginSuccess', function ($client, $data) {    
    echo "O1 Successful attempt to login as operator\n";   
});

$operator1->addAction('loginFailed', function ($client, $data) {    
    echo "O1 Unsuccessful attempt to login as operator\n";
});

$operator1->addAction('allOpenChats', function ($client, $data) {    
    echo "O1 Recived chat list\n";
    foreach ($data['chats'] as $chatUid) {
         echo "OP2 Recived chat: $chatUid\n";
    }   
});

$operator1->addAction('chatOpen', function ($client, $data) {    
    echo "O1 Client open chat ".$data['chatUid']."\n";  
});

$operator1->addAction('chatClose', function ($client, $data) {    
    echo "O1 Client close chat ".$data['chatUid']."\n";  
});

$operator1->addAction('chatHistory', function ($client, $data) {    
    echo "O1 chat history: ".json_encode($data['chatHistory'])."\n";
    
    foreach ($data['chatHistory']['messages'] as $message) {
        echo $message['from']." ".$message['type']." ".$message['message']."\n";
    }
});

$operator1->addAction('operatorAddMessageToChat', function ($client, $data) {   
    echo "O1 Operator (".$data['operatorUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";
});

$operator1->addAction('clientAddMessageToChat', function ($client, $data) {    
    echo "O1 Client (".$data['clientUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";  
});

$operator1->addAction('clientDisconected', function ($client, $data) {    
    echo "O1 client disconected: ".$data['clientUid']."\n";
});

$operator1->addAction('allClientsDisconectedFromChat', function ($client, $data) {    
    echo "O1 No client remaining in chat ".$data['chatUid']."\n";
});

$operator1->addAction('chatClosed', function ($client, $data) {    
    echo "O1 Chat closed ".$data['chatUid']."\n";
});

/* operator2*/

$operator2->afterConnect(function ($client) {
    echo "O2 After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$operator2->addAction('ping', function ($client, $data) {    
    $client->sendMessage(json_encode(['action'=>'pong']));
});

$operator2->addAction('uid', function ($client, $data) {    
    global $state;
    echo "O2 Server return clientUid: ".$data['uid']."\n"; 
    $state['operator2']['uid'] = $data['uid'];
});


$operator2->addAction('loginSuccess', function ($client, $data) {    
    echo "O2 Successful attempt to login as operator\n";   
});

$operator2->addAction('loginFailed', function ($client, $data) {    
    echo "O2 Unsuccessful attempt to login as operator\n";
});

$operator2->addAction('allOpenChats', function ($client, $data) {    
    echo "O2 Recived chat list\n";
    foreach ($data['chats'] as $chatUid) {
         echo "O2 Recived chat: $chatUid\n";
    }   
});

$operator2->addAction('chatOpen', function ($client, $data) {    
    echo "O2 Client open chat ".$data['chatUid']."\n";  
});

$operator2->addAction('chatClose', function ($client, $data) {    
    echo "O2 Client close chat ".$data['chatUid']."\n";  
});

$operator2->addAction('chatHistory', function ($client, $data) {    
    echo "O2 chat history: ".json_encode($data['chatHistory'])."\n";
    
    foreach ($data['chatHistory']['messages'] as $message) {
        echo $message['from']." ".$message['type']." ".$message['message']."\n";
    }
});

$operator2->addAction('operatorAddMessageToChat', function ($client, $data) {   
    echo "O2 Operator (".$data['operatorUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";
});

$operator2->addAction('clientAddMessageToChat', function ($client, $data) {    
    echo "O2 Client (".$data['clientUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";  
});

$operator2->addAction('clientDisconected', function ($client, $data) {    
    echo "O2 client disconected: ".$data['clientUid']."\n";
});

$operator2->addAction('allClientsDisconectedFromChat', function ($client, $data) {    
    echo "O2 No client remaining in chat ".$data['chatUid']."\n";
});

$operator2->addAction('chatClosed', function ($client, $data) {    
    echo "O2 Chat closed ".$data['chatUid']."\n";
});


$pool = new WebSocketPool();

/* ACTIONS */
$pool->addAction(['delay'=>1000000], function(){
    echo "AC1 Client1 open new chat\n";
    
    global $state;
    global $client1;
    
    $client1->sendMessage(json_encode(['action'=>'openChat', 'chatUid'=>'']));
    
});

$pool->addAction(['delay'=>2000000], function(){
    echo "AC2 Operator1 login\n";
    
    global $state;
    global $operator1;

    $operator1->sendMessage(json_encode(['action'=>'login','token'=>'password']));
});

$pool->addAction(['delay'=>3000000], function(){
    echo "AC3 Operator1 get all open chats\n";
    
    global $state;
    global $operator1;

    $operator1->sendMessage(json_encode(['action'=>'getAllOpenChats']));
});

$pool->addAction(['delay'=>4000000], function(){
    echo "AC4 Client1 add message to chat\n";
    
    global $state;
    global $client1;

    $client1->sendMessage(json_encode([
        'action'=>'addClientMessageToChat',
        'chatUid'=>$state['client1']['chatUid'],
        'message'=>'message1'
    ]));
});

$pool->addAction(['delay'=>5000000], function(){
    echo "AC5 Operator1 add message to chat\n";
    
    global $state;
    global $operator1;

    $operator1->sendMessage(json_encode([
        'action'=>'addOperatorMessageToChat',
        'chatUid'=>$state['client1']['chatUid'],
        'message'=>'message2'
    ]));
});

$pool->addAction(['delay'=>6000000], function(){
    echo "AC6 Client2 open existing chat\n";
    
    global $state;
    global $client2;
    
    $client2->sendMessage(json_encode(['action'=>'openChat', 'chatUid'=> $state['client1']['chatUid']]));
});

$pool->addAction(['delay'=>7000000], function(){
    echo "AC7 Client2 add message to chat\n";
    
    global $state;
    global $client2;
    
     $client2->sendMessage(json_encode([
        'action'=>'addClientMessageToChat',
        'chatUid'=>$state['client1']['chatUid'],
        'message'=>'message3'
    ]));
});
  
$pool->addAction(['delay'=>8000000], function(){
    echo "AC8 Operator1 add message to chat\n";
    
    global $state;
    global $operator1;
    
     $operator1->sendMessage(json_encode([
        'action'=>'addOperatorMessageToChat',
        'chatUid'=>$state['client1']['chatUid'],
        'message'=>'message4'
    ]));
});

$pool->addAction(['delay'=>9000000], function(){
    echo "AC9 Operator3 get chat history\n";
    
    global $state;
    global $operator1;
    
     $operator1->sendMessage(json_encode([
        'action'=>'getChatHistory',
        'chatUid'=>$state['client1']['chatUid']
    ]));
});

$pool->addAction(['delay'=>10000000], function(){
    echo "AC10 Client1 close\n";
    
    global $client1;
    
    $client1->sendMessage(json_encode([
        'action'=>'close'
    ]));
});

$pool->addAction(['delay'=>11000000], function(){
    echo "AC11 Operator1 close\n";
    
    global $operator1;
    
    $operator1->sendMessage(json_encode([
        'action'=>'close'
    ]));
});

$pool->addAction(['delay'=>12000000], function(){
    echo "AC12 Operator2 login\n";
    
    global $state;
    global $operator2;

    $operator2->sendMessage(json_encode(['action'=>'login','token'=>'password']));
});

$pool->addAction(['delay'=>13000000], function(){
    echo "AC13 Operator2 get all open chats\n";
    
    global $state;
    global $operator2;

    $operator2->sendMessage(json_encode(['action'=>'getAllOpenChats']));
});

$pool->addAction(['delay'=>14000000], function(){
    echo "AC14 Client2 close\n";
    
    global $client2;
    
    $client2->sendMessage(json_encode([
        'action'=>'close'
    ]));
});

$pool->addAction(['delay'=>15000000, 'repeat'=> 1000000], function(){
    echo "repeat test\n";    
});

  
$pool->listen([
    $client1, $client2, $operator1, $operator2
]);
