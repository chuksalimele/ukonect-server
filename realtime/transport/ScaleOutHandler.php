<?php

namespace Realtime\Transport;

use Thruway\Module\RouterModuleInterface;
use Thruway\Peer\Client;
use Thruway\Peer\Router;
use Thruway\Peer\RouterInterface;
use Thruway\Logging\Logger;
use React\EventLoop\LoopInterface;
use Realtime\Microservices\Channel;
use Thruway\Message\Message;
use Thruway\Message\PublishMessage;
use Thruway\Message\CallMessage;
use Thruway\Message\YieldMessage;
use Thruway\Message\ResultMessage;
use Thruway\Message\InvocationMessage;
use Thruway\Message\CancelMessage;
use Thruway\Message\InterruptMessage;
use Thruway\Registration;
use Thruway\Common\Utils;
use Thruway\Message\EventMessage;
use Thruway\Serializer\JsonSerializer;
use Redis;

class ScaleOutHandler extends Client implements RouterModuleInterface, Channel {

    protected $router;
    private $redis;
    protected $session;
    private $sessionId;
    private $waitingYeilds;
    private $callInvocationTrack = [];
    private $serializer;

    /**
     * Constructor
     */
    public function __construct($realm, ListenAddress $redisAddress) {
        parent::__construct($realm);
        $this->redis = new Redis();
        $this->redis->connect($redisAddress->getHost(), $redisAddress->getPort());
        $this->serializer = new JsonSerializer();
    }

    /**
     * @param RouterInterface $router
     * @param LoopInterface $loop
     */
    public function initModule(RouterInterface $router, LoopInterface $loop) {
        $this->router = $router;

        $this->setLoop($loop);

        $this->router->addInternalClient($this);
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport) {
        $this->session = $session;
        $this->sessionId = $session->getSessionId();
    }

    /**
     * Handle on new session joined.
     * This is where session is initially created and client is connected to socket server
     *
     * @param array $args
     * @param array $kwArgs
     * @param array $options
     * @return void
     */
    public function onSessionJoin($args, $kwArgs, $options) {
        
    }

    /**
     * Handle on session left.
     *
     * @param array $args
     * @param array $kwArgs
     * @param array $options
     * @return void
     */
    public function onSessionLeave($args, $kwArgs, $options) {
        
    }

    /**
     * @return Router
     */
    public function getRouter() {
        return $this->router;
    }

    /**
     * If people don't want to implement this
     *
     * @inheritdoc
     */
    public static function getSubscribedEvents() {
        return [];
    }

    public function onChannel(string $channel, string $msg) {

        echo 'REDIS CHANNEL RECEIVED... ' . $msg . PHP_EOL;

        $msgObj = json_decode($msg);

        if ($msgObj->origin->internalClientSessionId == $this->sessionId) {

            return; // leave because you published it to redis
        }

        $realm_name = $msgObj->realm;
        $wampMessage = $msgObj->wampMessage;

        echo 'proceed...' . PHP_EOL;

        //get realm by realm name using RealmManger of the router
        $realm = $this->router->getRealmManager()->getRealm($realm_name);
        $wampMessage = $this->serializer->deserialize($wampMessage);

        switch ($channel) {
            case Channel::PUBLISH_MESSAGE :
                $this->handlePublishMessage($realm, $wampMessage);
                break;
            case Channel::CALL_MESSAGE :
                $this->handleCallMessage($realm, $wampMessage, $msgObj->origin);
                break;
            case Channel::YEILD_MESSAGE :
                $this->handleYieldMessage($realm, $wampMessage, $msgObj->origin);
                break;
            case Channel::CANCEL_MESSAGE ://call cancelled
                $this->handleCancelMessage($realm, $wampMessage);
                break;
        }
    }

    public function publishChannel(string $channel, string $msg, $session) {

        $realm_name = $session->getRealm()->getRealmName();

        $channelMsg = [
            'realm' => $realm_name,
            'wampMessage' => $msg,
            'origin' => [
                'internalClientSessionId' => $this->sessionId,
                'sessionId' => $session->getSessionId() //this sessionId will be used to track Caller when the callee replies with YieldMessage                 
            ]
        ];

        //Set Authentication details which may be required in Call messages
        if ($session->getAuthenticationDetails()) {

            //this If block is not yet tested - come back to check for correctness abeg o!!!

            $authenticationDetails = $session->getAuthenticationDetails();
            $auth_details = [
                'authid' => $authenticationDetails->getAuthId(),
                'authrole' => $authenticationDetails->getAuthRole(),
                'authroles' => $authenticationDetails->getAuthRoles(),
                'authmethod' => $authenticationDetails->getAuthMethod(),
            ];

            if ($authenticationDetails->getAuthExtra() !== null) {
                $auth_details['_thruway_authextra'] = $authenticationDetails->getAuthExtra();
            }

            $channelMsg['origin']['auth_details'] = $auth_details;
        }

        if ($channel == Channel::YEILD_MESSAGE) {
            $deserializedMessage = $session->getTransport()->getSerializer()->deserialize($msg);
            $requestId = $deserializedMessage->getRequestId();
            if (!isset($this->callInvocationTrack[$requestId])) {
                return; //no need since we did not save it we can do nothing!
            }

            $channelMsg['origin']['callInvocationTrack'] = $this->callInvocationTrack[$requestId];
            unset($this->callInvocationTrack[$requestId]); //release memory
        }


        $this->redis->publish($channel, json_encode($channelMsg));

        echo 'REDIS CHANNEL PUBLISH... ' . json_encode($channelMsg) . PHP_EOL;
    }

    /**
     * Handle published message. The message will be sent to the
     * subscriber of this topic
     * 
     * @param type $realm
     * @param type $msg
     * @return type
     */
    public function handlePublishMessage($realm, PublishMessage $msg) {

        echo 'ScaleOutHandler::handlePublishMessage' . PHP_EOL;

        //get subscribers through the broker

        if ($msg->getPublicationId() === null) {
            $msg->setPublicationId(Utils::getUniqueId());
        }

        $subscriptions = $realm->getBroker()->getSubscriptions();

        if (!$subscriptions) {
            return;
        }

        echo 'about to do ScaleOutHandler::handlePublishMessage foreach' . PHP_EOL;

        foreach ($subscriptions as $subscription) {

            $session = $subscription->getSession();
            $sessionId = $subscription->getSession()->getSessionId();
            $authroles = [];
            $authid = '';
            $authenticationDetails = $subscription->getSession()->getAuthenticationDetails();

            if ($authenticationDetails) {
                $authroles = $authenticationDetails->getAuthRoles();
                $authid = $authenticationDetails->getAuthId();
            }
            //send the message to the subscriber through his Session object

            if (!$msg->isExcluded($sessionId) && $msg->isWhiteListed($sessionId) && $msg->hasEligibleAuthrole($authroles) && $msg->hasEligibleAuthid($authid)
            ) {

                $eventMsg = EventMessage::createFromPublishMessage($msg, $subscription->getId());
                $session->sendMessage($eventMsg);
            }

            echo 'ScaleOutHandler::handlePublishMessage $session->sendMessage($msg)' . PHP_EOL;
        }
    }

    /**
     * Handle call of procedure registered by a Callee.
     * The message will be sent to the Callee to execute
     * the procedure
     * 
     * @param type $realm
     * @param type $msg
     * @return type
     */
    public function handleCallMessage($realm, CallMessage $msg, $origin) {


        echo 'ScaleOutHandler::handleCallMessage' . PHP_EOL;

        //Check if the Callee who registered the procedure
        //that the Caller has called exists in this cluster server 
        //get the Callee through the dealer
        $procedures = $realm->getDealer()->getProcedures();

        if (!isset($procedures[$msg->getProcedureName()])) {
            return; //procedure not found
        }

        $procedure = $procedures[$msg->getProcedureName()];

        $this->processCall($procedure, $msg, $origin);
        
    }

    /**
     * Handle result from a procedure called by a Caller.
     * The result will be transported to the Caller
     * 
     * @param type $realm
     * @param type $msg
     * @return type
     */
    public function handleYieldMessage($realm, YieldMessage $msg, $origin) {

        echo 'ScaleOutHandler::handleYieldMessage' . PHP_EOL;

        //most to this is taken from Thruway source code
        $details = new \stdClass();

        $yieldOptions = $msg->getOptions();
        //we are ignoring progress options - $yieldOptions->progress
        //it is difficult to keep track of that
        $track = $origin->callInvocationTrack;

        $callerSession = $this->router->getSessionBySessionId($track->callerSessionId);
        if (!$callerSession) {
            return;
        }

        $resultMessage = new ResultMessage(
                $track->callMessageRequestId, $details, $msg->getArguments(), $msg->getArgumentsKw()
        );

        $callerSession->sendMessage($resultMessage);
    }

    /**
     * Handle call cancelled by the caller
     * 
     * @param type $realm
     * @param type $msg
     * @return type
     */
    public function handleCancelMessage($realm, CancelMessage $msg) {

        echo 'ScaleOutHandler::handleCancelMessage' . PHP_EOL;
    }

    /**
     * Create InvocationMessage from CallMessage and Registration.
     * Best effort was made to use Thruway source code to achieve this
     * 
     * @param CallMessage $msg
     * @param type $registration
     * @param type $origin
     * @return type
     */
    private function createInvocationMessage(CallMessage $msg, $registration, $origin) {

        $invocationMessage = InvocationMessage::createMessageFrom($msg, $registration);

        $invocationMessage->setRequestId(Utils::getUniqueId());

        $details = [];

        if ($registration->getDiscloseCaller() === true && $origin->auth_details) {
            $details = [
                'caller' => $origin->sessionId,
                'authid' => $origin->auth_details->auth_id,
                'authrole' => $origin->auth_details->auth_role,
                'authroles' => $origin->auth_details->authh_roles,
                'authmethod' => $origin->auth_details->auth_method,
            ];

            if ($origin->auth_details->auth_extra != null) {
                $details['_thruway_authextra'] = $origin->auth_details->auth_extra;
            }
            
        }

        // TODO: check to see if callee supports progressive call
        $callOptions = $msg->getOptions();
        $isProgressive = false;
        if (is_object($callOptions) && isset($callOptions->receive_progress) && $callOptions->receive_progress) {
            $details = array_merge($details, ['receive_progress' => true]);
            $isProgressive = true;
        }

        // if nothing was added to details - change ot stdClass so it will serialize correctly
        if (count($details) === 0) {
            $details = new \stdClass();
        }
        $invocationMessage->setDetails($details);

        //$this->setIsProgressive($isProgressive);
        //$this->setInvocationMessage($invocationMessage);
        return $invocationMessage;
    }

    //-------------------------------------------------
    //--------------------------------------------------
    //--------------------------------------------------

    /**
     * Taken from Thruway source and slightly modified to achieve the same
     * sense.
     * 
     * See the method Procedure::processQueue
     *
     * @throws \Exception
     */
    public function processCall($procedure, $msg, $origin) {

        // find the best candidate

        $registration = NULL;
        if (!$procedure->isAllowMultipleRegistrations()) {//Single resgistration
            $registration = $this->getNextFirstRegistration($procedure); //just pick the first
        } else if (strcasecmp($procedure->getInvokeType(), Registration::FIRST_REGISTRATION) === 0) {
            $registration = $this->getNextFirstRegistration($procedure);//pick the first also
        } else if (strcasecmp($procedure->getInvokeType(), Registration::LAST_REGISTRATION) === 0) {
            $registration = $this->getNextLastRegistration($procedure);//pick the last
        } else if (strcasecmp($procedure->getInvokeType(), Registration::RANDOM_REGISTRATION) === 0) {
            $registration = $this->getNextRandomRegistration($procedure);//randomly pick any
        } else if (strcasecmp($procedure->getInvokeType(), Registration::ROUNDROBIN_REGISTRATION) === 0) {
            $registration = $this->getNextRoundRobinRegistration($procedure);//pick using roundrobin
        } else if (strcasecmp($procedure->getInvokeType(), Registration::THRUWAY_REGISTRATION) === 0) {
            $registration = $this->getNextThruwayRegistration($procedure);//thruway registration type 
        }

        if ($registration === NULL) {
            return;
        }


        //below is not directly from Thruway source code
        
        $calleeSession = $registration->getSession();

        //send the message to the Callee through his Session object

        $invocationMsg = $this->createInvocationMessage($msg, $registration, $origin);
        $calleeSession->sendMessage($invocationMsg);

        //now lets track this call invocation
        $this->callInvocationTrack[$invocationMsg->getRequestId()] = (object) [
                    'callerSessionId' => $origin->sessionId,
                    'calleeSessionId' => $calleeSession->getSessionId(),
                    'callMessageRequestId' => $msg->getRequestId(),
                    'invocationIme' => microtime(true), //this we be used to avoid memory leak but not yet implemented -  TODO, periodically check the collection to detect if it is missed e.g when the caller session is lost 
        ];
        
    }

    /**
     * Taken from Thruway source and slightly modified to achieve the same
     * sense.
     * 
     * See the method Procedure::getNextThruwayRegistration
     *
     * @throws \Exception
     */
    private function getNextThruwayRegistration($procedure) {
        $congestion = true;
        /* @var $bestRegistration \Thruway\Registration */
        $bestRegistration = $procedure->getRegistrations()[0];
        /* @var $registration \Thruway\Registration */
        foreach ($procedure->getRegistrations() as $registration) {
            if ($registration->getSession()->getPendingCallCount() == 0) {
                $bestRegistration = $registration;
                $congestion = false;
                break;
            }
            if ($registration->getSession()->getPendingCallCount() <
                    $bestRegistration->getSession()->getPendingCallCount()
            ) {
                $bestRegistration = $registration;
            }
        }
        if ($congestion) {
            // there is congestion
            $bestRegistration->getSession()->getRealm()->publishMeta('thruway.metaevent.procedure.congestion', [
                    ['name' => $procedure->getProcedureName()]]
            );
            return NULL;
        }
        return $bestRegistration;
    }

    /**
     * Taken from Thruway source and slightly modified to achieve the same
     * sense.
     * 
     * See the method Procedure::getNextRandomRegistration
     *
     * @throws \Exception
     */
    private function getNextRandomRegistration($procedure) {
        if (count($procedure->getRegistrations()) === 1) {
            //just return this so that we don't have to run mt_rand
            return $procedure->getRegistrations()[0];
        }
        //mt_rand is apparently faster than array_rand(which uses the libc generator)
        return $procedure->getRegistrations()[mt_rand(0, count($procedure->getRegistrations()) - 1)];
    }

    /**
     * Taken from Thruway source and slightly modified to achieve the same
     * sense.
     * 
     * See the method Procedure::getNextRoundRobinRegistration
     *
     * @throws \Exception
     */
    private function getNextRoundRobinRegistration($procedure) {
        /* @var $bestRegistration \Thruway\Registration */
        $bestRegistration = $procedure->getRegistrations()[0];
        /* @var $registration \Thruway\Registration */
        foreach ($procedure->getRegistrations() as $registration) {
            if ($registration->getStatistics()['lastCallStartedAt'] <
                    $bestRegistration->getStatistics()['lastCallStartedAt']) {
                $bestRegistration = $registration;
                break;
            }
        }
        return $bestRegistration;
    }

    /**
     * Taken from Thruway source and slightly modified to achieve the same
     * sense.
     * 
     * See the method Procedure::getNextFirstRegistration
     *
     * @throws \Exception
     */
    private function getNextFirstRegistration($procedure) {
        /* @var $bestRegistration \Thruway\Registration */
        $bestRegistration = $procedure->getRegistrations()[0];
        return $bestRegistration;
    }

    /**
     * Taken from Thruway source and slightly modified to achieve the same
     * sense.
     * 
     * See the method Procedure::getNextLastRegistration
     *
     * @throws \Exception
     */
    private function getNextLastRegistration($procedure) {
        /* @var $bestRegistration \Thruway\Registration */
        $bestRegistration = $procedure->getRegistrations()[count($procedure->getRegistrations()) - 1];
        return $bestRegistration;
    }

}
