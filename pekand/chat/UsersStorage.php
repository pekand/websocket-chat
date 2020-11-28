<?php

namespace pekand\Chat;

class UsersStorage
{
    private $users = [];
    
    private $storage = null;
    
    private $clients = [];
    private $operators = [];

    private $tokensPath = 'storage/tokens/tokens.json';
    private $tokens = [];
    
    public function __construct($storagePath, $subDir) {
           $this->storage = new Storage($storagePath, $subDir);
           $this->loadTokens();
    }
    
    public function getUsers() {    
        return $this->users;
    }
    
    public function getUser($username) {        
        return $this->storage->get($username);
    }
    
    public function addUser($username, $password) {
        $this->user[$username] = [
            'password' => password_hash($password, PASSWORD_BCRYPT),    
            'role' => 'operator',   
            'token' => null,    
            'tokenTime' => null,    
        ];
        
        $this->storage->setAndSave($username, $this->user[$username]);
    }
    
    public function isValidOperator($username, $password) {     
        $user =  $this->getUser($username);
        
        if($user == null){
            return false;
        }
        
        if(password_verify($password, $user['password'])){
            return true;
        }
        
        return false;
    }

    public function isOperator($clientUid)
    {
        return isset($this->operators[$clientUid]);
    }
    
    public function isClient($clientUid)
    {
        return isset($this->clients[$clientUid]);
    }
    
    public function addOperator($clientUid)
    {
        $this->operators[$clientUid] = [];
        $this->removeClient($clientUid);
    }
    
    public function removeOperator($clientUid)
    {
        unset($this->operators[$clientUid]);
        $this->addClient($clientUid);
    }
    
    public function addClient($clientUid)
    {
        $this->clients[$clientUid] = [];
    }
    
    public function removeClient($clientUid)
    {
        unset($this->clients[$clientUid]);
    }
    
    public function getClients()
    {
       return $this->clients;
    }
    
    public function getOperators()
    {
       return $this->operators;
    }
    
    public function getToken()
    {
       $token = bin2hex(openssl_random_pseudo_bytes(16));
       $this->tokens[$token] = [
            'time' => microtime(true)
       ];
       
       $this->saveTokens();
       
       return $token;
    }
    
    public function isValidToken($token)
    {          
       return isset($this->tokens[$token]);
    }
    
    public function saveTokens()
    {
       file_put_contents($this->tokensPath, json_encode($this->tokens));       
    }
    
    public function loadTokens()
    {
        if(!file_exists($this->tokensPath)){
            return;
        }
        
       $this->tokens = json_decode(file_get_contents($this->tokensPath), true);       
    }
    
    public function getInfo()
    {
        $userStorage = Services::getUsersStorage();
        $chatStorage = Services::getChatStorage();
        
        return [
            'memoryUsage' => round(memory_get_usage(true)/1048576,2)." megabytes",
            'clientsCount' => count($this->clients),
            'operatorsCount' => count($this->operators),
            'tokensCount' => count($this->tokens),
            'usersCount' => count($userStorage->getUsers()),
            'chatsCount' => count($chatStorage->getChats()),
        ];
    }
}
