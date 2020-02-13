<?php

use WebSocketServer\WebsocketClient;
use WebSocketServer\WebSocketPool;

$client1 = new WebsocketClient();
$client2 = new WebsocketClient();
$operator1 = new WebsocketClient();

$client3 = new WebsocketClient();
$client4 = new WebsocketClient();
$operator2 = new WebsocketClient();

$state = [];

/* CLIENT1 */

$client1->afterConnect(function ($client) {
    echo "C1 After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$client1->addListener(function ($client, $request) {    
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->sendMessage(json_encode(['action'=>'pong']));      
    }
    
    if($data['action'] == 'uid') { 
       echo "C1 Server return clientUid: ".$data['uid']."\n"; 
       $state['client1']['uid'] = $data['uid'];
    }
    
    if($data['action'] == 'pong') { 
        echo "C1 Server response to pong\n";
    }

    if($data['action'] == 'loginSuccess') { 
        echo "C1 Successful attempt to login as operator\n";
    }
    
    if($data['action'] == 'logoutSuccess') { 
        echo "C1 Successful attempt to logout as operator\n";
    }
    
    if($data['action'] == 'loginFailed') { 
        echo "C1 Unsuccessful attempt to login as operator\n";
    }
    
    if($data['action'] == 'accessDenied') { 
        echo "C1 Accessdenied to opperation: ".$data['forbidden']."\n";
    }
    
    if($data['action'] == 'operatorBroadcastMessage') { 
        echo "C1 operator (".$data['operator']."): broadcast message: ".$data['message']."\n";
    }
    
    if($data['action'] == 'operatorConnected') { 
        echo "C1 operator is connected\n";
    }
    
    if($data['action'] == 'allOperatorsDisconected') { 
        echo "C1 no operator is connected notification\n";
    }
    
    if($data['action'] == 'message') { 
        echo "C1 mesage from (".$data['from']."): ".$data['message']."\n";
    }

});

/* CLIENT2 */

$client2->afterConnect(function ($client) {
    echo "C2 After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$client2->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->sendMessage(json_encode(['action'=>'pong']));      
    }
    
    if($data['action'] == 'uid') { 
       echo "C2: Server return clientUid: ".$data['uid']."\n"; 
       $state['client2']['uid'] = $data['uid'];
    }
    
    if($data['action'] == 'operatorBroadcastMessage') { 
        echo "C2 operator (".$data['operator']."): broadcast message: ".$data['message']."\n";
    }
    
    if($data['action'] == 'message') { 
        echo "C2 mesage from (".$data['from'].") (".strlen($data['message'])."): ".substr($data['message'], 0,  1000)."\n";
    }
    
});

/* OPERATOR1 */

$operator1->afterConnect(function ($client) {
    echo "OP After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$operator1->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->sendMessage(json_encode(['action'=>'pong']));      
    }
    
    if($data['action'] == 'uid') { 
        echo "OP1 Server return clientUid: ".$data['uid']."\n";   
        $state['operator1']['uid'] = $data['uid'];
    }
    
    if($data['action'] == 'loginSuccess') { 
        echo "OP1 Successful attempt to login as operator\n";     
    }
    
    if($data['action'] == 'loginFailed') { 
        echo "OP1 Unsuccessful attempt to login as operator\n";
    }
    
    if($data['action'] == 'clients') { 
        echo "OP1 Recived client list\n";
        foreach ($data['clients'] as $clientUid) {
             echo "OP1 Recived client: $clientUid\n";
        }      
    }
    
    if($data['action'] == 'clientDisconected') { 
        echo "OP1 client disconected: ".$data['clientUid']."\n";
    }
    
    if($data['action'] == 'messageFromClient') { 
        echo "OP1 Recived mesasge from client (".$data['from'].") message: ".$data['message']."\n";
    }
    
    if($data['action'] == 'message') { 
        echo "OP1 mesage from (".$data['from']."): ".$data['message']."\n";
        /*$client->sendMessage(json_encode(['action'=>'close']));*/
    }
});

/* CLIENT3 */

$client3->afterConnect(function ($client) {
    echo "C3 After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$client3->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->sendMessage(json_encode(['action'=>'pong']));      
    }
    
    if($data['action'] == 'uid') { 
       echo "C3: Server return clientUid: ".$data['uid']."\n"; 
       
       $state['client3']['uid'] = $data['uid'];
    }
    
    if($data['action'] == 'chatUid') { 
       echo "C3: Server return chatUid: ".$data['chatUid']."\n";
       $state['client3']['chatUid'] = $data['chatUid'];
    } 
});

/* CLIENT4 */

$client4->afterConnect(function ($client) {
    echo "C4 After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$client4->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->sendMessage(json_encode(['action'=>'pong']));      
    }
    
    if($data['action'] == 'uid') { 
       echo "C4: Server return clientUid: ".$data['uid']."\n"; 
       $state['client4']['uid'] = $data['uid'];
    }
    
    if($data['action'] == 'chatUid') { 
       echo "C4: Server return chatUid: ".$data['chatUid']."\n";
       $state['client4']['chatUid'] = $data['chatUid'];
    } 
    
    if($data['action'] == 'message') { 
        echo "C6 mesage from (".$data['from']."): ".$data['message']."\n";
    }
});

$operator2->afterConnect(function ($client) {
    echo "OP2 After connect\n";
    $client->sendMessage(json_encode(['action'=>'getUid']));
});

$operator2->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->sendMessage(json_encode(['action'=>'pong']));      
    }
    
    if($data['action'] == 'uid') { 
       echo "OP2: Server return clientUid: ".$data['uid']."\n"; 
       $state['operator2']['uid'] = $data['uid'];
    }
    
    if($data['action'] == 'loginSuccess') { 
        echo "OP2 Successful attempt to login as operator\n";   
    }
    
    if($data['action'] == 'loginFailed') { 
        echo "OP2 Unsuccessful attempt to login as operator\n";
    }
    
    if($data['action'] == 'allOpenChats') { 
        echo "OP2 Recived chat list\n";
        foreach ($data['chats'] as $chatUid) {
             echo "OP2 Recived chat: $chatUid\n";
        }       
    }
    
    if($data['action'] == 'clientDisconected') { 
        echo "OP2 client disconected: ".$data['clientUid']."\n";
    }
});

$pool = new WebSocketPool();

/* ACTIONS */
$pool->addAction(1000000, function(){
    echo "Action: Client1 send message to self and client2 \n";
    
    global $state;
    global $client1;
    
    $client1->sendMessage(json_encode(['action'=>'ping']));

    //$client->sendMessage(json_encode(['action'=>'shutdown']));
  
   $client1->sendMessage(json_encode([
    'action'=>'sendMessage',
    'to'=>$state['client1']['uid'],
    'message'=>"Mesage from client1 to client1"
   ]));      
   
   $client1->sendMessage(json_encode([
    'action'=>'sendMessage', 
    'to'=> $state['client2']['uid'],
    'message'=>"Mesage from client1 to client2"
    ])); 
   
   $longMessage1 = "";
   for($i=0; $i<125-3;$i++){
     $longMessage1 .= "O";
   }
   
   $client1->sendMessage(json_encode([
    'action'=>'sendMessage', 
    'to'=> $state['client2']['uid'],
    'message'=>"L".$longMessage1."NG"
    ])); 
   
   $longMessage2 = "";
   for($i=0; $i<126-3;$i++){
     $longMessage2 .= "O";
   }
   
   $client1->sendMessage(json_encode([
    'action'=>'sendMessage', 
    'to'=> $state['client2']['uid'],
    'message'=>"L".$longMessage2."NG"
    ])); 
   
   $longMessage3 = "";
   for($i=0; $i<127-3;$i++){
     $longMessage3 .= "O";
   }
   
   $client1->sendMessage(json_encode([
    'action'=>'sendMessage', 
    'to'=> $state['client2']['uid'],
    'message'=>"L".$longMessage3."NG"
    ])); 
   
   $longMessage4 = "";
   for($i=0; $i<2**16-3;$i++){
     $longMessage4 .= "O";
   }
   
   $client1->sendMessage(json_encode([
    'action'=>'sendMessage', 
    'to'=> $state['client2']['uid'],
    'message'=>"L".$longMessage4."NG"
    ])); 
   
   $longMessage5 = "";
   for($i=0; $i<2**17-3;$i++){
     $longMessage5 .= "O";
   }
   
   $client1->sendMessage(json_encode([
    'action'=>'sendMessage', 
    'to'=> $state['client2']['uid'],
    'message'=>"L".$longMessage5."NG"
    ])); 
   
});

$pool->addAction(2000000, function(){
    echo "Action: Client1 invalid login as operator \n";
    
    global $state;
    global $client1;
    
    $client1->sendMessage(json_encode(['action'=>'login','token'=>'wrong_pasword']));
    $client1->sendMessage(json_encode(['action'=>'logout']));
    $client1->sendMessage(json_encode(['action'=>'login','token'=>'password']));
    $client1->sendMessage(json_encode(['action'=>'logout']));
    $client1->sendMessage(json_encode(['action'=>'isOperatorLogged']));
    //$client->sendMessage(json_encode(['action'=>'shutdown']));
  
});

$pool->addAction(3000000, function(){
    echo "Action: Client1 login and logout as operator \n";
    
    global $state;
    global $client1;
    
    $client1->sendMessage(json_encode(['action'=>'login','token'=>'password']));
    $client1->sendMessage(json_encode(['action'=>'logout']));
});

$pool->addAction(4000000, function(){
    echo "Action: Client1 check if operator is still logged \n";
    
    global $state;
    global $client1;
    
    $client1->sendMessage(json_encode(['action'=>'isOperatorLogged']));
});

$pool->addAction(5000000, function(){
    echo "Action: Client2 send mesage to all operators\n";
    
    global $state;
    global $client2;
    
    
    $client2->sendMessage(json_encode([
      'action'=>'sendMessageToOperator',
      'message'=>"message from client2 to all operators",
    ]));
    
});

$pool->addAction(6000000, function(){
    echo "Action: operator1 login\n";
    
    global $state;
    global $operator1;

    $operator1->sendMessage(json_encode(['action'=>'login','token'=>'password']));
});

$pool->addAction(7000000, function(){
    echo "Action: operator1 get opened clients\n";
    
    global $state;
    global $operator1;

    $operator1->sendMessage(json_encode(['action'=>'getClients']));
});

$pool->addAction(8000000, function(){
    echo "Action: Operator1 broadcast message to all clients\n";
    
    global $state;
    global $operator1;

    $operator1->sendMessage(json_encode(['action'=>'broadcast', "message"=>"Mesage from operator1 to all clients"]));
});

$pool->addAction(9000000, function(){
    echo "Action: operator1 send message to client1 \n";
    
    global $state;
    global $operator1;
       
    $operator1->sendMessage(json_encode([
        'action'=>'sendMessage', 
        'to'=> $state['client1']['uid'],
        'message'=>"Mesage from operator1 to client1"
    ]));
});

$pool->addAction(10000000, function(){
    echo "Action: Client2 close connection\n";
    
    global $state;
    global $client2;
    
    
    $client2->sendMessage(json_encode(['action'=>'close'])); // TODO
    
});

$pool->addAction(11000000, function(){
    echo "Action: Closed client2 send messge to client1\n";
    
    global $state;
    global $client2;
    
    
    $client2->sendMessage(json_encode([
        'action'=>'sendMessage', 
        'to'=> $state['client1']['uid'],
        'message'=>"Mesage from client2 to client1"
    ]));
    
});

$pool->addAction(12000000, function(){
    echo "Action: Operator2 login\n";
    
    global $state;
    global $operator2;

    $operator2->sendMessage(json_encode(['action'=>'login','token'=>'password']));
});

$pool->addAction(13000000, function(){    
    echo "Action: CLose all operators\n";
    global $operator1;
    global $operator2;

    $operator1->sendMessage(json_encode(['action'=>'close']));
    $operator2->sendMessage(json_encode(['action'=>'close']));
    
});

$pool->addAction(14000000, function(){    
    echo "Action: CLose all clients\n";
    global $client1;
    global $client3;

    $client1->sendMessage(json_encode(['action'=>'close']));
    $client3->sendMessage(json_encode(['action'=>'close']));
    
});

  
$pool->listen([
    $client1, $client2, $operator1,
    $client4, $client3, $operator2
]);
