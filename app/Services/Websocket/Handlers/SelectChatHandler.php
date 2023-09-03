<?php

namespace App\Services\Websocket\Handlers;

use App\Services\Websocket\WebsocketService;
use Ratchet\ConnectionInterface;

class SelectChatHandler
{
    private $connectedUsersId;

    public function __construct()
    {
        $this->connectedUsersId = $this->getWebsocketService()->getConnectedUsersId();
    }

    private function getWebsocketService(): WebsocketService
    {
        return app(WebsocketService::class);
    }

    public function handle(ConnectionInterface $from, $msg)
    {
        $userId = $this->connectedUsersId [$from->resourceId];
        $chatId = $msg->chat_id;

        $this->getWebsocketService()->storeChatIdForUser($userId, $chatId);

        $message = [
            'message' => 'chat_selected',
        ];

        $from->send(json_encode($message));
    }
}
