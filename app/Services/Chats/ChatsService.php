<?php

namespace App\Services\Chats;

use App\Models\Chat;
use App\Services\Chats\Repositories\ChatRepository;

class ChatsService
{
    public function __construct(
        private readonly ChatRepository $chatRepository,
    )
    {
    }

    public function find(int $id): ?Chat
    {
        return $this->chatRepository->find($id);
    }

    public function createFromArray(array $data): Chat
    {
        return $this->chatRepository->createFromArray($data);
    }

    public function findChatBetweenTwoUsers(int $userIdFirst, int $userIdSecond): ?Chat
    {
        return $this->chatRepository->findChatBetweenTwoUsers($userIdFirst, $userIdSecond);
    }
}
