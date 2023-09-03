<?php

namespace App\Services\Websocket\Handlers;

use App\Services\Chats\ChatsService;
use App\Services\Messages\MessagesService;
use App\Services\Websocket\WebsocketService;
use Ratchet\ConnectionInterface;

class MarkMessagesAsReadHandler
{
    private $clients;
    private $connectedUsersId;

    public function __construct()
    {
        $this->clients = $this->getWebsocketService()->getClients();
        $this->connectedUsersId = $this->getWebsocketService()->getConnectedUsersId();
    }

    private function getWebsocketService(): WebsocketService
    {
        return app(WebsocketService::class);
    }

    private function getChatsService(): ChatsService
    {
        return app(ChatsService::class);
    }

    private function getMessagesService(): MessagesService
    {
        return app(MessagesService::class);
    }

    public function handle(ConnectionInterface $from, $msg)
    {
        $this->connectedUsersId = $this->getWebsocketService()->getConnectedUsersId();
        $this->clients = $this->getWebsocketService()->getClients();

        $userId = $this->connectedUsersId [$from->resourceId];
        $chatId = $msg->chat_id;

        $this->getMessagesService()->setReadStatusMessages($chatId, $userId);

        $chat = $this->getChatsService()->find($chatId);

        $userReceiverId = $chat->user_id_first == $userId ? $chat->user_id_second : $chat->user_id_first;

        foreach ($this->connectedUsersId as $key => $userId) {
            if ($userId == $userReceiverId) {

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $key) {
                        $message = [
                            'message' => 'mark_messages_as_read',
                        ];
                        $client->send(json_encode($message));

                        break;
                    }
                }

            }
        }
    }
}
