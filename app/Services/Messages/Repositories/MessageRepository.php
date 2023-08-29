<?php

namespace App\Services\Messages\Repositories;

use App\Models\Message;

interface MessageRepository
{
    public function find(int $id): ?Message;
    public function createFromArray(array $data): Message;
    public function getMessagesByChatIdOffsetLimit(int $chatId, int $offset, int $limit);
}
