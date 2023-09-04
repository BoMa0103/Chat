<?php

namespace App\Services\Websocket\Handlers\Messages;

use App\Services\Websocket\Handlers\BaseHandler;
use Ratchet\ConnectionInterface;

class MarkUserChatAsOfflineHandler extends BaseHandler
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
                        'message' => 'mark_chat_as_offline',
                        'chat_id' => $chat->id,
                    ];

                    $client->send(json_encode($message));
                }
            }
        }
    }
}
