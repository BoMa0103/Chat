<?php

namespace App\Services\Websocket;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

class Websocket implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        $message = [
            'message' => 'online',
            'value' => $this->clients->count(),
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($message));
        }

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $msg = json_decode($msg);

        if($msg->message == 'new message') {
            $message = [
                'message' => 'message',
                'value' => $msg->value,
                'user' => $msg->user,
                ];
            foreach ($this->clients as $client) {
                $client->send(json_encode($message));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        $message = [
            'message' => 'online',
            'value' => $this->clients->count(),
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($message));
        }

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
