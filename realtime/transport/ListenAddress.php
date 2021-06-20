<?php
namespace Realtime\Transport;

class ListenAddress{
    
    private $host;
    private $port;
    /**
     * Constructor
     */
    public function __construct(string $host, int $port) {
        
        $this->host = $host;
        $this->port = $port;
        
    }
    
    public function getHost(){
        return $this->host;
    }
    
    public function getPort(){
        return $this->port;
    }
}
