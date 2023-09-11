<?php

namespace App\Services\Websocket\Handlers\Chats;

use App\Services\Websocket\Handlers\BaseHandler;
use App\Services\Websocket\Handlers\Messages\RequireMessagesHistoryHandler;
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

    private function getMarkUserChatAsOnlineHandler(): MarkUserChatAsOnlineHandler
    {
        return app(MarkUserChatAsOnlineHandler::class);
    }

    private function getLoadChatsHandler(): LoadChatsHandler
    {
        return app(LoadChatsHandler::class);
    }

    private function getRequireMessagesHistoryHandler(): RequireMessagesHistoryHandler
    {
        return app(RequireMessagesHistoryHandler::class);
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
        $this->getLoadChatsHandler()->handle($from);
        $this->clearHistory($from);

        foreach ($this->connectedUsersId as $key => $userId) {
            if ($userId == $msg->user_id) {

                foreach ($this->clients as $client) {
                    if ($client->resourceId == $key) {
                        $this->getLoadChatsHandler()->handle($client);
                        $this->getMarkUserChatAsOnlineHandler()->handle($client);
                        break;
                    }
                }

            }
        }
    }

    private function clearHistory(ConnectionInterface $from): void
    {
        $data = [
            'message' => 'clear_history',
        ];
        $from->send(json_encode($data));
    }
}
