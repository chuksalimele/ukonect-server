<?php

namespace Realtime\Client;

use MongoDB\Operation\FindOneAndUpdate;

trait DBCommonActions
{
 
  
    public function updateFields($collection, $obj, string $key_field, $excluded=array(), bool $returnNewDocument=false){
        
        $setOp = ['$set' =>[]];
        foreach ($obj as $key => $value) {     
			if(in_array($key, $excluded)){
				continue;//we will not store the base64 in the mongo document for performance reasons especially read performance
			}
            $setOp['$set'][$key] = $value;
        }


        $result = $collection->findOneAndUpdate(
                [$key_field => $obj->$key_field], 
				$setOp,
				['projection' => ['_id' => 0], 'returnDocument' => $returnNewDocument? FindOneAndUpdate::RETURN_DOCUMENT_AFTER : FindOneAndUpdate::RETURN_DOCUMENT_BEFORE]
        );

        return $result;  
    }
   
    public function upsertFields($collection, $obj, string $key_field, $excluded=array(), bool $returnNewDocument=false){
        

        $setOp = ['$set' =>[]];
        foreach ($obj as $key => $value) {     
			if(in_array($key, $excluded)){
				continue;//we will not store the base64 in the mongo document for performance reasons especially read performance
			}
            $setOp['$set'][$key] = $value;
        }

		var_dump($setOp);

        $result = $collection->findOneAndUpdate(
                [$key_field => $obj->$key_field], 
				$setOp,                 
				['projection' => ['_id' => 0], 'upsert'=> true, 'returnDocument' => $returnNewDocument? FindOneAndUpdate::RETURN_DOCUMENT_AFTER : FindOneAndUpdate::RETURN_DOCUMENT_BEFORE]
        );

        return $result;  
    }
   
 
}
