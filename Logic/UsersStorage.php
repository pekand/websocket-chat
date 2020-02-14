<?php

namespace Logic;

class UsersStorage
{
	private $users = [];
	
	private $storage = null;
	
	public function __construct($storagePath, $subDir) {
           $this->storage = new Storage($storagePath, $subDir);
    }
    
    public function addUser($username, $password) {
    	$this->user[$username] = [
    		'password' => password_hash($password, PASSWORD_BCRYPT),	
    		'role' => 'operator',	
    		'token' => null,	
    	];
    	
    	$this->storage->setAndSave($username, $this->user[$username]);
    }
    
    public function getUser($username) {    	
    	return $this->storage->get($username);
    }

    public function isValidUser($username, $password) {    	
    	$user =  $this->getUser($username);
    	
    	if($user == null){
    		return false;
    	}
    	   
    	if(password_verify($password, $user['password'])){
    		return true;
    	}
    	
    	return false;
    }    
    
    public function isValidToken($username, $token) {    	
    	$user =  $this->getUser($username);
    	
    	if($user == null){
    		return false;
    	}
    	
    	if(password_verify($password, $user['password'])){
    		return true;
    	}
    	
    	return false;
    }
    
    public function getUsers() {    
        return $this->users;
    }
}