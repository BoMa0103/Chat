<?php

namespace App\Services\Messages;

use App\Models\Message;
use App\Services\Messages\Repositories\MessageRepository;

class MessagesService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
    )
    {
    }

    public function find(int $id): ?Message
    {
        return $this->messageRepository->find($id);
    }

    public function createFromArray(array $data): Message
    {
        return $this->messageRepository->createFromArray($data);
    }

    public function getMessagesByChatIdOffsetLimit(int $chatId, int $offset, int $limit)
    {
        return $this->messageRepository->getMessagesByChatIdOffsetLimit($chatId, $offset, $limit);
    }
}
