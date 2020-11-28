<?php

namespace pekand\Chat;

class Validator
{
	private $errors = [];
	
	public function __construct()
	{
		
	}

	public function rules($rules = [], $params = []){
		$this->params = array_merge(
			[
				'matchall' => true,
			],
			$params
		);
		$this->rules = $rules;
		return $this;
	}
	
	public function getErrors(){
		return $this->errors;
	}
	
	public function isValid($data = []) {		
		$this->errors = [];
		
		foreach ($data as $key => $value) {
			if($this->params['matchall'] && $this->params['matchall'] && !isset($this->rules[$key])) {
				$this->errors[$key][] = 'missing item: '.$key;
				continue;
			}
			
			if(!isset($this->rules[$key])){
				continue;
			}
			
			if($value === null && isset($this->rules[$key]['allowNull'])) {

			    if($this->rules[$key]['allowNull'] === false) {
                    $this->errors[$key][] = 'value is null';
                }

			} else if($value === "" && isset($this->rules[$key]['allowEmpty'])) {

			    if($this->rules[$key]['allowEmpty'] === false) {
                    $this->errors[$key][] = 'value is empty';
                }

            } else {
				
				foreach ($this->rules[$key] as $rule => $ruleValue) {
					if($rule == 'inSet') {
                        if(!isset($ruleValue['values']) || !in_array($value, $ruleValue['values'])) {
                            $this->errors[$key][] = 'value is not from set of values';
                        }
					}
					
					if($rule == 'length' && isset($ruleValue['min'])) {
                        if(strlen($value)<$ruleValue['min']) {
                            $this->errors[$key][] = 'lenght is smaller then ' . $ruleValue['min'];
                        }
					}
					
					if($rule == 'length' && isset($ruleValue['max'])) {
                        if($ruleValue['max']<strlen($value)) {
                            $this->errors[$key][] = 'lenght is bigger then ' . $ruleValue['max'];
                        }
					}
					
					if($rule == 'interval' && isset($ruleValue['min'])) {
                        if(!is_numeric($value) || $value<$ruleValue['min']) {
                            $this->errors[$key][] = 'value is smaller then ' . $ruleValue['min'];
                        }
					}
					
					if($rule == 'interval' && isset($ruleValue['max'])) {
					    if(!is_numeric($value) || $ruleValue['max'] < $value) {
                            $this->errors[$key][] = 'value is bigger then ' . $ruleValue['max'];
                        }
					}

                    if($rule == 'type' && $ruleValue == 'uid') {
                        if(!preg_match('/^[0-9a-zA-Z]{32}$/', $value)) {
                            $this->errors[$key][] = 'value is not type of uid';
                        }
                    }

					if($rule == 'type' && $ruleValue == 'int') {
                        if(!is_int($value)) {
                            $this->errors[$key][] = 'value is not type of integer';
                        }
					}
					
					if($rule == 'type' && $ruleValue == 'float') {
                        if(!is_float($value)) {
                            $this->errors[$key][] = 'value is not type of float';
                        }
					}
					
					if($rule == 'type' && $ruleValue == 'numeric') {
                        if(!is_numeric($value)) {
                            $this->errors[$key][] = 'value is not type of numeric';
                        }
					}
					
					if($rule == 'type' && $ruleValue == 'string') {
                        if(!is_string($value)) {
                            $this->errors[$key][] = 'value is not type of string';
                        }
					}
					
					if($rule == 'type' && $ruleValue == 'message_type') {
					    if(!($value == "message" || $value == "info")){
                            $this->errors[$key][] = 'invalid message type';
                        }
					}

					if($rule == 'match') {
                        if(!preg_match($ruleValue, $value)) {
                            $this->errors[$key][] = 'value is not match regex';
                        }
					}
				}
			}
			
		}
		
		foreach ($this->rules as $key => $rule) {
			if(!isset($data[$key])) {
				$errors[$key][] = 'rules not found for item: '.$key;
				continue;
			}
		}
		
		if(count($this->errors)>0){
			return false;
		}
		
		return true;
	}
}

