<?php

namespace App\Services\Websocket\Handlers;

use App\Services\Chats\ChatsService;
use App\Services\Messages\MessagesService;
use App\Services\Users\UsersService;
use App\Services\Websocket\WebsocketService;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

abstract class BaseHandler
{
    protected SplObjectStorage $clients;
    protected array $connectedUsersId;

    public function __construct()
    {
        $this->clients = $this->getWebsocketService()->getClients();
        $this->connectedUsersId = $this->getWebsocketService()->getConnectedUsersId();
    }

    protected function getWebsocketService(): WebsocketService
    {
        return app(WebsocketService::class);
    }

    protected function getUsersService(): UsersService
    {
        return app(UsersService::class);
    }

    protected function getChatsService(): ChatsService
    {
        return app(ChatsService::class);
    }

    protected function getMessagesService(): MessagesService
    {
        return app(MessagesService::class);
    }

    private function getMarkMessagesAsReadHandler(): MarkMessagesAsReadHandler
    {
        return app(MarkMessagesAsReadHandler::class);
    }

    protected function sendMarkUserChatAsOnline(ConnectionInterface $from): void
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

    protected function loadChats(ConnectionInterface $from): void
    {
        $userId = $this->connectedUsersId [$from->resourceId];

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

        $currentChatId = $this->getWebsocketService()->findChatIdByUserId($userId);

        if (!$currentChatId) {
            $this->showRequireSelectChatMessage($from);
        }

        $message_chats = [
            'message' => 'load_chats',
            'value' => $chats,
            'chat_names_list' => $chatsNameList,
            'chats_last_message_list' => $chatsLastMessageList,
            'chats_unread_messages_count_list' => $chatsUnreadMessagesCountList,
            'current_chat_id' => $currentChatId,
        ];

        $from->send(json_encode($message_chats));
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
