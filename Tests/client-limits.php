<?php

use pekand\WebSocketServer\WebSocketClient;
use pekand\WebSocketServer\WebSocketPool;

$clients = [];
$state = [];

$pool = new WebSocketPool();

for($i=0; $i<100; $i++){
    $client = new WebSocketClient();
    $clients[$i] = $client;
    
    $client->afterConnect(function ($client) {
        echo "After connect\n";
        $client->send(['action'=>'getUid']);
    });

    $client->addAction('ping', function ($client, $data) {   
        $client->send(['action'=>'pong']);          
    });

    $client->addAction('uid', function ($client, $data) {   
        global $state;
        echo "Server return clientUid: ".$data['uid']."\n"; 
        $state['client1']['uid'] = $data['uid'];    
    });

    $client->addAction('chatUid', function ($client, $data) {   
        global $state;
        echo "Server return chatUid: ".$data['chatUid']."\n";
        $state['client1']['chatUid'] = $data['chatUid'];
    });

    $client->addAction('operatorAddMessageToChat', function ($client, $data) {   
        echo "Operator (".$data['operatorUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";
    });

    $client->addAction('clientAddMessageToChat', function ($client, $data) {   
        echo "Client (".$data['clientUid'].") add message to chat (".$data['chatUid'].") message: ".$data['message']."\n";
    });

    $client->addAction('operatorsDisconected', function ($client, $data) {   
        echo "No operator left\n";
    });

    $client->addAction('operatorConnected', function ($client, $data) {   
        echo "New operator is available \n";
    });

    $pool->addAction(['delay'=>1000000], function() use ($client, $i){
        echo "C{$i} Client open new chat\n";        
        $client->send(['action'=>'openChat', 'chatUid'=>'']);
    });

    
}

$pool->listen($clients);
