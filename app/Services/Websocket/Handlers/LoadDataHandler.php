<?php

namespace App\Services\Websocket\Handlers;

use App\Services\Websocket\Handlers\Chats\LoadChatsHandler;
use App\Services\Websocket\Handlers\Chats\MarkUserChatAsOnlineHandler;
use App\Services\Websocket\Handlers\Users\UpdateOnlineUsersDataHandler;
use Ratchet\ConnectionInterface;

class LoadDataHandler extends BaseHandler
{
    private function getMarkUserChatAsOnlineHandler(): MarkUserChatAsOnlineHandler
    {
        return app(MarkUserChatAsOnlineHandler::class);
    }

    private function getUpdateOnlineUsersDataHandler(): UpdateOnlineUsersDataHandler
    {
        return app(UpdateOnlineUsersDataHandler::class);
    }

    private function getLoadChatsHandler(): LoadChatsHandler
    {
        return app(LoadChatsHandler::class);
    }

    public function handle(ConnectionInterface $from): void
    {
        $this->getLoadChatsHandler()->handle($from);

        $this->getMarkUserChatAsOnlineHandler()->handle($from);

        $this->getUpdateOnlineUsersDataHandler()->handle();
    }
}
