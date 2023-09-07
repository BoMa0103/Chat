<?php

namespace App\Services\Websocket;

use App\Services\Websocket\Handlers\Chats\SearchChatsHandler;
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
use React\EventLoop\Loop;
use SplObjectStorage;

class WebsocketService implements MessageComponentInterface
{
    protected static $clients;
    protected static $connectedUsersId = [];
    protected static $loops = [];
    protected static $timers = [];

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

    public function expireChatIdForUser(int $userId): void
    {
        $this->getWebsocketRepository()->expireChatIdForUser($userId);
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        self::$clients->attach($conn);

        $this->startTimer($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $validatedMsg = $this->getMessageValidator()->validateJsonString($msg);

        switch ($validatedMsg->message) {
            case 'connection_identify':
                $this->connectionIdentify($from, $validatedMsg);
                break;
            case 'new_message':
                $validatedMsg = $this->getMessageValidator()->validateMessage($msg);
                $this->getCreateMessageHandler()->handle($from, $validatedMsg);
                break;
            case 'require_messages_history':
                $this->getRequireMessagesHistoryHandler()->handle($from, $validatedMsg);
                break;
            case 'load_data':
                $this->getLoadDataHandler()->handle($from);
                break;
            case 'select_or_create_new_chat':
                $this->getSelectOrCreateChatHandler()->handle($from, $validatedMsg);
                break;
            case 'select_chat':
                $this->getSelectChatHandler()->handle($from, $validatedMsg->chat_id);
                break;
            case 'mark_messages_as_read':
                $this->getMarkMessagesAsReadHandler()->handle($from, $validatedMsg->chat_id);
                break;
            case 'search_chats':
                $this->getSearchChatsHandler()->handle($from, $validatedMsg);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        self::$clients->detach($conn);

        $this->getMarkUserChatAsOfflineHandler()->handle($conn);

        $this->stopTimer($conn);

        unset(self::$connectedUsersId[$conn->resourceId]);

        $this->getUpdateOnlineUsersDataHandler()->handle();

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e): void
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

    private function getSearchChatsHandler(): SearchChatsHandler
    {
        return app(SearchChatsHandler::class);
    }

    private function connectionIdentify(ConnectionInterface $from, $msg): void
    {
        self::$connectedUsersId [$from->resourceId] = $msg->user_id;
    }

    private function startTimer(ConnectionInterface $conn): void
    {
        $loop = Loop::get();
        $resourceId = $conn->resourceId;

        $timer = $loop->addPeriodicTimer(50, function () use ($conn, $resourceId) {
            $this->getWebsocketRepository()->expireChatIdForUser(self::$connectedUsersId [$resourceId]);
        });

        self::$loops[$resourceId] = $loop;
        self::$timers[$resourceId] = $timer;
    }

    private function stopTimer(ConnectionInterface $conn): void
    {
        $resourceId = $conn->resourceId;
        if (isset(self::$loops[$resourceId])) {
            $loop = self::$loops[$resourceId];
            $loop->cancelTimer(self::$timers[$resourceId]);
            unset(self::$loops[$resourceId]);
            unset(self::$timers[$resourceId]);
        }
    }
}
