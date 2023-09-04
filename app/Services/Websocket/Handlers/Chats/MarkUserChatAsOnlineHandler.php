<?php

namespace App\Services\Websocket\Handlers\Chats;

use App\Services\Websocket\Handlers\BaseHandler;
use Ratchet\ConnectionInterface;

class MarkUserChatAsOnlineHandler extends BaseHandler
{
    public function handle(ConnectionInterface $from): void
    {
        $userId = $this->connectedUsersId [$from->resourceId];

        $chats = $this->getUsersService()->find($userId)->chats()->get();

        foreach ($chats as $chat) {
            $userReceiverId = $chat->user_id_first == $userId ? $chat->user_id_second : $chat->user_id_first;
            foreach ($this->clients as $client) {
                $clientUserId = $this->connectedUsersId [$client->resourceId];

                if ($clientUserId == $userReceiverId) {

                    $message = [
                        'message' => 'mark_chat_as_online',
                        'chat_id' => $chat->id,
                    ];

                    $client->send(json_encode($message));
                    $from->send(json_encode($message));
                }
            }
        }
    }
}
