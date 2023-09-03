<?php

namespace App\Services\Websocket\Handlers;

use App\Services\Chats\ChatsService;
use App\Services\Messages\MessagesService;
use App\Services\Users\UsersService;
use App\Services\Websocket\WebsocketService;
use Ratchet\ConnectionInterface;

class CreateMessageHandler
{
    private $connectedUsersId;

    public function __construct()
    {
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

    public function handle(ConnectionInterface $from, $msg)
    {
        $userId = $this->connectedUsersId [$from->resourceId];
        $user = $this->getUsersService()->find($userId);

        $chatId = $this->getWebsocketService()->findChatIdByUserId($userId);
        $chat = $this->getChatsService()->find($chatId);

        $userReceiverId = $user->id == $chat->user_id_first ? $chat->user_id_second : $chat->user_id_first;

        $message = [
            'message' => 'message',
            'value' => $msg->value,
            'user' => $user,
            'time' => $msg->time,
            'read_status' => 0,
        ];

        $changeLastMessage = [
            'message' => 'change_last_message',
            'value' => $msg->value,
            'chat_id' => $chatId,
        ];

        $from->send(json_encode($message));
        $from->send(json_encode($changeLastMessage));

        $this->getMessagesService()->createFromArray([
            'value' => $msg->value,
            'user_id' => $userId,
            'chat_id' => $chatId,
        ]);

        $clients = $this->getWebsocketService()->getClients();

        foreach ($clients as $client) {
            $clientUserId = $this->connectedUsersId [$client->resourceId];

            if ($clientUserId == $userReceiverId) {
                if ($this->userSelectedChatId($clientUserId) == $chatId) {
                    $client->send(json_encode($message));
                    $client->send(json_encode($changeLastMessage));

                    $this->getMessagesService()->setReadStatusMessages($chatId, $clientUserId);

                    $messageRead = [
                        'message' => 'mark_messages_as_read',
                    ];
                    $from->send(json_encode($messageRead));
                } else {
                    $unreadMessagesCount = [
                        'message' => 'show_unread_messages_count',
                        'chat_id' => $chatId,
                        'unread_messages_count' => $this->getMessagesService()->getUnreadMessagesCount($chatId, $clientUserId),
                    ];
                    $client->send(json_encode($changeLastMessage));
                    $client->send(json_encode($unreadMessagesCount));
                }
            }
        }
    }

    private function userSelectedChatId(int $userId): ?int
    {
        return $this->getWebsocketService()->findChatIdByUserId($userId);
    }
}
