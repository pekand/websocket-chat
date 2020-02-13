<?php

namespace SocketServer;

class SocketServer {   
    
    private $socket = null;
    
    private $clients = [];
       
    protected $options = [
        'ip'=> '0.0.0.0', 
        'port' => 8080,
        'waitInterval' => 50000, // event loop wait cicle (in ms)
        'clientInactivityInterval' => 30, // check with ping if client live after this interval (in s)
        'maxClientInactivityInterval' => 60, // if client not respond to ping or send eny message get killed after clientInactivityInterval + maxClientInactivityInterval (in s)
        'maxClientHeaderLength' => 9999, // first request maximal size (0 is unlimite)
        'maxClientRequestLength' => 0, // (0 is unlimite)
        'maxClientLiveTime' => 0, // How long can be active client connected to server (0 is unlimite)
        'maxClientRequestCount' => 0, // (0 is unlimite)
        'maxClientRequestPerMinuteCount' => 1000, // (0 is unlimite)
        'maxClientsCount' => 1000, // max clients on server (0 is unlimite)
    ];
       
    public function __construct($options = []) {
        
        $this->options = array_merge($this->options, $options);
        
        if(!extension_loaded("sockets")) {
            die("php sockets extension is required and not loaded!!");
        }
        
        if(!extension_loaded("openssl")) {
            die("php openssl extension is required and not loaded!!");
        }
    }
    
    public function uid() {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }
    
    public function afterServerError($afterServerErrorEvent = null) {
        $this->afterServerErrorEvent = $afterServerErrorEvent;
        return $this;
    }
    
    public function processSocketError($clientUid = null) {
        
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);

        if(isset($clientUid)) {
            if ($errorcode == 10053 || $errorcode == 10054) { // client disconected
                $this->clients[$clientUid]['live'] = false;
                if (isset($this->clientDisconnectedEvent) && is_callable($this->clientDisconnectedEvent)) {
                     call_user_func_array($this->clientDisconnectedEvent, [$clientUid, 'CLIENT_UNEXPECTEDLY_CLOSED_SOCKET']);
                }
                
                $this->closeClient($clientUid);         
            } else {
                if (isset($this->afterClientErrorEvent) && is_callable($this->afterClientErrorEvent)) {
                    call_user_func_array($this->afterClientErrorEvent, [$clientUid, $errorcode, $errormsg]);
                }
            }
        } else {            
            if (isset($this->afterServerErrorEvent) && is_callable($this->afterServerErrorEvent)) {
                call_user_func_array($this->afterServerErrorEvent, [$errorcode, $errormsg]);
            }
        }
            
        return $this;
    }
    
    public function bindSocket() {      
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(!@socket_bind($socket, $this->options['ip'], $this->options['port'])){
            $this->processSocketError();
        } else {
            if(!socket_listen($socket)) {
                $this->processSocketError();
                                
            } else {
                $this->socket = $socket;
                if(!socket_set_nonblock($this->socket)) {
                    $this->processSocketError();
                }
            }
        }

        return $this;
    }
    
    public function getClient($clientUid) {
       if (isset($this->clients[$clientUid])) {
         return $this->clients[$clientUid];
       }
       
       return null;
    }
    
    public function isClient($clientUid) {
       return isset($this->clients[$clientUid]);
    }
    
    public function closeClient($clientUid) {
        
        if (isset($this->clients[$clientUid])) {
            if ($this->clients[$clientUid]['ref'] !== null){
                socket_close($this->clients[$clientUid]['ref']);
                $this->clients[$clientUid]['ref'] = null;
            }        
        
            unset($this->clients[$clientUid]);
        }
    }
    
    public function afterClientError($afterClientErrorEvent = null) {
        $this->afterClientErrorEvent = $afterClientErrorEvent;
        return $this;
    }
    
    public function sendData($clientUid, $data) {
        
        if (!isset($this->clients[$clientUid]) || !$this->clients[$clientUid]['live']) {
            return false;
        }
        
        $result = @socket_write($this->clients[$clientUid]['ref'], $data, strlen($data));
        
        if (false === $result) {            
            $this->processSocketError($clientUid);
            return false;
        }
        
        return true;
    }

    public function acceptClient($acceptClientEvent = null) {
        $this->acceptClientEvent = $acceptClientEvent;
        return $this;
    }
    
    public function clientDisconnected($clientDisconnectedEvent = null) {
        $this->clientDisconnectedEvent = $clientDisconnectedEvent;
        return $this;
    }

    public function buildPing($buildPingEvent) { 
        $this->buildPingEvent = $buildPingEvent;
        return $this;
    }
    
    
    public function afterShutdown($afterShutdownEvent = null) {
        $this->afterShutdownEvent = $afterShutdownEvent;
        return $this;
    }
    
    public function shutdown() { 
        $this->listening = false;
        return $this;
    }
    
    public function listenToSocket($readFromClientEvent = null) {
        
        $this->bindSocket();
        
        if (!$this->socket){
            return;
        }
        
        
        $this->listening = true;
        
        while($this->listening)
        {
            if(($ref = socket_accept($this->socket)) !== false)
            {
                
                if ($this->options['maxClientsCount'] != 0 && count($this->clients) >= $this->options['maxClientsCount']) {
                    if (isset($this->afterServerErrorEvent) && is_callable($this->afterServerErrorEvent)) {
                         call_user_func_array($this->afterServerErrorEvent, [null, 'SERVER_IS_FULL']);
                    }
                    
                    socket_close($ref);
                } 
                
                if(is_resource($ref)) {
                    
                    $clientUid =  $this->uid();
                    
                    $this->clients[$clientUid] = [
                        'uid' => $clientUid,
                        'ref' => $ref,
                        'created' => microtime(true),
                        'live' => true,
                        'requestCount' => 0,

                        'clientInactivityInterval' => $this->options['clientInactivityInterval'] == 0 ? false :  $this->options['clientInactivityInterval'],
                        'maxClientInactivityInterval' => $this->options['maxClientInactivityInterval'] == 0 ? false :  $this->options['maxClientInactivityInterval'],
                        
                        'maxClientLiveTime' => $this->options['maxClientLiveTime'],
                        'maxClientHeaderLength' => $this->options['maxClientHeaderLength'] == 0 ? false :  $this->options['maxClientHeaderLength'],
                        'maxClientRequestLength' => $this->options['maxClientRequestLength'] == 0 ? false :  $this->options['maxClientRequestLength'],
                        'maxClientRequestCount' => $this->options['maxClientRequestCount'] == 0 ? false :  $this->options['maxClientRequestCount'],
                        'maxClientRequestPerMinuteCount' => $this->options['maxClientRequestPerMinuteCount'] == 0 ? false :  $this->options['maxClientRequestPerMinuteCount'],
                        
                        'requestPerMinuteInterval' => microtime(true),
                        'requestPerMinuteCount' => 0,
 
                        'lastActivityTime' => microtime(true),
                        'ping' => false,
                    ];
                    
                    $headerFromServer = "";
                    
                    /*if (false === ($headerFromServer = socket_read($this->clients[$clientUid]['ref'], 2048, MSG_WAITALL))) {
                        echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($this->socket)) . "\n";
                    }*/

                    socket_set_nonblock($this->clients[$clientUid]['ref']);
 
                    while ($buf = @socket_read($this->clients[$clientUid]['ref'], 1024)) {  
                    
                        if (false === $buf){
                            $this->processSocketError($clientUid);
                            break;                            
                        }   
                                           
                        $headerFromServer .= $buf;
                        
                        if($this->clients[$clientUid]['maxClientHeaderLength'] !== false && $this->clients[$clientUid]['maxClientHeaderLength'] < strlen($headerFromServer)) {                              
                            break;
                        }
                    }

                    if($this->clients[$clientUid]['maxClientHeaderLength'] !== false && $this->clients[$clientUid]['maxClientHeaderLength'] < strlen($headerFromServer)) {
                        if (isset($this->clientDisconnectedEvent) && is_callable($this->clientDisconnectedEvent)) {
                             call_user_func_array($this->clientDisconnectedEvent, [$clientUid, 'CLIENT_HEADER_IS_TOO_BIG']);
                        }
                        
                        $this->closeClient($clientUid);
                    } else {
                        $acceptedNewClient = true;
                        if (isset($this->acceptClientEvent) && is_callable($this->acceptClientEvent)) {
                            $acceptedNewClient = call_user_func_array($this->acceptClientEvent, [$clientUid, $headerFromServer]);
                        }

                        if (!$acceptedNewClient) {
                            $this->closeClient($clientUid);
                        }
                    }
                }
            }

            if (count($this->clients)) {
                
                $cientUids = [];
                foreach ($this->clients as $client) {
                    $cientUids[] = $client['uid'];
                }
                
                foreach ($cientUids as $clientUid) {

                    if (!isset($this->clients[$clientUid]) || !is_array($this->clients[$clientUid]) || $this->clients[$clientUid]['ref'] === null) {
                        continue;
                    }
                    
                    $data = "";
                    while ($buf = @socket_read($this->clients[$clientUid]['ref'], 1024)) {
                        $data .= $buf;
                        
                        if($this->clients[$clientUid]['maxClientRequestLength'] !== false && $this->clients[$clientUid]['maxClientRequestLength'] < strlen($data)) {
                            break; 
                        }
                    }
                    
                    if($this->clients[$clientUid]['maxClientRequestLength'] !== false && $this->clients[$clientUid]['maxClientRequestLength'] < strlen($data)) {
                        if (isset($this->clientDisconnectedEvent) && is_callable($this->clientDisconnectedEvent)) {
                             call_user_func_array($this->clientDisconnectedEvent, [$clientUid, 'CLIENT_REQUEST_IS_TOO_BIG']);
                        }
                        
                        $this->closeClient($clientUid);
                        continue; 
                    }
                        

                    if ($data === "") {                       
                        
                        if($this->clients[$clientUid]['maxClientLiveTime'] > 0 && microtime(true) - $this->clients[$clientUid]['created'] > $this->clients[$clientUid]['maxClientLiveTime']) {
                            if (isset($this->clientDisconnectedEvent) && is_callable($this->clientDisconnectedEvent)) {
                                 call_user_func_array($this->clientDisconnectedEvent, [$clientUid, 'CLIENT_LIVE_TO_LONG']);
                            }
                            
                            $this->closeClient($clientUid);
                            continue;
                        } else if(microtime(true) - $this->clients[$clientUid]['lastActivityTime'] > $this->clients[$clientUid]['maxClientInactivityInterval']) { // kill connection due to inactivity
                            if (isset($this->clientDisconnectedEvent) && is_callable($this->clientDisconnectedEvent)) {
                                call_user_func_array($this->clientDisconnectedEvent, [$clientUid, 'CLIENT_IS_INACTIVE_TO_LONG']);
                            }
                            
                            $this->closeClient($clientUid);
                            continue;
                        } else if (isset($this->buildPingEvent) && is_callable($this->buildPingEvent)) {
                            if($this->clients[$clientUid]['ping'] == false &&  microtime(true) - $this->clients[$clientUid]['lastActivityTime'] > $this->clients[$clientUid]['clientInactivityInterval']) { // check if client still listening
                                call_user_func_array($this->buildPingEvent, [$clientUid]);
                            }
                        }

                        continue;
                    }

                    $this->clients[$clientUid]['lastActivityTime'] = microtime(true);
                    $this->clients[$clientUid]['ping'] = false;  

                    if($this->clients[$clientUid]['maxClientRequestCount'] !== false) {
                        if ($this->clients[$clientUid]['maxClientRequestCount'] <= 0) {
                            if (isset($this->clientDisconnectedEvent) && is_callable($this->clientDisconnectedEvent)) {
                                 call_user_func_array($this->clientDisconnectedEvent, [$clientUid, 'CLIENT_HAS_TOO_MANY_REQUESTS']);
                            }
                            
                            $this->closeClient($clientUid);
                            continue;
                        }  else {
                            $this->clients[$clientUid]['maxClientRequestCount']--;
                        }
                    }
                    
                    if($this->clients[$clientUid]['maxClientRequestPerMinuteCount'] !== false) {
                        if ($this->clients[$clientUid]['maxClientRequestPerMinuteCount'] <= $this->clients[$clientUid]['requestPerMinuteCount']) {
                            if (isset($this->clientDisconnectedEvent) && is_callable($this->clientDisconnectedEvent)) {
                                 call_user_func_array($this->clientDisconnectedEvent, [$clientUid, 'CLIENT_HAS_TOO_MANY_REQUESTS_PER_MINUTE']);
                            }
                            
                            $this->closeClient($clientUid);
                            continue;
                        }
                        
                        if(microtime(true) - $this->clients[$clientUid]['requestPerMinuteInterval'] >= 60) {
                            $this->clients[$clientUid]['requestPerMinuteInterval'] = microtime(true);
                            $this->clients[$clientUid]['requestPerMinuteCount'] = 0;
                        }
                        
                        $this->clients[$clientUid]['requestPerMinuteCount']++;
                    }

                    $this->clients[$clientUid]['requestCount']++;
 
                    if (is_callable($readFromClientEvent)) {
                        call_user_func_array($readFromClientEvent, [$clientUid, $data]);
                    }
                }
            }            
            usleep($this->options['waitInterval']);
        }
        
        if (isset($this->afterShutdownEvent) && is_callable($this->afterShutdownEvent)) {
             call_user_func_array($this->afterShutdownEvent, [$this]);
        }
                    
    }
}