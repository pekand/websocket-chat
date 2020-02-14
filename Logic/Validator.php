<?php

namespace Logic;


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
			
			if($value === null) {
				if(!isset($this->rules[$key]['null']) || $this->rules[$key]['null'] === false){
					$this->errors[$key][] = 'value is null';
				}
			} else {
				
				foreach ($this->rules[$key] as $rule => $ruleValue) {
					if($rule == 'inSet' && (!isset($ruleValue['values']) || !in_array($value, $ruleValue['values']))) {
						$this->errors[$key][] = 'value is not from set of values';
					}
					
					if($rule == 'length' && isset($ruleValue['min']) && strlen($value)<$ruleValue['min']) {
						$this->errors[$key][] = 'lenght is smaller then '.$ruleParams['min'];
					}
					
					if($rule == 'length' && isset($ruleValue['max']) && $ruleValue['max']<strlen($value)) {
						$this->errors[$key][] = 'lenght is bigger then '.$ruleValue['max'];
					}
					
					if($rule == 'interval' && isset($ruleValue['min']) && (!is_numeric($value) || $value<$ruleValue['min'])) {
						$this->errors[$key][] = 'value is smaller then '.$ruleValue['min'];
					}
					
					if($rule == 'interval' && isset($ruleValue['max']) && (!is_numeric($value) || $ruleValue['max']<$value)) {
						$this->errors[$key][] = 'value is bigger then '.$ruleValue['max'];
					}
					
					if($rule == 'type' && isset($ruleValue['type']) && $ruleValue == 'int' && !is_int($value)) {
						$this->errors[$key][] = 'value is not type of integer';
					}
					
					if($rule == 'type' && isset($ruleValue['type']) && $ruleValue == 'float' && !is_float($value)) {
						$this->errors[$key][] = 'value is not type of float';
					}
					
					if($rule == 'type' && isset($ruleValue['type']) && $ruleValue == 'numeric' && !is_numeric($value)) {
						$this->errors[$key][] = 'value is not type of float';
					}
					
					if($rule == 'type' && isset($ruleValue['type']) && $ruleValue == 'string' && !is_string($value)) {
						$this->errors[$key][] = 'value is not type of float';
					}
					
					if($rule == 'match' && !preg_match($ruleValue, $value)) {
						$this->errors[$key][] = 'value is not match';
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

