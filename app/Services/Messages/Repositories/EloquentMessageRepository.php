<?php

namespace App\Services\Messages\Repositories;

use App\Models\Message;

class EloquentMessageRepository implements MessageRepository
{

    public function find(int $id): ?Message
    {
        return Message::find($id);
    }

    public function createFromArray(array $data): Message
    {
        return Message::create($data);
    }

    public function getMessagesByChatIdOffsetLimit(int $chatId, int $offset, int $limit)
    {
        return Message::select('*')
            ->where('chat_id', '=', $chatId)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
}
