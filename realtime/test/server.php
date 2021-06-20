<?php

require  "../../vendor/autoload.php";
require  "../../realtime/transport/ScaleOutTransportProvider.php";
require __DIR__ . '/InternalClient.php';
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;
use Realtime\Transport\ScaleOutTransportProvider;

$port = 8081;
    
sprintf('Starting Sockets Service on Port [%s]', $port);

$router = new Router();

$router->registerModule(new ScaleOutTransportProvider("127.0.0.1", $port));   // use 0.0.0.0 if you want to expose outside world

// common realm ( realm1 )
$router->registerModule(
    new InternalClient()    // instantiate the Socket class now
);

// administration realm (administration)
// $router->registerModule(new \AdminClient());

$router->start();