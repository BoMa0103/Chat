<?php

namespace App\Services\Websocket;

use App\Services\Websocket\Handlers\Chats\MarkUserChatAsOnlineHandler;
use App\Services\Websocket\Handlers\Chats\SelectChatHandler;
use App\Services\Websocket\Handlers\Chats\SelectOrCreateChatHandler;
use App\Services\Websocket\Handlers\LoadDataHandler;
use App\Services\Websocket\Handlers\Messages\CreateMessageHandler;
use App\Services\Websocket\Handlers\Messages\MarkMessagesAsReadHandler;
use App\Services\Websocket\Handlers\Messages\MarkUserChatAsOfflineHandler;
use App\Services\Websocket\Handlers\Messages\RequireMessagesHistoryHandler;
use App\Services\Websocket\Handlers\Users\UpdateOnlineUsersDataHandler;
use App\Services\Websocket\Repositories\WebsocketRepository;
use App\Services\Websocket\Validators\MessageValidator;
use Exception;
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
        $msg = $this->getMessageValidator()->validate($msg);

        switch ($msg->message) {
            case 'connection_identify':
                $this->connectionIdentify($from, $msg);
                break;
            case 'new_message':
                $this->getCreateMessageHandler()->handle($from, $msg);
                break;
            case 'require_messages_history':
                $this->getRequireMessagesHistoryHandler()->handle($from, $msg);
                break;
            case 'load_data':
                $this->getLoadDataHandler()->handle($from);
                break;
            case 'select_or_create_new_chat':
                $this->getSelectOrCreateChatHandler()->handle($from, $msg);
                break;
            case 'select_chat':
                $this->getSelectChatHandler()->handle($from, $msg->chat_id);
                break;
            case 'mark_messages_as_read':
                $this->getMarkMessagesAsReadHandler()->handle($from, $msg->chat_id);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        self::$clients->detach($conn);

        $this->getMarkUserChatAsOfflineHandler()->handle($conn);

        unset(self::$connectedUsersId[$conn->resourceId]);

        $this->getUpdateOnlineUsersDataHandler()->handle();

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    private function getMessageValidator(): MessageValidator
    {
        return app(MessageValidator::class);
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

    private function getMarkUserChatAsOfflineHandler(): MarkUserChatAsOfflineHandler
    {
        return app(MarkUserChatAsOfflineHandler::class);
    }

    private function getUpdateOnlineUsersDataHandler(): UpdateOnlineUsersDataHandler
    {
        return app(UpdateOnlineUsersDataHandler::class);
    }

    private function getWebsocketRepository(): WebsocketRepository
    {
        return app(WebsocketRepository::class);
    }

    private function connectionIdentify(ConnectionInterface $from, $msg)
    {
        self::$connectedUsersId [$from->resourceId] = $msg->user_id;
    }
}
