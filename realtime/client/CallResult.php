<?php

namespace Realtime\Client;

use MongoDB\Operation\FindOneAndUpdate;
use App\Util\Result;

trait CallResult
{
 
	function serialized($value){
		return (new Result)->serialized($value);
	}
	
	function error($value){
		return (new Result)->errorJson($value);
	}
	
	function debug($value){
		return (new Result)->debugJson($value);
	}
	
 
}