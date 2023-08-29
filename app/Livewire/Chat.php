<?php

namespace App\Livewire;

use App\Services\Websocket\WebsocketService;
use Livewire\Component;

class Chat extends Component
{
    private function getWebsocketService(): WebsocketService
    {
        return app(WebsocketService::class);
    }

    public function getChats()
    {
        return $this->getWebsocketService()->loadChats(null, auth()->user()->id);
    }

    public function getUsers()
    {
        dump($this->getWebsocketService()->sendUsersOnlineList());
        return $this->getWebsocketService()->sendUsersOnlineList();

    }

    public function selectChat() {

    }

    public function render()
    {
        return view('livewire.chat', [
            "user_id" => auth()->user()->id,
            "chats" => $this->getChats(),
            "users" => $this->getUsers(),
        ]);
    }
}
