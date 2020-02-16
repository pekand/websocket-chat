<?php

namespace Logic;

// business logic
class Services
{
    static $chatsStorage = null;
    static $usersStorage = null;
    
    public static function init()
    {
        if (!file_exists(STORAGE)) {
            mkdir(STORAGE, 0777, true);
        }
        
        if (!file_exists(STORAGE.DIRECTORY_SEPARATOR.'log')) {
            mkdir(STORAGE.DIRECTORY_SEPARATOR.'log', 0777, true);
        }
        
        if (!file_exists(STORAGE.DIRECTORY_SEPARATOR.'users')) {
            mkdir(STORAGE.DIRECTORY_SEPARATOR.'users', 0777, true);
        }
        
        if (!file_exists(STORAGE.DIRECTORY_SEPARATOR.'tokens')) {
            mkdir(STORAGE.DIRECTORY_SEPARATOR.'tokens', 0777, true);
        }
        
        if (!file_exists(STORAGE.DIRECTORY_SEPARATOR.'chats')) {
            mkdir(STORAGE.DIRECTORY_SEPARATOR.'chats', 0777, true);
        }
    }
    
    public static function getChatStorage()
    {
        if (self::$chatsStorage == null){
            self::$chatsStorage = new ChatsStorage();
        }
        
        return self::$chatsStorage;
    }
    
    public static function getUsersStorage()
    {
        if (self::$usersStorage == null){
            self::$usersStorage = new UsersStorage(STORAGE, 'users');
        }
        
        return self::$usersStorage;
    }
}