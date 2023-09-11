<?php

namespace App\Services\Websocket\Handlers\Chats;

use App\Services\Websocket\Handlers\BaseHandler;
use App\Services\Websocket\Handlers\Messages\MarkMessagesAsReadHandler;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;

class SearchChatsHandler extends BaseHandler
{
    private function getMarkMessagesAsReadHandler(): MarkMessagesAsReadHandler
    {
        return app(MarkMessagesAsReadHandler::class);
    }

    private function getMarkUserChatAsOnlineHandler(): MarkUserChatAsOnlineHandler
    {
        return app(MarkUserChatAsOnlineHandler::class);
    }

    public function handle(ConnectionInterface $from, $msg): void
    {
        $userId = $this->connectedUsersId [$from->resourceId];

        $chatsInfo = $this->getChatsInfo($from, $userId);

        $currentChatId = $this->getWebsocketService()->findChatIdByUserId($userId);

        if (!$currentChatId) {
            $this->showRequireSelectChatMessage($from);
        }

        dump($chatsInfo);

        $selectedChats = [];

        foreach ($chatsInfo ['chat_names_list'] as $key => $chatName) {
            if (Str::startsWith(strtolower($chatName), strtolower($msg->value))) {
                $selectedChats [] = $chatsInfo ['chats']->find($key);
            }
        }

        dump($selectedChats);

        $message_chats = [
            'message' => 'load_chats',
            'value' => $selectedChats,
            'chat_names_list' => $chatsInfo ['chat_names_list'],
            'chats_last_message_list' => $chatsInfo ['chats_last_message_list'],
            'chats_unread_messages_count_list' => $chatsInfo ['chats_unread_messages_count_list'],
            'current_chat_id' => $currentChatId,
        ];

        $from->send(json_encode($message_chats));

        $this->getMarkUserChatAsOnlineHandler()->handle($from);
    }

    private function getChatsInfo(ConnectionInterface $from, int $userId): array
    {
        $chats = $this->getUsersService()->find($userId)->chats()->get();

        $chatsNameList = [];
        $chatsLastMessageList = [];
        $chatsUnreadMessagesCountList = [];

        foreach ($chats as $chat) {

            $chatsLastMessageList [$chat->id] = $this->getMessagesService()->getMessagesByChatIdOffsetLimit($chat->id, 0, 1)->first();

            if (!$chatsLastMessageList [$chat->id]) {
                $chatsLastMessageList [$chat->id] = '';
            } else {
                $chatsLastMessageList [$chat->id] = $chatsLastMessageList [$chat->id]->value;
            }

            if ($chat->user_id_first == $userId) {
                $chatsNameList [$chat->id] = $this->getUsersService()->find($chat->user_id_second)->name;
            } else {
                $chatsNameList [$chat->id] = $this->getUsersService()->find($chat->user_id_first)->name;
            }

            if ($this->userSelectedChatId($userId) == $chat->id) {
                $this->getMarkMessagesAsReadHandler()->handle($from, $chat->id);
            }

            $chatsUnreadMessagesCountList [$chat->id] = $this->getMessagesService()->getUnreadMessagesCount($chat->id, $userId);
        }

        return [
            'chats' => $chats,
            'chat_names_list' => $chatsNameList,
            'chats_last_message_list' => $chatsLastMessageList,
            'chats_unread_messages_count_list' => $chatsUnreadMessagesCountList
        ];
    }

    private function showRequireSelectChatMessage(ConnectionInterface $from): void
    {
        $message = [
            'message' => 'require_select_chat',
        ];
        $from->send(json_encode($message));
    }

    private function userSelectedChatId(int $userId): ?int
    {
        return $this->getWebsocketService()->findChatIdByUserId($userId);
    }
}
