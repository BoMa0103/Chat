<?php

namespace App\Services\Websocket\Handlers\Users;

use App\Services\Websocket\Handlers\BaseHandler;

class UpdateOnlineUsersDataHandler extends BaseHandler
{
    public function handle(): void
    {
        $this->sendUsersOnlineCount();

        $this->sendUsersOnlineList();
    }

    private function sendUsersOnlineCount(): void
    {
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
