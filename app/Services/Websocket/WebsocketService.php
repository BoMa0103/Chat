<?php

namespace App\Services\Websocket;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use PDOException;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

class WebsocketService implements MessageComponentInterface
{
    protected $clients;
    protected $uniqueUsersId = [];

    public function __construct()
    {
        $this->clients = new SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $msg = preg_replace("/[\r\n]+/", "<br>", $msg);

        $msg = json_decode($msg);

        if ($msg->message == 'new_message') {
            $user = User::findOrFail($msg->user_id);
            $chatId = Redis::get($this->uniqueUsersId [$from->resourceId]);

            $chat = Chat::findOrFail($chatId);

            $userReceiverId = $user->id == $chat->user_id_first ? $chat->user_id_second : $chat->user_id_first;

            $message = [
                'message' => 'message',
                'value' => $msg->value,
                'user' => $user,
                'time' => $msg->time,
            ];

            foreach ($this->clients as $client) {
                if ($this->uniqueUsersId [$client->resourceId] == $userReceiverId) {
                    if (Redis::get($this->uniqueUsersId [$client->resourceId]) == $chatId) {
                        $client->send(json_encode($message));
                    }
                }
            }

            $from->send(json_encode($message));

            Message::create([
                'value' => $msg->value,
                'user_id' => $msg->user_id,
                'chat_id' => Redis::get($this->uniqueUsersId [$from->resourceId]),
            ]);

        } else if ($msg->message == 'require_messages_history') {
            $chatId = Redis::get($this->uniqueUsersId [$from->resourceId]);

            $messages = Message::select('*')
                ->where('chat_id', '=', $chatId)
                ->orderBy('id', 'desc')
                ->offset($msg->load_messages_count)
                ->limit($msg->default_messages_count_load)
                ->get();

            foreach ($messages as $message) {
                $data = [
                    'message' => 'load_history',
                    'value' => $message->value,
                    'user' => $message->user,
                    'time' => $message->created_at->format('H:i'),
                ];
                $from->send(json_encode($data));
            }
        } else if ($msg->message == 'connection_identify') {
            $this->uniqueUsersId [$from->resourceId] = $msg->user_id;

            $this->checkUserHasSelectedChat($from);

            $this->loadChats($from);

            $this->sendUsersOnlineCount();

            $this->sendUsersOnlineList();
        } else if ($msg->message == 'new_chat') {
            try {
                Chat::create([
                    'user_id_first' => $this->uniqueUsersId [$from->resourceId],
                    'user_id_second' => $msg->user_id,
                ]);
                $this->loadChats($from);
                foreach ($this->uniqueUsersId as $key => $userId) {
                    if($userId == $msg->user_id) {
                        foreach ($this->clients as $client) {
                               if($client->resourceId == $key) {
                                   $this->loadChats($client);
                                   break;
                               }
                        }
                        break;
                    }
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '45000') {
                    $chat = Chat::select('*')
                        ->where(function ($query) use ($from, $msg){
                            $query->where('user_id_first', '=', $this->uniqueUsersId[$from->resourceId])
                                ->where('user_id_second', '=', $msg->user_id);
                        })
                        ->orWhere(function ($query) use ($from, $msg) {
                            $query->where('user_id_first', '=', $msg->user_id)
                                ->where('user_id_second', '=', $this->uniqueUsersId[$from->resourceId]);
                        })
                        ->first();
                    $this->selectChat($from, $chat->id);
                } else {
                    Log::error($e);
                }
            }
        } else if ($msg->message == 'select_chat') {
            $this->selectChat($from, $msg->chat_id);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        unset($this->uniqueUsersId[$conn->resourceId]);

        $this->sendUsersOnlineCount();

        $this->sendUsersOnlineList();

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    private function showRequireSelectChatMessage(ConnectionInterface $from) {
        $message = [
            'message' => 'require_select_chat',
        ];
        $from->send(json_encode($message));
    }

    private function checkUserHasSelectedChat(ConnectionInterface $from) {
        $chatId = Redis::get($this->uniqueUsersId [$from->resourceId]);

        if (!$chatId) {
            $this->showRequireSelectChatMessage($from);
        }
    }

    private function loadChats(ConnectionInterface $from)
    {
        $userId = $this->uniqueUsersId [$from->resourceId];

        $chats = Chat::select("*")
            ->where('user_id_first', '=', $userId)
            ->orWhere('user_id_second', '=', $userId)
            ->get();

        $chat_list = [];

        foreach ($chats as $chat) {
            if ($chat->user_id_first == $userId) {
                $chat_list [$chat->id] = User::select('name')
                    ->where('id', '=', $chat->user_id_second)
                    ->first()->name;
            } else {
                $chat_list [$chat->id] = User::select('name')
                    ->where('id', '=', $chat->user_id_first)
                    ->first()->name;
            }
        }

        $message_chats = [
            'message' => 'load_chats',
            'value' => $chats,
            'chat_names_list' => $chat_list,
        ];

        $from->send(json_encode($message_chats));
    }

    private function sendUsersOnlineCount()
    {
        $message_online = [
            'message' => 'online_users_count',
            'value' => count(array_unique($this->uniqueUsersId)),
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($message_online));
        }
    }

    private function sendUsersOnlineList()
    {
        $users = [];

        foreach (array_unique($this->uniqueUsersId) as $userId) {
            $users [] = User::findOrFail($userId);
        }

        $message_users = [
            'message' => 'online_users_list',
            'value' => $users,
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($message_users));
        }
    }

    private function selectChat(ConnectionInterface $from, int $chatId)
    {
        Redis::set($this->uniqueUsersId [$from->resourceId], $chatId);
        $message = [
            'message' => 'chat_selected',
        ];
        $from->send(json_encode($message));
    }
}
