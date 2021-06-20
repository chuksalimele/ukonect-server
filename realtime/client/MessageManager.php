<?php

namespace Realtime\Client;

use Thruway\Message\Message;
use Thruway\Message\PublishMessage;
use Thruway\Event\MessageEvent;
use Thruway\Logging\Logger;
use Illuminate\Support\Str;
use App\Util\Constants;
use App\Util\Util;
use MongoDB\Operation\FindOneAndUpdate;

class MessageManager extends AbstractManager {

    /**
     * Constructor
     */
    public function __construct($db, $realm = 'chat') {
        parent::__construct($db, $realm);
    }

    public function initModule(\Thruway\Peer\RouterInterface $router, \React\EventLoop\LoopInterface $loop) {
        parent::initModule($router, $loop);
    }

    public function onSessionStart($session, $transport) {
        parent::onSessionStart($session, $transport);
    }

    public function onSessionJoin($args, $kwArgs, $options) {
        parent::onSessionJoin($args, $kwArgs, $options);
    }

    /**
     * This method can be called by remote clients using RPC
     * 
     * @param type $args
     * @return type
     */
    public function callFetchByTopics($args) {
		
		$topics = $args[0];
		
        $cursor = $this->db->messages->find(
				['topic' => ['$in' => $topics]],
                ['projection' => ['_id' => 0]]);

		var_dump($topics);
		
        $docs = array();
        foreach ($cursor as $doc) {
            $docs[] = $doc;
        }

		var_dump($docs);

        return $this->serialized($docs);
	   
    }


    public function handlePublishMessage(MessageEvent $event) {
        $session = $event->session;
        $message = $event->message;

        $json = $message->jsonSerialize();
        $topic = $json[3];
        $args = $json[4];
		$content = $args[0];
			
        if (!empty($content->photo_base64) && !empty($content->message_id)) {
            $content->media_url = Util::uploadBase64($content->media_base64,
                            Constants::DEFAULT_UPLOAD_MESSAGE_MEDIA_DIR,
                            $content->message_id,
                            $content->photo_file_extension);
        }
	
		
        if (Str::startsWith($topic, 'com.ukonectinfo.chat.message.')){
	
            $content->topic = $topic; //key used to fetch the chat messages for both private and group            
			$content->time = Util::millitime(); //set the message time on the message object

            $valid = Validator::validateChatMessage($content);
			
            if ($valid != true) {
                Logger::debug($this, is_string($valid) ? $valid : "rejected invalid content : " . $content);
                return;
            }
			
			$this->db->messages->insertOne($content);
			
        }else if (Str::startsWith($topic, 'com.ukonectinfo.group.chat.message.')){
	
            $content->topic = $topic; //key used to fetch the chat messages for both private and group            
			$content->time = Util::millitime(); //set the message time on the message object

            $valid = Validator::validateGroupChatMessage($content);
			
            if ($valid != true) {
                Logger::debug($this, is_string($valid) ? $valid : "rejected invalid content : " . $content);
                return;
            }
			
			$this->db->messages->insertOne($content);
			
        }else if (Str::startsWith($topic, 'com.ukonectinfo.post.message.')) {
	
            $content->topic = $topic; //key used to fetch the chat messages for both private and group            
			$content->time = Util::millitime(); //set the message time on the message object

            $valid = Validator::validatePostMessage($content);
			
            if ($valid != true) {
                Logger::debug($this, is_string($valid) ? $valid : "rejected invalid content : " . $content);
                return;
            }
			
			$this->db->messages->insertOne($content);
			
        }  else if (Str::startsWith($topic, 'com.ukonectinfo.chat.seen.') 
			|| Str::startsWith($topic, 'com.ukonectinfo.group.chat.seen.') 
			|| Str::startsWith($topic, 'com.ukonectinfo.post.seen.')) {            

            $this->db->messages->updateOne(
                    ['message_id' => $content], ['$set' => ['status' => Constants::MSG_STATUS_SEEN]]
            );
        } else if (Str::startsWith($topic, 'com.ukonectinfo.chat.read.') 
			|| Str::startsWith($topic, 'com.ukonectinfo.group.chat.read.') 
		    || Str::startsWith($topic, 'com.ukonectinfo.post.read.')) {

            $this->db->messages->updateOne(
                    ['message_id' => $content], ['$set' => ['status' => Constants::MSG_STATUS_READ]]
            );
        } else if (Str::startsWith($topic, 'com.ukonectinfo.chat.modified.')) {
			
            $valid = Validator::validateChatMessage($content);
            if ($valid != true) {
                Logger::debug($this, is_string($valid) ? $valid : "rejected invalid content : " . $content);
                return;
            }
			
			$action = ['$inc' => ['modified_count' => 1],
						'$set' => [
							'text' => $content->text,
							'modified_time' => Util::millitime()
						]
                    ];
			
			if(!empty($content->media_url)){
				$action['$set']['media_url'] = 	$content->media_url;
			}				
						
            $this->db->messages->updateOne(
                    ['message_id' => $content->message_id], $action
            );
			
        }  else if (Str::startsWith($topic, 'com.ukonectinfo.group.chat.modified.')) {
			
            $valid = Validator::validateGroupChatMessage($content);
            if ($valid != true) {
                Logger::debug($this, is_string($valid) ? $valid : "rejected invalid content : " . $content);
                return;
            }
						
            $action = ['$inc' => ['modified_count' => 1],
						'$set' => [
							'text' => $content->text,
							'modified_time' => Util::millitime()
						]
                    ];
			
			if(!empty($content->media_url)){
				$action['$set']['media_url'] = 	$content->media_url;
			}				
						
            $this->db->messages->updateOne(
                    ['message_id' => $content->message_id], $action
            );
			
        }  else if (Str::startsWith($topic, 'com.ukonectinfo.post.modified.')) {
			
            $valid = Validator::validatePostMessage($content);
            if ($valid != true) {
                Logger::debug($this, is_string($valid) ? $valid : "rejected invalid content : " . $content);
                return;
            }
						
            $action = ['$inc' => ['modified_count' => 1],
						'$set' => [
							'text' => $content->text,
							'modified_time' => Util::millitime()
						]
                    ];
			
			if(!empty($content->media_url)){
				$action['$set']['media_url'] = 	$content->media_url;
			}				
						
            $this->db->messages->updateOne(
                    ['message_id' => $content->message_id], $action
            );
			
        } else if (Str::startsWith($topic, 'com.ukonectinfo.chat.deleted.') 
			|| Str::startsWith($topic, 'com.ukonectinfo.group.chat.deleted.') 
		    || Str::startsWith($topic, 'com.ukonectinfo.post.deleted.')) {


            $this->db->messages->updateOne(
                    ['message_id' => $content], ['$set' => ['deleted' => true]]
            );
        }
    }

    /**
     *
     * @return array
     */
    public function getSubscribedRealmEvents() {
        return [
            'PublishMessageEvent' => ['handlePublishMessage', 10],
        ];
    }

    public function registerCalls($session) {
        $session->register('com.ukonectinfo.backend.fetch.by.topics', [$this, 'callFetchByTopics']);
    }


}
