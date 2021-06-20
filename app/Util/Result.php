<?php

namespace App\Util;

class Result {

    public $success = false;
    public $data = '';
    public $errMsg = '';
    public $debugMsg = '';
    public $stackTraceMsg = '';

    function error(string $err, string $value = null) {
        if($err != null){
            $this->errMsg = $err;
        }
        if($value != null){
            $this->data = $value;
        }
        $this->success = false;
        return $this;
    }

    function errorJson($err, $value = null) {
        return json_encode($this->errorArray($err, $value));
    }

    function errorArray($err, $value = null) {
        return (array)$this->error($err, $value);
    }

    function debugJson($ex, $value = null) {        
        return json_encode($this->debugArray($ex, $value));
    }

    function debugArray($ex, $value = null){
        if($ex != null){
            $this->debugMsg = $ex->getMessage();
            $this->stackTraceMsg = $ex->getTraceAsString();
        }
        if($value != null){
            $this->data = $value;
        }
        return (array) $this->error(Constants::SOMETHING_WENT_WRONG, $value);
    }
    
    function dataJson($send = ''){
        return json_encode($this->dataArray($send));
    }
    
    function dataArray($send = ''){
        $this->data = $send;
		$this->success = true;
        return (array)$this;
    }

	/**
	 * Serialize the data field before serializing the entire class object. 
	 * This  is particularly useful where the data field on the frontend is of variable object type.
	 * After the first deserialization the data field is thereafter deserialized to the expected object type	 
	 */
	function serialized($data){
		
		if(!is_string($data)){
			$data = json_encode($data);
		}
		$this->data = $data;
		$this->success = true;
		return $this->json();		
	}
    
    function json() {
        return json_encode($this);
    }
    function toArray() {        
        return (array)$this;
    }

}
