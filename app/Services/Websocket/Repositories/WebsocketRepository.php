<?php

namespace App\Services\Websocket\Repositories;

interface WebsocketRepository
{
    public function findChatIdByUserId(int $userId): ?int;
    public function getOnline(): int;
    public function storeChatIdForUser(int $userId, int $chatId): int;
    public function storeOnline(int $online): int;
}
