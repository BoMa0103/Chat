<?php

namespace App\Services\Users\Repositories;

use App\Models\User;

interface UserRepository
{
    public function find(int $id): ?User;
}
