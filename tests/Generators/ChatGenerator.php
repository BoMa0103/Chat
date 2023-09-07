<?php

namespace Tests\Generators;

use App\Models\Chat;
use App\Models\User;

class ChatGenerator
{
    public static function generate(array $data = []): Chat
    {
        $userFirst = User::factory()->create();
        $userSecond = User::factory()->create();
        return Chat::factory()->for($userFirst, 'userFist')->for($userSecond, 'userSecond')->create($data);
    }

    public static function generateModel(array $data = []): array
    {
        $userFirst = User::factory()->create();
        $userSecond = User::factory()->create();
        return [
            'user_id_first' => $data['user_id_first'] ?? $userFirst->id,
            'user_id_second' => $data['user_id_second'] ?? $userSecond->id,
        ];
    }
}
