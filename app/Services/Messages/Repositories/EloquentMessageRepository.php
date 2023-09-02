<?php

namespace App\Services\Messages\Repositories;

use App\Models\Message;

class EloquentMessageRepository implements MessageRepository
{
    private const UNREAD_STATUS = 0;
    private const READ_STATUS = 1;

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

    public function setReadStatusMessages(int $chatId, int $userId)
    {
        return Message::where('chat_id', '=', $chatId)
            ->where('read_status', '=', self::UNREAD_STATUS)
            ->where('user_id', '!=', $userId)
            ->update(['read_status' => self::READ_STATUS]);
    }

    public function getUnreadMessagesCount(int $chatId, int $userId): int
    {
        return Message::where('chat_id', '=', $chatId)
            ->where('user_id', '!=', $userId)
            ->where('read_status', '=', self::UNREAD_STATUS)
            ->count();
    }
}
