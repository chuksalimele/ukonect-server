<?php

namespace Realtime\Microservices;

interface Channel{
    
    
    const PUBLISH_MESSAGE = "PUBLISH_MESSAGE";
    const CALL_MESSAGE = "CALL_MESSAGE";
    const YEILD_MESSAGE = "YEILD_MESSAGE";
    const CANCEL_MESSAGE = "CANCEL_MESSAGE";

    function onChannel(string $channel, string $msg);
    
    function publishChannel(string $channel, string $msg, $session);
}

