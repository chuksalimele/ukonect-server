<?php

namespace Realtime\Transport;

use Ratchet\RFC6455\Messaging\Frame;
use React\EventLoop\LoopInterface;
use Thruway\Event\ConnectionCloseEvent;
use Thruway\Event\ConnectionOpenEvent;
use Thruway\Event\RouterStartEvent;
use Thruway\Event\RouterStopEvent;
use Thruway\Exception\DeserializationException;
use Thruway\Logging\Logger;
use Thruway\Message\HelloMessage;
use Thruway\Message\PublishMessage;
use Thruway\Message\CallMessage;
use Thruway\Message\YieldMessage;
use Thruway\Message\CancelMessage;
use Thruway\Serializer\JsonSerializer;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\WebSocket\WsServerInterface;
use React\Socket\Server as Reactor;
use Thruway\Session;
use Thruway\Transport\AbstractRouterTransportProvider;
use Thruway\Transport\RatchetTransport;
use Realtime\Microservices\Channel;

/**
 * Class RatchetTransportProvider modified to allow horizontal scaling of the WebSocket application
 *
 * @package Thruway\Transport
 */
class ScaleOutTransportProvider extends AbstractRouterTransportProvider implements MessageComponentInterface, WsServerInterface, Channel {

    /**
     * @var string
     */
    private $address;

    /**
     * @var string|int
     */
    private $port;

    /**
     * @var \Ratchet\Server\IoServer
     */
    private $server;

    /**
     * @var \SplObjectStorage
     */
    private $sessions;

    /**
     * @var WsServer
     */
    private $ws;

    /**
     * @var \ScaleOutHandler
     */
    private $scaleOutHandler;
    

    /**
     * Constructor
     *
     * @param string $address
     * @param string|int $port
     */
    public function __construct($scaleOutHandler, ListenAddress $wsAddress) {
        $this->port = $wsAddress->getPort();
        $this->address = $wsAddress->getHost();
        $this->sessions = new \SplObjectStorage();
        $this->ws = new WsServer($this);

        $this->scaleOutHandler = $scaleOutHandler;
    }

    /**
     * Interface stuff
     */

    /** @inheritdoc */
    public function getSubProtocols() {
        return ['wamp.2.json'];
    }

    /** @inheritdoc */
    public function onOpen(ConnectionInterface $conn) {
        Logger::debug($this, "ScaleOutTransportProvider::onOpen");

        $transport = new RatchetTransport($conn, $this->loop);

        // this will need to be a little more dynamic at some point
        $transport->setSerializer(new JsonSerializer());

        $transport->setTrusted($this->trusted);

        $session = $this->router->createNewSession($transport);

        $this->sessions->attach($conn, $session);

        $this->router->getEventDispatcher()->dispatch("connection_open", new ConnectionOpenEvent($session));
    }

    /** @inheritdoc */
    public function onClose(ConnectionInterface $conn) {
        /** @var Session $session */
        $session = $this->sessions[$conn];

        $this->sessions->detach($conn);

        $this->router->getEventDispatcher()->dispatch('connection_close', new ConnectionCloseEvent($session));

        unset($this->sessions[$conn]);

        Logger::info($this, "Ratchet has closed");
    }

    /** @inheritdoc */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        Logger::error($this, "onError...");
    }

    /** @inheritdoc */
    public function onMessage(ConnectionInterface $from, $msg) {
        Logger::debug($this, "onMessage: ({$msg})");
        /** @var Session $session */
        $session = $this->sessions[$from];

        try {
            $serializedMsg = $msg;
            //$this->router->onMessage($transport, $transport->getSerializer()->deserialize($msg));
            $deserializedMsg = $session->getTransport()->getSerializer()->deserialize($msg);

            if ($deserializedMsg instanceof HelloMessage) {

                $details = $deserializedMsg->getDetails();

                $details->transport = (object) $session->getTransport()->getTransportDetails();

                $deserializedMsg->setDetails($details);
            }

            Logger::debug($deserializedMsg, $serializedMsg);

            $this->dispatch($session, $deserializedMsg, $serializedMsg);
        } catch (DeserializationException $e) {
            Logger::alert($this, "Deserialization exception occurred.");
        } catch (\Exception $e) {
            Logger::alert($this, "Exception occurred during onMessage: " . $e->getMessage());
        }
    }

    private function dispatch($session, $deserializedMsg, $serializedMsg) {
        

        $channel = null;
        $foundLocally = true;
        $realm = $session->getRealm();

        if ($deserializedMsg instanceof PublishMessage) {
            $channel = Channel::PUBLISH_MESSAGE;
        } else if ($deserializedMsg instanceof CallMessage) {
            $channel = Channel::CALL_MESSAGE;

            //check if the procedures is registered in this domain
            //if not, then we will skip dispatching this message locally
            //so that an error message of 'no such procedure' is not sent
            //back to the Caller. We will publish the CallMessage through redis
            //to all the servers in the cluster and hopefully find the procedure in any.
            //If we allow the 'no such procedure' error returned back to the 
            //Caller then even if the procedure is found in the cluster the 
            //Caller client will reject the ResultMessage sent by the Callee
            //which is not what we expect!
            
            $procedures = $realm->getDealer()->getProcedures();
            $foundLocally = isset($procedures[$deserializedMsg->getProcedureName()]);
            
        } else if ($deserializedMsg instanceof YieldMessage) {
            $channel = Channel::YEILD_MESSAGE;
        }

        if ($foundLocally) {
            $session->dispatchMessage($deserializedMsg);
        }

        if ($channel) {
            $this->publishChannel($channel, $serializedMsg, $session);
        }
        
        
    }
    
    /**
     * Handle on pong
     *
     * @param \Ratchet\ConnectionInterface $from
     * @param Frame $frame
     */
    public function onPong(ConnectionInterface $from, Frame $frame) {
        $transport = $this->sessions[$from];

        if (method_exists($transport, 'onPong')) {
            $transport->onPong($frame);
        }
    }

    public function handleRouterStart(RouterStartEvent $event) {

        $socket = new Reactor('tcp://' . $this->address . ':' . $this->port, $this->loop);

        Logger::info($this, "Websocket listening on " . $this->address . ":" . $this->port);

        $this->server = new IoServer(new HttpServer($this->ws), $socket, $this->loop);
    }

    public function enableKeepAlive(LoopInterface $loop, $interval = 30) {
        $this->ws->enableKeepAlive($loop, $interval);
    }

    public function handleRouterStop(RouterStopEvent $event) {
        if ($this->server) {
            $this->server->socket->close();
        }

        foreach ($this->sessions as $k) {
            $this->sessions[$k]->shutdown();
        }
    }

    public static function getSubscribedEvents() {
        return [
            "router.start" => ["handleRouterStart", 10],
            "router.stop" => ["handleRouterStop", 10]
        ];
    }

    public function onChannel(string $channel, string $msg) {
        $this->scaleOutHandler->onChannel($channel, $msg);
    }

    public function publishChannel(string $channel, string $msg, $session) {
        $this->scaleOutHandler->publishChannel($channel, $msg, $session);
    }

}
