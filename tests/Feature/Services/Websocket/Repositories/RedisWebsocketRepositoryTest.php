<?php

namespace Tests\Feature\Services\Websocket\Repositories;

use App\Services\Websocket\Repositories\RedisWebsocketRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Nette\Utils\Random;
use Tests\TestCase;

class RedisWebsocketRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private function getRedisWebsocketRepository(): RedisWebsocketRepository
    {
        return app(RedisWebsocketRepository::class);
    }

    public function testFindChatIdByUserId(): void
    {
        $chatId = Random::generate(3, '0-9');
        $userId = Random::generate(3, '0-9');
        Redis::setex('chat-id-' . $userId, 60, $chatId);

        $findChatId = $this->getRedisWebsocketRepository()->findChatIdByUserId($userId);

        $this->assertEquals($chatId, $findChatId);
    }

    public function testStoreChatIdForUser(): void
    {
        $chatId = Random::generate(3, '0-9');
        $userId = Random::generate(3, '0-9');

        $this->getRedisWebsocketRepository()->storeChatIdForUser($userId, $chatId);

        $findChatId = Redis::get('chat-id-' . $userId);
        $this->assertEquals($chatId, $findChatId);
    }

    public function testExpireChatIdForUser(): void
    {
        $chatId = Random::generate(3, '0-9');
        $userId = Random::generate(3, '0-9');
        Redis::setex('chat-id-' . $userId, 60, $chatId);

        $this->getRedisWebsocketRepository()->expireChatIdForUser($userId);

        $ttl = Redis::ttl('chat-id-' . $userId);
        $this->assertEquals(60, $ttl);
    }
}
