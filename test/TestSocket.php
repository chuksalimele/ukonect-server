<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/realtime/chat/Chat.php';

use Ratchet\Server\IoServer;
use React\Socket\Server;
use Realtime\Chat\Chat;
use App\Util\Constants;

use React\ZMQ\Context;

$loop = React\EventLoop\Factory::create();
$pusher = new Pusher;

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
$pull->on('message', array($pusher, 'onBlogEntry'));



$clientContext = new ZMQContext();
$socket = $clientContext->getSocket(ZMQ::SOCKET_PUSH, 'null', function($sock, $persist_id){
    echo 'persisted id='.$persist_id;
});
$socket->connect("tcp://localhost:5555");
$socket->send('chuks alimele');
class Pusher{
    
    public function onBlogEntry($param) {
        echo $param;
    }
}

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

echo 'conected to redis.';

$chat = new Chat($redis);

$webSock = new Server(8081, $loop); // Binding to 0.0.0.0 means remotes can connect

$server = new IoServer($chat,$webSock);

//new Ratchet\Server\IoServer(
echo 'running...';
$loop->run();