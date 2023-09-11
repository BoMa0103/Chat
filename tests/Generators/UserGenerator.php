<?php

namespace Tests\Generators;

use App\Models\User;

class UserGenerator
{
    public static function generate(array $data = []): User
    {
        return User::factory()->create($data);
    }
}
