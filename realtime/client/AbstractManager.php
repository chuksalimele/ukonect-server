<?php

namespace Realtime\Client;

use Thruway\Module\RouterModuleInterface;
use Thruway\Module\RealmModuleInterface;
use Thruway\Peer\Client;
use Thruway\Peer\Router;
use Thruway\Peer\RouterInterface;
use Thruway\Logging\Logger;
use React\EventLoop\LoopInterface;
use MongoDB\Operation\FindOneAndUpdate;

abstract class AbstractManager extends Client implements RouterModuleInterface, RealmModuleInterface {

	use CallResult;
	use DBCommonActions;

    protected $session;
    protected $router;
    protected $db; //mongodb collection name
    /**
     * Constructor
     */

    public function __construct($db, $realm) {
        parent::__construct($realm);
        $this->db = $db;
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

    public function onSessionStart($session, $transport) {
        $this->session = $session;
        $realms = $this->router->getRealmManager()->getRealms();

        foreach ($realms as $realm) {

            if ($realm->getRealmName() == $this->getRealm()) {
                $realm->addModule($this);
            }
            //IN FUTURE WE MAY CONSIDER ADDING THIS MODULE TO OTHER REALMS
        }

        //now that the session has started, setup the stuff
        $this->registerCalls($session);
    }


    abstract function registerCalls($session);
	
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
        $connectedClientSession = $this->router->getSessionBySessionId($sessionId);
        Logger::debug($this, 'Client ' . $sessionId . ' connected');
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

        Logger::debug($this, 'Client ' . $sessionId . ' left');

        // Below won't work because once this event is triggered, client session is already ended
        // and cleared from router. If you need to access closed session, you may need to implement
        // a cache service such as Redis to access data manually.
        //$connectedClientSession = $this->router->getSessionBySessionId($sessionId); 
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

    /**
     *
     * @return array
     */
    public function getSubscribedRealmEvents() {
        return [];
    }

}
