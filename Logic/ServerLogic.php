<?php

namespace Logic;

// business logic
class ServerLogic
{
    static $clients = [];
    static $operators = [];
    
    
    static $tokensPath = 'storage/tokens/tokens.json';
    static $tokens = [];
 
    static $chatsStorage = null;
    
    public static function init()
    {
        self::loadTokens();
    }
    
    public static function getChatStorage()
    {
        if (self::$chatsStorage == null){
            self::$chatsStorage = new ChatsStorage();
        }
        
        return self::$chatsStorage;
    }
    
    public static function isOperator($clientUid)
    {
        return isset(self::$operators[$clientUid]);
    }
    
    public static function isClient($clientUid)
    {
        return isset(self::$clients[$clientUid]);
    }
    
    public static function addOperator($clientUid)
    {
        self::$operators[$clientUid] = [];
        self::removeClient($clientUid);
    }
    
    public static function removeOperator($clientUid)
    {
        unset(self::$operators[$clientUid]);
        self::addClient($clientUid);
    }
    
    public static function addClient($clientUid)
    {
        self::$clients[$clientUid] = [];
    }
    
    public static function removeClient($clientUid)
    {
        unset(self::$clients[$clientUid]);
    }
    
    public static function getClients()
    {
       return self::$clients;
    }
    
    public static function getOperators()
    {
       return self::$operators;
    }
    
    public static function getToken()
    {
       $token = bin2hex(openssl_random_pseudo_bytes(16));
       self::$tokens[$token] = [
            'time' => microtime(true)
       ];
       
       self::saveTokens();
       
       return $token;
    }
    
    public static function isValidToken($token)
    {          
       return isset(self::$tokens[$token]);
    }
    
    public static function saveTokens()
    {
       file_put_contents(self::$tokensPath, json_encode(self::$tokens));       
    }
    
    public static function loadTokens()
    {
        if(!file_exists(self::$tokensPath)){
            return;
        }
        
       self::$tokens = json_decode(file_get_contents(self::$tokensPath), true);       
    }
}