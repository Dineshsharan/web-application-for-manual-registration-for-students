<?php

namespace Google\Cloud\Samples\Bookshelf\PubSub;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class HealthCheckListener implements MessageComponentInterface
{
    private $logger;

    public function __construct($logger = null)
    {
        $this->logger = $logger;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->log(sprintf('New connection: %s', $conn->resourceId));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // send the 200 OK health response and return
        $from->send("HTTP/1.1 200 OK\n");
        $from->close();
    }

    public function onClose(ConnectionInterface $conn)
    {
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->log(sprintf('An error has occurred: %s', $e->getMessage()));
        $conn->close();
    }

    private function log($message)
    {
        if ($this->logger) {
            $this->logger->log($message);
        }
    }
}
