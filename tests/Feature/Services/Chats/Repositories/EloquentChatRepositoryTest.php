<?php

namespace Tests\Feature\Services\Chats\Repositories;

use App\Services\Chats\Repositories\EloquentChatRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nette\Utils\Random;
use Tests\Generators\ChatGenerator;
use Tests\Generators\UserGenerator;
use Tests\TestCase;

class EloquentChatRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private function getEloquentChatRepository(): EloquentChatRepository
    {
        return app(EloquentChatRepository::class);
    }

    public function testFindExpectsNotNull(): void
    {
        $model = ChatGenerator::generate();

        $chat = $this->getEloquentChatRepository()->find($model->id);

        $this->assertNotNull($chat);
    }

    public function testFindExpectsNull(): void
    {
        $id = Random::generate(3, '0-9');

        $category = $this->getEloquentChatRepository()->find($id);

        $this->assertNull($category);
    }

    public function testCreateExpectsSuccess(): void
    {
        $model = ChatGenerator::generateModel();

        $chat = $this->getEloquentChatRepository()->createFromArray($model);

        $this->assertSame($model['user_id_first'], $chat->user_id_first);
        $this->assertDatabaseCount('chats', 1);
    }

    public function testFindChatBetweenTwoUsersExpectsNull(): void
    {
        $userFirst = UserGenerator::generate();

        $chat = $this->getEloquentChatRepository()->findChatBetweenTwoUsers($userFirst->id, Random::generate(3, '0-9'));

        $this->assertNull($chat);
    }

    public function testFindChatBetweenTwoUsersExpectsNotNull(): void
    {
        $chat = ChatGenerator::generate();

        $chat = $this->getEloquentChatRepository()->findChatBetweenTwoUsers($chat->user_id_first, $chat->user_id_second);

        $this->assertNotNull($chat);
    }
}
