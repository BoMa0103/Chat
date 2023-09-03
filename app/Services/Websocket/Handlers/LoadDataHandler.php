<?php

namespace App\Services\Websocket\Handlers;

use App\Services\Chats\ChatsService;
use App\Services\Messages\MessagesService;
use App\Services\Users\UsersService;
use App\Services\Websocket\WebsocketService;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

class LoadDataHandler extends BaseHandler
{
    public function handle(ConnectionInterface $from): void
    {
        $this->loadChats($from);

        $this->sendUsersOnlineCount();

        $this->sendUsersOnlineList();

        $this->sendMarkUserChatAsOnline($from);
    }

    private function sendUsersOnlineCount(): void
    {
        $this->clients = $this->getWebsocketService()->getClients();

        $message_online = [
            'message' => 'online_users_count',
            'value' => count(array_unique($this->connectedUsersId)),
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($message_online));
        }
    }

    private function sendUsersOnlineList(): void
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
}
