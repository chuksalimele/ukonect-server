<?php
/*
$context = new ZMQContext();
$subscriber = new ZMQSocket($context, ZMQ::SOCKET_SUB);
$subscriber->connect("tcp://127.0.0.1:2222");
$subscriber->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, 'B');

while (true) {
    //  Read envelope with address
    $address = $subscriber->recv();
    //  Read message contents
    $contents = $subscriber->recv();
    printf ("[%s] %s%s", $address, $contents, PHP_EOL);
}*/

require '../../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$context = new React\ZMQ\Context($loop);

$sub = $context->getSocket(ZMQ::SOCKET_SUB);
$sub->connect('tcp://127.0.0.1:2222');
//$sub->subscribe('');
$sub->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, 'B');

$sub->on('message', function ($msg) {
    echo "Received: $msg\n";
});



$loop->run();