<?php

namespace App\Services\Chats\Repositories;

use App\Models\Chat;

class EloquentChatRepository implements ChatRepository
{

    public function find(int $id): ?Chat
    {
        return Chat::find($id);
    }

    public function createFromArray(array $data): Chat
    {
        return Chat::create($data);
    }

    public function findChatBetweenTwoUsers(int $userIdFirst, int $userIdSecond): ?Chat
    {
        return Chat::select('*')
            ->where(function ($query) use ($userIdFirst, $userIdSecond) {
                $query->where('user_id_first', '=', $userIdFirst)
                    ->where('user_id_second', '=', $userIdSecond);
            })
            ->orWhere(function ($query) use ($userIdFirst, $userIdSecond) {
                $query->where('user_id_first', '=', $userIdSecond)
                    ->where('user_id_second', '=', $userIdFirst);
            })
            ->first();
    }
}
