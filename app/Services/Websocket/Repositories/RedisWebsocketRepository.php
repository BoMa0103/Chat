<?php

namespace App\Services\Websocket\Repositories;

use Illuminate\Support\Facades\Redis;

class RedisWebsocketRepository implements WebsocketRepository
{
    const CHAT_ONLINE_KEY = 'chat-online';

    public function findChatIdByUserId(int $userId): ?int
    {
        return $this->get($userId);
    }

    public function getOnline(): int
    {
        return $this->get(self::CHAT_ONLINE_KEY);
    }

    public function storeChatIdForUser(int $userId, int $chatId): int
    {
        $this->set($userId, $chatId);
        return $chatId;
    }

    public function storeOnline(int $online): int
    {
        $this->set(self::CHAT_ONLINE_KEY, $online);
    }

    private function get(string $key): ?int
    {
        return Redis::get($key);
    }

    private function set(string $key, string $data)
    {
        Redis::set($key, $data);
    }
}
