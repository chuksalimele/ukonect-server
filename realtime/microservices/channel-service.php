<?php

namespace Realtime\Microservices;

use Redis;
use ZMQContext;
use ZMQ;

require_once dirname(__DIR__) . '../../vendor/autoload.php';
require_once dirname(__DIR__) . '/microservices/Channel.php';

use App\Util\Constants;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379, 0, //connection timeout 0 means unlimited or php default value
        NULL, //should be NULL if retry_interval is specified
        100//retry interval in milliseconds
);

$NO_TIMEOUT = -1; //This will prevent socket timeout error and prevent loss of publisded data. The default socket timeout in php is 60 seconds
$redis->setOption(Redis::OPT_READ_TIMEOUT, $NO_TIMEOUT); //The default socket timeout in php is 60 seconds. We don't want that!
//or
//ini_set(‘default_socket_timeout’, $NO_TIMEOUT); //The default socket timeout in php is 60 seconds. We don't want that!

echo 'conected to redis.' . PHP_EOL;

$context = new ZMQContext();
//create a pusher socket -  this needful in case the other endpoint goes off the data
//can be saved until when it comes up and the save data is redeliver to it. This is a great
//feature where the endpoint never losses data met for it whether it goes off or not
$socket = $context->getSocket(ZMQ::SOCKET_PUB, 'channel_sock'); //using persistent_id
$socket->bind('tcp://127.0.0.1:2222'); // Binding to 127.0.0.1 means the only client that can connect is itself

echo 'bind channel service' . PHP_EOL;

$channels = [
    Channel::PUBLISH_MESSAGE,
    Channel::CALL_MESSAGE,
    Channel::YEILD_MESSAGE,
    Channel::CANCEL_MESSAGE,//call cancelled by the caller
];

while (true) {//loop in case of other error apart from that cased by read timeout which we now prevent by setting $NO_TIMEOUT
    try {

        $redis->subscribe($channels, function ($redis, $channel, $msg) use($socket) {
            //echo 'sending...'.PHP_EOL;
            $socket->send(json_encode(array($channel, $msg))); //send multipart    
        });
    } catch (Exception $ex) {//should be other error apart from that cased by read timeout which we now prevent by setting $NO_TIMEOUT
        echo $ex->getMessage() . PHP_EOL;
    }
}

