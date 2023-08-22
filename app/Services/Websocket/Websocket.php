<?php

namespace App\Services\Websocket;

use App\Events\StoreMessage;
use App\Models\Message;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use function Laravel\Prompts\select;
use function PHPUnit\Framework\isEmpty;

class Websocket implements MessageComponentInterface
{
    protected $clients;
    protected $users_cookie = [];

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
        $msg = preg_replace("/[\r\n]+/", "<br>", $msg);

        $msg = json_decode($msg);

        if($msg->message == 'new message') {
            $message = [
                'message' => 'message',
                'value' => $msg->value,
                'user' => $msg->user,
                'time' => $msg->time,
                ];

            foreach ($this->clients as $client) {
                $client->send(json_encode($message));
            }

            Message::create([
                'value' => $msg->value, 'user' => $msg->user, 'time' => $msg->time,
            ]);
        }

        if($msg->message == 'require_messages_history') {
            $messages = Message::select('value', 'user', 'time')->orderBy('id', 'desc')->get();

            foreach ($messages as $message) {
                $data = [
                    'message' => 'load_history',
                    'value' => $message->value,
                    'user' => $message->user,
                    'time' => $message->time,
                ];
                $from->send(json_encode($data));
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
