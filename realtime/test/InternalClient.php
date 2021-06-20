<?php

use Thruway\Module\RouterModuleInterface;
use Thruway\Peer\Client;
use Thruway\Peer\Router;
use Thruway\Peer\RouterInterface;
use Thruway\Logging\Logger;
use React\EventLoop\LoopInterface;

class InternalClient extends Client implements RouterModuleInterface
{
    protected $_router;

    /**
     * Contructor
     */
    public function __construct()
    {
        parent::__construct("realm1");
    }

    /**
     * @param RouterInterface $router
     * @param LoopInterface $loop
     */
    public function initModule(RouterInterface $router, LoopInterface $loop)
    {
        $this->_router = $router;

        $this->setLoop($loop);

        $this->_router->addInternalClient($this);
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        // TODO: now that the session has started, setup the stuff

        echo "--------------- Hello from InternalClient ------------\n";
        $session->register('com.example.getphpversion', [$this, 'getPhpVersion']);

        $session->subscribe('wamp.metaevent.session.on_join',  [$this, 'onSessionJoin']);
        $session->subscribe('wamp.metaevent.session.on_leave', [$this, 'onSessionLeave']);
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
        $sessionId = $args && $args[0] ? $args[0]->session : 0;
        $connectedClientSession = $this->_router->getSessionBySessionId($sessionId);
        Logger::debug($this, 'Client '. $sessionId. ' connected');
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

        $sessionId = $args && $args[0] ? $args[0]->session : 0;

        Logger::debug($this, 'Client '. $sessionId. ' left');

        // Below won't work because once this event is triggered, client session is already ended
        // and cleared from router. If you need to access closed session, you may need to implement
        // a cache service such as Redis to access data manually.
        //$connectedClientSession = $this->_router->getSessionBySessionId($sessionId); 
    }

    /**
     * RPC Call messages
     * These methods will run internally when it is called from another client. 
     */
    public function getPhpVersion() {

        // You can emit or broadcast another message in this case
        $this->emitMessage('com.example.commonTopic', 'phpVersion', array('msg'=> phpVersion()));

        $this->broadcastMessage('com.example.anotherTopic', 'phpVersionRequested', array('msg'=> phpVersion()));

        // and return result of your rpc call back to requester
        return [phpversion()];
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->_router;
    }


    /**
     * @param $topic
     * @param $eventName
     * @param $msg
     * @param null $exclude
     */
    protected function broadcastMessage($topic, $eventName, $msg)
    {
        $this->emitMessage($topic, $eventName, $msg, false);
    }

    /**
     * @param $topic
     * @param $eventName
     * @param $msg
     * @param null $exclude
     */
    protected function emitMessage($topic, $eventName, $msg, $exclude = true)
    {
        $this->session->publish($topic, array($eventName), array('data' => $msg), array('exclude_me' => $exclude));
    }

    /**
     * If people don't want to implement this
     *
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [];
    }
}