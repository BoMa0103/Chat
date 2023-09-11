<?php

namespace App\Services\Websocket\Handlers\Messages;

use App\Services\Websocket\Handlers\BaseHandler;
use Ratchet\ConnectionInterface;

class RequireMessagesHistoryHandler extends BaseHandler
{
    public function handle(ConnectionInterface $from, $msg): void
    {
        $userId = $this->connectedUsersId  [$from->resourceId];
        $chatId = $this->getWebsocketService()->findChatIdByUserId($userId);

        if (!$chatId) {
            $this->showRequireSelectChatMessage($from);
            return;
        }

        $messages = $this->getMessagesService()->getMessagesByChatIdOffsetLimit($chatId, $msg->load_messages_count, $msg->default_messages_count_load);

        foreach ($messages as $message) {
            $data = [
                'message' => 'load_history',
                'value' => $message->value,
                'user' => $message->user,
                'time' => $message->created_at->format('H:i'),
                'read_status' => $message->read_status,
            ];
            $from->send(json_encode($data));
        }
    }

    private function showRequireSelectChatMessage(ConnectionInterface $from): void
    {
        $message = [
            'message' => 'require_select_chat',
        ];
        $from->send(json_encode($message));
    }
}
