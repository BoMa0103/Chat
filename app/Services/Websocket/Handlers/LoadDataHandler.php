<?php

namespace App\Services\Websocket\Handlers;

use App\Services\Chats\ChatsService;
use App\Services\Messages\MessagesService;
use App\Services\Users\UsersService;
use App\Services\Websocket\WebsocketService;
use Ratchet\ConnectionInterface;

class LoadDataHandler
{
    private $clients;
    private $connectedUsersId;

    public function __construct()
    {
        $this->clients = $this->getWebsocketService()->getClients();
        $this->connectedUsersId = $this->getWebsocketService()->getConnectedUsersId();
    }

    private function getWebsocketService(): WebsocketService
    {
        return app(WebsocketService::class);
    }

    private function getUsersService(): UsersService
    {
        return app(UsersService::class);
    }

    private function getChatsService(): ChatsService
    {
        return app(ChatsService::class);
    }

    private function getMessagesService(): MessagesService
    {
        return app(MessagesService::class);
    }

    public function handle(ConnectionInterface $from)
    {
        $this->loadChats($from);

        $this->sendUsersOnlineCount();

        $this->sendUsersOnlineList();

        $this->sendMarkUserChatAsOnline($from);
    }

    private function loadChats(ConnectionInterface $from)
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
                $this->markMessagesAsRead($from, $chat->id);
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

    private function sendUsersOnlineCount()
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

    private function sendUsersOnlineList()
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

    private function sendMarkUserChatAsOnline(ConnectionInterface $from)
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

    private function userSelectedChatId(int $userId): ?int
    {
        return $this->getWebsocketService()->findChatIdByUserId($userId);
    }

    private function markMessagesAsRead(ConnectionInterface $from, int $chatId)
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

    private function showRequireSelectChatMessage(ConnectionInterface $from)
    {
        $message = [
            'message' => 'require_select_chat',
        ];
        $from->send(json_encode($message));
    }
}
