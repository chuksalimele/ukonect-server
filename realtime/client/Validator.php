<?php
namespace Realtime\Client;

use App\Util\Constants;
use App\Util\Util;
//use Illuminate\Support\Facades\Validator as LValidator; //DID NOT WORK FOR ME!!!

class Validator{
    

    static public function validateChatMessage($content) {

		
        $validator = Validator::make($content, [
                    'topic' => 'required|string', //will be set on the server
                    'time' => 'required|string',
                    'message_id' => 'required|string', //message id that identifies the message - must be a uuid create by the client
                    'from_user_id' => 'required|string',
                    'to_user_id' => 'required|string',
                        //'text' => 'required|string',
                        //'media_url' => 'required|string',
        ]);

        if ($validator != true) {
            return $validator;
        }

        return true;
    }
	
    static public function validateGroupChatMessage($content) {

		
        $validator = Validator::make($content, [
                    'topic' => 'required|string', //will be set on the server
                    'time' => 'required|string',
                    'message_id' => 'required|string', //message id that identifies the message - must be a uuid create by the client
                    'from_user_id' => 'required|string',
                    'group_name' => 'required|string',
                        //'text' => 'required|string',
                        //'media_url' => 'required|string',
        ]);

        if ($validator != true) {
            return $validator;
        }

        return true;
    }
	
    static public function validatePostMessage($content) {

		
        $validator = Validator::make($content, [
                    'topic' => 'required|string', //will be set on the server
                    'time' => 'required|string',
                    'message_id' => 'required|string', //message id that identifies the message - must be a uuid create by the client
                    'from_user_id' => 'required|string',
                        //'text' => 'required|string',
                        //'media_url' => 'required|string',
        ]);

        if ($validator != true) {
            return $validator;
        }

        return true;
    }
	
	static private function make($content, $options) {
		
		
		foreach($options as $optKey => $optValue){

				$checks = explode("|", $optValue);
				foreach($checks as $chkValue){
					if($chkValue == 'required' && !isset($content->$optKey)){
						return "$optKey is required";
					}
					if($chkValue == 'string' 
						 && !is_string($content->$optKey)
						 && !is_int($content->$optKey)
						 && !is_bool($content->$optKey)
						 && !is_float($content->$optKey)
						 && !is_double($content->$optKey)){
						return "$optKey must be a string";
					}
					
					if($chkValue == 'bool' 
						 && !is_bool($content->$optKey)){
						return "$optKey must be true or false";
					}
					
					if($chkValue == 'numeric' 
						 && !is_int($content->$optKey)
						 && !is_float($content->$optKey)
						 && !is_double($content->$optKey)){
						return "$optKey must be numeric";
					}
					
				}
			
		}
		
		return true;
	}

}

