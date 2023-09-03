<?php

namespace App\Services\Websocket;

use App\Services\Chats\ChatsService;
use App\Services\Messages\MessagesService;
use App\Services\Users\UsersService;
use App\Services\Websocket\Handlers\CreateMessageHandler;
use App\Services\Websocket\Handlers\MarkMessagesAsReadHandler;
use App\Services\Websocket\Handlers\SelectChatHandler;
use App\Services\Websocket\Handlers\SelectOrCreateChatHandler;
use App\Services\Websocket\Handlers\LoadDataHandler;
use App\Services\Websocket\Handlers\RequireMessagesHistoryHandler;
use App\Services\Websocket\Repositories\WebsocketRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use PDOException;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class WebsocketService implements MessageComponentInterface
{
    protected static $clients;
    protected static $connectedUsersId = [];

    public function __construct()
    {
        if (!self::$clients) {
            self::$clients = new SplObjectStorage;
        }
    }

    public function getClients(): SplObjectStorage
    {
        return self::$clients;
    }

    public function getConnectedUsersId(): array
    {
        return self::$connectedUsersId;
    }

    public function findChatIdByUserId(int $userId): ?int
    {
        return $this->getWebsocketRepository()->findChatIdByUserId($userId);
    }

    public function storeChatIdForUser(int $userId, int $chatId): int
    {
        return $this->getWebsocketRepository()->storeChatIdForUser($userId, $chatId);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        self::$clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $msg = $this->messageValidate($msg);

        switch ($msg->message) {
            case 'new_message':
                $this->getCreateMessageHandler()->handle($from, $msg);
                break;
            case 'require_messages_history':
                $this->getRequireMessagesHistoryHandler()->handle($from, $msg);
                break;
            case 'connection_identify':
                $this->connectionIdentify($from, $msg);
                break;
            case 'load_data':
                $this->getLoadDataHandler()->handle($from);
                break;
            case 'select_or_create_new_chat':
                $this->getSelectOrCreateChatHandler()->handle($from, $msg);
                break;
            case 'select_chat':
                $this->getSelectChatHandler()->handle($from, $msg);
                break;
            case 'mark_messages_as_read':
                $this->getMarkMessagesAsReadHandler()->handle($from, $msg);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        self::$clients->detach($conn);

        $this->sendMarkUserChatAsOffline($conn);

        unset(self::$connectedUsersId[$conn->resourceId]);

        $this->sendUsersOnlineCount();

        $this->sendUsersOnlineList();

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    private function getCreateMessageHandler(): CreateMessageHandler
    {
        return app(CreateMessageHandler::class);
    }

    private function getRequireMessagesHistoryHandler(): RequireMessagesHistoryHandler
    {
        return app(RequireMessagesHistoryHandler::class);
    }

    private function getLoadDataHandler(): LoadDataHandler
    {
        return app(LoadDataHandler::class);
    }

    private function getSelectOrCreateChatHandler(): SelectOrCreateChatHandler
    {
        return app(SelectOrCreateChatHandler::class);
    }

    private function getSelectChatHandler(): SelectChatHandler
    {
        return app(SelectChatHandler::class);
    }

    private function getMarkMessagesAsReadHandler(): MarkMessagesAsReadHandler
    {
        return app(MarkMessagesAsReadHandler::class);
    }

    private function getUsersService(): UsersService
    {
        return app(UsersService::class);
    }

    private function getWebsocketRepository(): WebsocketRepository
    {
        return app(WebsocketRepository::class);
    }

    private function messageValidate($msg)
    {
        $msg = preg_replace("/[\r\n]+/", "<br>", $msg);

        return json_decode($msg);
    }

    private function connectionIdentify(ConnectionInterface $from, $msg)
    {
        self::$connectedUsersId [$from->resourceId] = $msg->user_id;
    }

    private function sendUsersOnlineCount()
    {
        $message_online = [
            'message' => 'online_users_count',
            'value' => count(array_unique(self::$connectedUsersId)),
        ];

        foreach (self::$clients as $client) {
            $client->send(json_encode($message_online));
        }
    }

    private function sendUsersOnlineList()
    {
        $users = [];

        foreach (array_unique(self::$connectedUsersId) as $userId) {
            $users [] = $this->getUsersService()->find($userId);
        }

        $message_users = [
            'message' => 'online_users_list',
            'value' => $users,
        ];

        foreach (self::$clients as $client) {
            $client->send(json_encode($message_users));
        }
    }

    private function sendMarkUserChatAsOffline(ConnectionInterface $from)
    {
        $userId = self::$connectedUsersId [$from->resourceId];

        $chats = $this->getUsersService()->find($userId)->chats()->get();

        foreach ($chats as $chat) {
            $userReceiverId = $chat->user_id_first == $userId ? $chat->user_id_second : $chat->user_id_first;
            foreach (self::$clients as $client) {
                $clientUserId = self::$connectedUsersId [$client->resourceId];

                if ($clientUserId == $userReceiverId) {
                    $message = [
                        'message' => 'mark_chat_as_offline',
                        'chat_id' => $chat->id,
                    ];

                    $client->send(json_encode($message));
                }
            }
        }
    }
}
