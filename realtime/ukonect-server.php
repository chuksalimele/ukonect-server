<?php

require "boostrap.php"; //check added required files

use Thruway\Peer\Router;
use Realtime\Transport\ScaleOutTransportProvider;
use Realtime\Transport\ScaleOutHandler;
use Realtime\Transport\ListenAddress;
use React\ZMQ\Context;
use Realtime\Client\AuthManager;
use Realtime\Client\MessageManager;
use Realtime\Client\UserManager;
use Realtime\Client\GroupManager;
use Realtime\Client\LocationManager;
use Realtime\Client\CollectionInit;
use App\Util\Constants;
use MongoDB\Client as MongoClient;

$wsPort = isset($argv[1]) ? $argv[1] : 8080; // the command line argument is the websocket port to use

$router = new Router();
$wsAddress = new ListenAddress('0.0.0.0', $wsPort); // use 0.0.0.0 if you want to expose outside world
$redisAddress = new ListenAddress('127.0.0.1', 6379);
$scaleOutHandler = new ScaleOutHandler('scale_out', $redisAddress);

$transportProvider = new ScaleOutTransportProvider($scaleOutHandler, $wsAddress);
//$transportProvider = new Thruway\Transport\RatchetTransportProvider('0.0.0.0', $wsPort);

$mongoClient = new  MongoClient;
$db = $mongoClient->ukonectdb;

new CollectionInit($db);

$router->registerModules([
    $transportProvider,
    $scaleOutHandler,
    new AuthManager($db, Constants::DEFAULT_REALM),
    new MessageManager($db, Constants::DEFAULT_REALM),
    new UserManager($db,Constants::DEFAULT_REALM),
    new GroupManager($db, Constants::DEFAULT_REALM),
]);

// administration realm (administration)
// $router->registerModule(new \AdminClient());


$context = new Context($router->getLoop());
$sub = $context->getSocket(ZMQ::SOCKET_SUB, 'channel_sock'); //using persistent_id
$sub->connect("tcp://localhost:2222");
$sub->subscribe('');
//or
//$sub->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, '');
$onMessage = function($multipart) use($transportProvider) {
    echo 'received...' . PHP_EOL;
    $multipart = json_decode($multipart);
    $transportProvider->onChannel($multipart[0], $multipart[1]);
};

$sub->on('message', $onMessage);

echo PHP_EOL."Running...".PHP_EOL;
$router->start();
