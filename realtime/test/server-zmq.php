<?php

namespace Realtime\Microservices;
declare(ticks=100);
use Redis;
use ZMQContext;
use ZMQ;

require_once dirname(__DIR__) . '../../vendor/autoload.php';


$context = new ZMQContext();
//create a pusher socket -  this needful in case the other endpoint goes off the data
//can be saved until when it comes up and the save data is redeliver to it. This is a great
//feature where the endpoint never losses data met for it whether it goes off or not
$socket = $context->getSocket(ZMQ::SOCKET_PUB, 'channel_sock'); //using persistent_id
$socket->bind('tcp://127.0.0.1:2222'); // Binding to 127.0.0.1 means the only client that can connect is itself

$f = fopen( 'php://stdin', 'r' );

while( $line = fgets( $f ) ) {
  echo $line;
  $socket->send($line);
}

fclose( $f );
