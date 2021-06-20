<?php
namespace Realtime\Client;

class CollectionInit{
    
    public function __construct($db) {
        
        //index users collection
          $indexes = [
              [ 'key' => [ 'user_id' => 1 ], 'unique' => true ],
              [ 'key' => [ 'first_name' => 1 ]],
              [ 'key' => [ 'last_name' => 1 ]],
              [ 'key' => [ 'mobile_phone_number' => 1 ] ],
              [ 'key' => [ 'work_phone_number' => 1 ]],
              [ 'key' => [ 'contacts' => 1 ]],
          ];
          
        $db->users->createIndexes($indexes);
        
        //index groups collection
        $indexes = [
                ['key' => ['name' => 1], 'unique' => true],
                ['key' => ['created_by' => 1]]
        ];

        $db->groups->createIndexes($indexes);
        
        //index messages collection
        $indexes = [
                ['key' => ['message_id' => 1], 'unique' => true],
                ['key' => ['topic' => 1]],
                ['key' => ['deleted' => 1]],
                ['key' => ['modified_count' => 1]],
        ];

        $db->messages->createIndexes($indexes);
    }
}

