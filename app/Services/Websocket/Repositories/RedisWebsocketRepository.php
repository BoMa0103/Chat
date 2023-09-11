<?php

namespace App\Services\Websocket\Repositories;

use Illuminate\Support\Facades\Redis;

class RedisWebsocketRepository implements WebsocketRepository
{
    const CHAT_ID_KEY = 'chat-id-';
    const STORE_TIME = 60;

    public function findChatIdByUserId(int $userId): ?int
    {
        return $this->get(self::CHAT_ID_KEY . $userId);
    }

    public function storeChatIdForUser(int $userId, int $chatId): int
    {
        $this->setex(self::CHAT_ID_KEY . $userId, $chatId);
        return $chatId;
    }

    public function expireChatIdForUser(int $userId): void
    {
       $this->expire(self::CHAT_ID_KEY . $userId);
    }

    private function get(string $key): ?int
    {
        return Redis::get($key);
    }

    private function setex(string $key, string $data)
    {
        Redis::setex($key, self::STORE_TIME, $data);
    }

    private function expire(string $key)
    {
        return Redis::expire($key, self::STORE_TIME);
    }
}
