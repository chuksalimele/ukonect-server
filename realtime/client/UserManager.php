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
use MongoDB\BSON\Regex;

class UserManager extends AbstractManager {
	
	private $sessionIdentityMap = [];
	
    /**
     * Constructor
     */
    public function __construct($db, $realm = 'user') {
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
	
	public function callUserSessionIdentity($args){
		$user_id = $args[0];
		$session_id = $args[1];
		$this->sessionIdentityMap[$session_id] = $user_id;
		
        $updated =  $this->handleOnlineStatus($user_id, true);
		return $this->serialized($updated);
	}

	public function onConnectionOpened($args){
		$this->sessionIdentityMap[$args->session->getSessionId()] = null;
	}

	public function onConnectionClosed($args){
		
		$session_id = $args->session->getSessionId();
		
		if(!isset($this->sessionIdentityMap[$session_id])){
			return;
		}
		
		$user_id = $this->sessionIdentityMap[$session_id];
		
		unset($this->sessionIdentityMap[$args->session->getSessionId()]);
		
		$this->handleOnlineStatus($user_id, false);
	}

	private function handleOnlineStatus($user_id, $online){
		
		$last_seen = Util::millitime();
		
        $updateResult = $this->db->users->updateOne(
                ['user_id' => $user_id],
				['$set' => [
				'online' => $online,
				'last_seen' => $last_seen
				]]
        );
		
		$internal_cleint_session_id = $this->session->getSessionId();
		
		$topic = "com.ukonectinfo.backend.user.online.status.$user_id";
		
		$this->session->publish($topic, [$online, $last_seen], [], [
                    "acknowledge" => true,
                    'exclude' => [$internal_cleint_session_id],
                        /* 'eligible' => $sessionIds */                        ]
                )
                ->then(function () use($gjrTopic) {
                    Logger::debug($this, "Publish Acknowledged - $topic\n");
                }, function ($error) use($gjrTopic) {
                    // publish failed
                    Logger::debug($this, "Publish Error {$error}\n - $topic\n");
                }
        );
		
        return $updateResult->getMatchedCount() > 0 || $updateResult->getModifiedCount() > 0;        
	}
	
    public function handlePublishMessage(MessageEvent $event) {
        $session = $event->session;
        $message = $event->message;

        $json = $message->jsonSerialize();
        $topic = $json[3];
        $args = $json[4];


        if (Str::startsWith($topic, 'com.ukonectinfo.user.profile.update.')) {
            $user_profile = $args[0];

            $this->db->users->updateOne(
                    ['user_id' => $user_profile->user_id], ['$set' => $user_profile]
            );
        } else if (Str::startsWith($topic, 'com.ukonectinfo.location.change.')) {
            $location = $args[0];
            $user_id = $location->user_id;

            $this->db->users->updateOne(
                    ['user_id' => $user_id], ['$set' => ['location' => $location]]
            );
        }
    }

    public function callSearchUsers($args) {
        $criteria = $args[0];
		$skip = isset($args[1])? $args[1]: 0;//offset
        $limit = isset($args[2])? $args[2]: -1;

		if($limit >=0){
			if($skip >= 0){
			}else{
				$skip = 0;
			}				
		}else{
			$skip = 0;
			$limit = -1;	
		}

		
		$regex_criteria = new Regex(preg_quote($criteria), 'i');
        
        $cursor = $this->db->users->find(
                ['$or' => [
                        ['first_name' => $regex_criteria],
                        ['last_name' => $regex_criteria],
                        ['user_id' => $regex_criteria],
                        ['mobile_phone_no' => $regex_criteria],
                        ['work_phone_no' => $regex_criteria]
                    ],
                ],
				[
                'projection' => ['_id' => 0],
				'skip' => $skip,
				'limit' => $limit
				]
        );
		
        $users = array();
        foreach ($cursor as $user) {
            $users[] = $user;
        }
		
        return $this->serialized($users);
    }

    public function callUserContactsUpdate($args) {
        $user_id = $args[0];
        $contacts = $args[1]; // array of contacts phones

        $updateResult = $this->db->users->updateOne(
                ['user_id' => $user_id], ['$set' => ['contacts' => $contacts]]
        );

        $updated = $updateResult->getMatchedCount() > 0 || $updateResult->getModifiedCount() > 0;
        return $this->serialized($updated);
    }

    public function callUserContactsFetch($args) {
        $contacts = $args[0]; // array of contacts phones
        //Note: the contacts phone number must be the full quanlified 
        //phone number ie [code][National Number] e.g [234][7032453745]
        //because that is what forms the user id of this app. So the 
        //client side ensure that the phone number sent here are valid

        $cursor = $this->db->users->find(
				['user_id' => ['$in' => $contacts]],
                ['projection' => ['_id' => 0]]);

        $users = array();
        foreach ($cursor as $user) {
            $users[] = $user;
        }

        return $this->serialized($users);
    }

    public function callGetLastSeen($args) {

        $user_id = $args[0];

        $found = $this->db->users->findOne(
                ['user_id' => $user_id],
                ['projection' => ['last_seen' => 1]]
        );

        if (!$found) {
            return $this->error(Constants::NOT_FOUND);
        }

        return $this->serialized($found->last_seen);
    }

    public function callUpdateLastSeen($args) {

        $user_id = $args[0];

        $updateResult = $this->db->users->updateOne(
                ['user_id' => $user_id], ['$set' => ['last_seen' => Util::millitime()]]
        );

        $updated = $updateResult->getMatchedCount() > 0 || $updateResult->getModifiedCount() > 0;
        return $this->serialized($updated);
    }

    public function callUpdateProfile($args) {

        $user = $args[0];
		$subscribed_topic = $args[1];
		
        if (!empty($user->photo_base64)) {
            $user->photo_url = Util::uploadBase64($user->photo_base64,
                            Constants::DEFAULT_UPLOAD_USER_PROFILE_DIR,
                            $user->user_id,
                            $user->photo_file_extension);
        }

		$user->updated_at = Util::millitime();
		
        $user = $this->updateFields($this->db->users, $user, 'user_id', array('photo_base64'), true);
        
        $this->session->publish($subscribed_topic, [$user], [], ["acknowledge" => true])
                ->then(function () use($subscribed_topic) {
                    Logger::debug($this, "Publish Acknowledged - $subscribed_topic\n");
                }, function ($error) use($subscribed_topic) {
                    // publish failed
                    Logger::debug($this, "Publish Error {$error}\n - $subscribed_topic\n");
                }
        );

		
		
		return $this->serialized($user);
    }

	public static function getSubscribedEvents() {
        return [
            'connection_open' => ['onConnectionOpened', 10],
			'connection_close' => ['onConnectionClosed', 10],

		];
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
		$session->register('com.ukonectinfo.backend.user.session.identity', [$this, 'callUserSessionIdentity']);
        $session->register('com.ukonectinfo.backend.search.users', [$this, 'callSearchUsers']);
        $session->register('com.ukonectinfo.backend.user.contacts.update', [$this, 'callUserContactsUpdate']);
        $session->register('com.ukonectinfo.backend.user.contacts.fetch', [$this, 'callUserContactsFetch']);
        $session->register('com.ukonectinfo.backend.user.get.last.seen', [$this, 'callGetLastSeen']);
        $session->register('com.ukonectinfo.backend.user.update.last.seen', [$this, 'callUpdateLastSeen']);
        $session->register('com.ukonectinfo.backend.user.update.profile', [$this, 'callUpdateProfile']);
    }

}
