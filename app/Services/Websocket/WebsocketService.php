<?php

namespace App\Services\Websocket;

use App\Services\Chats\ChatsService;
use App\Services\Messages\MessagesService;
use App\Services\Users\UsersService;
use App\Services\Websocket\Repositories\WebsocketRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use PDOException;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class WebsocketService implements MessageComponentInterface
{
    protected $clients;
    protected $connectedUsersId = [];

    public function __construct()
    {
        $this->clients = new SplObjectStorage;
    }

    private function getUsersService(): UsersService
    {
        return app(UsersService::class);
    }

    private function getWebsocketRepository(): WebsocketRepository
    {
        return app(WebsocketRepository::class);
    }

    private function getChatsService(): ChatsService
    {
        return app(ChatsService::class);
    }

    private function getMessagesService(): MessagesService
    {
        return app(MessagesService::class);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $msg = $this->messageValidate($msg);

        if ($msg->message == 'new_message') {
            $this->newMessage($from, $msg);
        } else if ($msg->message == 'require_messages_history') {
            $this->requireMessagesHistory($from, $msg);
        } else if ($msg->message == 'connection_identify') {
            $this->connectionIdentify($from, $msg);
        } else if ($msg->message == 'new_chat') {
            $this->tryToCreateNewChatOrSelectExistent($from, $msg);
        } else if ($msg->message == 'select_chat') {
            $this->selectChat($from, $msg->chat_id);
        }
    }

    private function messageValidate($msg)
    {
        $msg = preg_replace("/[\r\n]+/", "<br>", $msg);

        return json_decode($msg);
    }

    private function newMessage(ConnectionInterface $from, $msg)
    {
        $userSender = $this->getUsersService()->find($msg->user_id);

        $userId = $this->connectedUsersId [$from->resourceId];
        $chatId = $this->getWebsocketRepository()->findChatIdByUserId($userId);

        $chat = $this->getChatsService()->find($chatId);

        $userReceiverId = $userSender->id == $chat->user_id_first ? $chat->user_id_second : $chat->user_id_first;

        $message = [
            'message' => 'message',
            'value' => $msg->value,
            'user' => $userSender,
            'time' => $msg->time,
        ];

        foreach ($this->clients as $client) {
            $clientUserId = $this->connectedUsersId [$client->resourceId];
            if ($clientUserId == $userReceiverId) {
                if ($this->getWebsocketRepository()->findChatIdByUserId($clientUserId) == $chatId) {
                    $client->send(json_encode($message));
                }
            }
        }

        $from->send(json_encode($message));

        $this->getMessagesService()->createFromArray([
            'value' => $msg->value,
            'user_id' => $msg->user_id,
            'chat_id' => $chatId,
        ]);
    }

    private function requireMessagesHistory(ConnectionInterface $from, $msg)
    {
        $userId = $this->connectedUsersId [$from->resourceId];
        $chatId = $this->getWebsocketRepository()->findChatIdByUserId($userId);

        $messages = $this->getMessagesService()->getMessagesByChatIdOffsetLimit($chatId, $msg->load_messages_count, $msg->default_messages_count_load);

        foreach ($messages as $message) {
            $data = [
                'message' => 'load_history',
                'value' => $message->value,
                'user' => $message->user,
                'time' => $message->created_at->format('H:i'),
            ];
            $from->send(json_encode($data));
        }
    }

    private function connectionIdentify(ConnectionInterface $from, $msg)
    {
        $this->connectedUsersId [$from->resourceId] = $msg->user_id;

        $this->loadChats($from);

        $this->sendUsersOnlineCount();

        $this->sendUsersOnlineList();
    }

    private function loadChats(ConnectionInterface $from)
    {
        $userId = $this->connectedUsersId [$from->resourceId];

        $chats = $this->getUsersService()->find($userId)->chats()->get();

        $chats_name_list = [];

        foreach ($chats as $chat) {
            if ($chat->user_id_first == $userId) {
                $chats_name_list [$chat->id] = $this->getUsersService()->find($chat->user_id_second)->name;
            } else {
                $chats_name_list [$chat->id] = $this->getUsersService()->find($chat->user_id_first)->name;

            }
        }

        $currentChatId = $this->getWebsocketRepository()->findChatIdByUserId($userId);

        if (!$currentChatId) {
            $this->showRequireSelectChatMessage($from);
        }

        $message_chats = [
            'message' => 'load_chats',
            'value' => $chats,
            'chat_names_list' => $chats_name_list,
            'current_chat_id' => $currentChatId,
        ];

        $from->send(json_encode($message_chats));
    }

    private function showRequireSelectChatMessage(ConnectionInterface $from)
    {
        $message = [
            'message' => 'require_select_chat',
        ];
        $from->send(json_encode($message));
    }

    private function sendUsersOnlineCount()
    {
        $message_online = [
            'message' => 'online_users_count',
            'value' => count(array_unique($this->connectedUsersId)),
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($message_online));
        }
    }

    private function sendUsersOnlineList()
    {
        $users = [];

        foreach (array_unique($this->connectedUsersId) as $userId) {
            $users [] = $this->getUsersService()->find($userId);
        }

        $message_users = [
            'message' => 'online_users_list',
            'value' => $users,
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($message_users));
        }
    }

    private function tryToCreateNewChatOrSelectExistent(ConnectionInterface $from, $msg)
    {
        $userSender = $this->connectedUsersId [$from->resourceId];

        try {
            $this->getChatsService()->createFromArray([
                'user_id_first' => $userSender,
                'user_id_second' => $msg->user_id,
            ]);
        } catch (PDOException $e) {

            if ($e->getCode() == '45000') {
                $chat = $this->getChatsService()->findChatBetweenTwoUsers($userSender, $msg->user_id);

                $this->selectChat($from, $chat->id);
            } else {
                Log::error($e);
            }

            return;
        }

        $this->loadChats($from);

        foreach ($this->connectedUsersId as $key => $userId) {
            if ($userId == $msg->user_id) {

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $key) {
                        $this->loadChats($client);
                        break;
                    }
                }

            }
        }

    }

    private function selectChat(ConnectionInterface $from, int $chatId)
    {
        $userId = $this->connectedUsersId [$from->resourceId];
        $this->getWebsocketRepository()->storeChatIdForUser($userId, $chatId);

        $message = [
            'message' => 'chat_selected',
        ];
        $from->send(json_encode($message));
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        unset($this->connectedUsersId[$conn->resourceId]);

        $this->sendUsersOnlineCount();

        $this->sendUsersOnlineList();

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
