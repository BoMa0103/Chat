<?php

namespace App\Services\Websocket\Handlers;

use Ratchet\ConnectionInterface;

class MarkMessagesAsReadHandler extends BaseHandler
{
    public function handle(ConnectionInterface $from, int $chatId): void
    {
        $userId = $this->connectedUsersId [$from->resourceId];

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
