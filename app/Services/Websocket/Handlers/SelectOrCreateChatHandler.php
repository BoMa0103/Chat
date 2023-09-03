<?php

namespace App\Services\Websocket\Handlers;

use Illuminate\Support\Facades\Log;
use PDOException;
use Ratchet\ConnectionInterface;

class SelectOrCreateChatHandler extends BaseHandler
{
    private const UNIQUE_COMBINATION_EXCEPTION_CODE = 45000;

    private function getSelectChatHandler(): SelectChatHandler
    {
        return app(SelectChatHandler::class);
    }

    public function handle(ConnectionInterface $from, $msg): void
    {
        $userId = $this->connectedUsersId [$from->resourceId];

        try {
            $chat = $this->getChatsService()->createFromArray([
                'user_id_first' => $userId,
                'user_id_second' => $msg->user_id,
            ]);
        } catch (PDOException $e) {

            if ($e->getCode() == self::UNIQUE_COMBINATION_EXCEPTION_CODE) {
                $chat = $this->getChatsService()->findChatBetweenTwoUsers($userId, $msg->user_id);

                $this->getSelectChatHandler()->handle($from, $chat->id);

                $message = [
                    'message' => 'select_chat',
                    'chat_id' => $chat->id,
                ];

                $from->send(json_encode($message));
            } else {
                Log::error($e);
            }

            return;
        }

        $this->getSelectChatHandler()->handle($from, $chat->id);

        $this->loadChats($from);

        foreach ($this->connectedUsersId as $key => $userId) {
            if ($userId == $msg->user_id) {

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $key) {
                        $this->loadChats($client);
                        $this->sendMarkUserChatAsOnline($client);
                        break;
                    }
                }

            }
        }
    }
}
