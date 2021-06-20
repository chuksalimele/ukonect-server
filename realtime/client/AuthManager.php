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

class AuthManager extends AbstractManager {

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

    public function handlePublishMessage(MessageEvent $event) {
        $session = $event->session;
        $message = $event->message;

        $json = $message->jsonSerialize();
        $topic = $json[3];
        $args = $json[4];
    }

    public function callSignupVerifyPhoneNumber($args) {

        $user = $this->upsertFields($this->db->users, $args[0], 'user_id', array(), true);
        return $this->serialized($user);
    }

    public function callConfirmPhoneNumber($args) {

        $user = $this->updateFields($this->db->users, $args[0], 'user_id', array(), true);
        return $this->serialized($user);
    }

    public function callSignupName($args) {

        $user = $this->updateFields($this->db->users, $args[0], 'user_id', array(), true);
        return $this->serialized($user);
    }

    public function callSignupProfilePhoto($args) {
        $user = $args[0];
        if (!empty($user->photo_base64)) {
            $user->photo_url = Util::uploadBase64($user->photo_base64,
                            Constants::DEFAULT_UPLOAD_USER_PROFILE_DIR,
                            $user->user_id,
                            $user->photo_file_extension);
        }
		
		$user->updated_at = Util::millitime();
		
        $user = $this->updateFields($this->db->users, $user, 'user_id', array('photo_base64'), true);
        return $this->serialized($user);
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

        $session->register('com.ukonectinfo.backend.signup.verify.phone.number', [$this, 'callSignupVerifyPhoneNumber']);
        $session->register('com.ukonectinfo.backend.signup.confirm.phone.number', [$this, 'callConfirmPhoneNumber']);
        $session->register('com.ukonectinfo.backend.signup.name', [$this, 'callSignupName']);
        $session->register('com.ukonectinfo.backend.signup.profile.photo', [$this, 'callSignupProfilePhoto']);
    }

}
