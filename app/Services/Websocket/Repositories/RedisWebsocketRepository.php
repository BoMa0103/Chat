<?php

namespace App\Services\Websocket\Repositories;

use Illuminate\Support\Facades\Redis;

class RedisWebsocketRepository implements WebsocketRepository
{
    const CHAT_ID_KEY = 'chat-id-';

    public function findChatIdByUserId(int $userId): ?int
    {
        return $this->get(self::CHAT_ID_KEY . $userId);
    }

    public function storeChatIdForUser(int $userId, int $chatId): int
    {
        $this->set(self::CHAT_ID_KEY . $userId, $chatId);
        return $chatId;
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
