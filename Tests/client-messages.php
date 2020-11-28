<?php

use pekand\WebSocketServer\WebsocketClient;
use pekand\WebSocketServer\WebSocketPool;

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
    $client->send(['action'=>'getUid']);
});

$client1->addListener(function ($client, $request) {    
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->send(['action'=>'pong']);      
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
        echo "C1 Accessdenied to opperation: ".print_r($data['errors'], true)."\n";
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
    $client->send(['action'=>'getUid']);
});

$client2->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->send(['action'=>'pong']);      
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
    $client->send(['action'=>'getUid']);
});

$operator1->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->send(['action'=>'pong']);      
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
    }
});

/* CLIENT3 */

$client3->afterConnect(function ($client) {
    echo "C3 After connect\n";
    $client->send(['action'=>'getUid']);
});

$client3->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->send(['action'=>'pong']);      
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
    $client->send(['action'=>'getUid']);
});

$client4->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->send(['action'=>'pong']);      
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
    $client->send(['action'=>'getUid']);
});

$operator2->addListener(function ($client, $request) {
    global $state;
    
    $data = json_decode($request, true);
    if (!isset($data['action'])) {
        return;
    }
    
    if($data['action'] == 'ping') { 
        $client->send(['action'=>'pong']);      
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
$pool->addAction(['delay'=>1000000], function(){
    echo "Action: Client1 send message to self and client2 \n";
    
    global $state;
    global $client1;
    
    $client1->send(['action'=>'ping']);

    //$client->send(['action'=>'shutdown']);
  
    if(isset($state['client1']['uid'])){
       $client1->send([
        'action'=>'sendMessage',
        'to'=>$state['client1']['uid'],
        'message'=>"Mesage from client1 to client1"
       ]); 
    }     
   
    if(isset($state['client2']['uid'])){
        $client1->send([
            'action'=>'sendMessage', 
            'to'=> $state['client2']['uid'],
            'message'=>"Mesage from client1 to client2"
        ]); 
    }
   
   $longMessage1 = "";
   for($i=0; $i<125-3;$i++){
     $longMessage1 .= "O";
   }
   
   if(isset($state['client2']['uid'])){
        $client1->send([
            'action'=>'sendMessage', 
            'to'=> $state['client2']['uid'],
            'message'=>"L".$longMessage1."NG"
        ]); 
    }
   
   $longMessage2 = "";
   for($i=0; $i<126-3;$i++){
     $longMessage2 .= "O";
   }
   
   if(isset($state['client2']['uid'])){
       $client1->send([
            'action'=>'sendMessage', 
            'to'=> $state['client2']['uid'],
            'message'=>"L".$longMessage2."NG"
        ]); 
    }
   
   $longMessage3 = "";
   for($i=0; $i<127-3;$i++){
     $longMessage3 .= "O";
   }
   
   if(isset($state['client2']['uid'])){
        $client1->send([
            'action'=>'sendMessage', 
            'to'=> $state['client2']['uid'],
            'message'=>"L".$longMessage3."NG"
        ]); 
    }
   
   $longMessage4 = "";
   for($i=0; $i<2**16-3;$i++){
     $longMessage4 .= "O";
   }
   
   if(isset($state['client2']['uid'])){
       $client1->send([
        'action'=>'sendMessage', 
        'to'=> $state['client2']['uid'],
        'message'=>"L".$longMessage4."NG"
        ]); 
   }
   
   $longMessage5 = "";
   for($i=0; $i<2**17-3;$i++){
     $longMessage5 .= "O";
   }
   
    if(isset($state['client2']['uid'])){
        $client1->send([
            'action'=>'sendMessage', 
            'to'=> $state['client2']['uid'],
            'message'=>"L".$longMessage5."NG"
        ]); 
    }
   
});

$pool->addAction(['delay'=>2000000], function(){
    echo "Action: Client1 invalid login as operator \n";
    
    global $state;
    global $client1;
    
    $client1->send(['action'=>'login','username'=>'admin', 'password'=>'wrong_pasword']);
    $client1->send(['action'=>'logout']);
    $client1->send(['action'=>'login','username'=>'admin', 'password'=>'password']);
    $client1->send(['action'=>'logout']);
    $client1->send(['action'=>'isOperatorLogged']);
    //$client->send(['action'=>'shutdown']);
  
});

$pool->addAction(['delay'=>3000000], function(){
    echo "Action: Client1 login and logout as operator \n";
    
    global $state;
    global $client1;
    
    $client1->send(['action'=>'login','username'=>'admin', 'password'=>'password']);
    $client1->send(['action'=>'logout']);
});

$pool->addAction(['delay'=>4000000], function(){
    echo "Action: Client1 check if operator is still logged \n";
    
    global $state;
    global $client1;
    
    $client1->send(['action'=>'isOperatorLogged']);
});

$pool->addAction(['delay'=>5000000], function(){
    echo "Action: Client2 send mesage to all operators\n";
    
    global $state;
    global $client2;
    
    
    $client2->send([
      'action'=>'sendMessageToOperator',
      'message'=>"message from client2 to all operators",
    ]);
    
});

$pool->addAction(['delay'=>6000000], function(){
    echo "Action: operator1 login\n";
    
    global $state;
    global $operator1;

    $operator1->send(['action'=>'login','username'=>'admin', 'password'=>'password']);
});

$pool->addAction(['delay'=>7000000], function(){
    echo "Action: operator1 get opened clients\n";
    
    global $state;
    global $operator1;

    $operator1->send(['action'=>'getClients']);
});

$pool->addAction(['delay'=>8000000], function(){
    echo "Action: Operator1 broadcast message to all clients\n";
    
    global $state;
    global $operator1;

    $operator1->send(['action'=>'broadcast', "message"=>"Mesage from operator1 to all clients"]);
});

$pool->addAction(['delay'=>9000000], function(){
    echo "Action: operator1 send message to client1 \n";
    
    global $state;
    global $operator1;
       
    if(isset($state['client1']['uid'])){
        $operator1->send([
            'action'=>'sendMessage', 
            'to'=> $state['client1']['uid'],
            'message'=>"Mesage from operator1 to client1"
        ]);
    }
});

$pool->addAction(['delay'=>10000000], function(){
    echo "Action: Client2 close connection\n";
    
    global $state;
    global $client2;
    
    
    $client2->send(['action'=>'close']); // TODO
    
});

$pool->addAction(['delay'=>11000000], function(){
    echo "Action: Closed client2 send messge to client1\n";
    
    global $state;
    global $client2;
    
    if(isset($state['client1']['uid'])){  
        $client2->send([
            'action'=>'sendMessage', 
            'to'=> $state['client1']['uid'],
            'message'=>"Mesage from client2 to client1"
        ]);
    }
    
});

$pool->addAction(['delay'=>12000000], function(){
    echo "Action: Operator2 login\n";
    
    global $state;
    global $operator2;

    $operator2->send(['action'=>'login','username'=>'admin', 'password'=>'password']);
});

$pool->addAction(['delay'=>13000000], function(){    
    echo "Action: CLose all operators\n";
    global $operator1;
    global $operator2;

    $operator1->send(['action'=>'close']);
    $operator2->send(['action'=>'close']);
    
});

$pool->addAction(['delay'=>14000000], function(){    
    echo "Action: CLose all clients\n";
    global $client1;
    global $client3;

    $client1->send(['action'=>'close']);
    $client3->send(['action'=>'close']);
    
});

$pool->listen([
    $client1, $client2, $operator1,
    $client4, $client3, $operator2
]);
