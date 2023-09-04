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
}
