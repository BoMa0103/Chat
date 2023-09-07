<?php

namespace Tests\Feature\Services\Messages\Repositories;

use App\Services\Messages\Repositories\EloquentMessageRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nette\Utils\Random;
use Tests\Generators\ChatGenerator;
use Tests\Generators\MessageGenerator;
use Tests\TestCase;

class EloquentMessageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function getEloquentMessageRepository(): EloquentMessageRepository
    {
        return app(EloquentMessageRepository::class);
    }

    public function testFindExpectsNotNull(): void
    {
        $model = MessageGenerator::generate();

        $message = $this->getEloquentMessageRepository()->find($model->id);

        $this->assertNotNull($message);
    }

    public function testFindExpectsNull(): void
    {
        $id = Random::generate(3, '0-9');

        $category = $this->getEloquentMessageRepository()->find($id);

        $this->assertNull($category);
    }

    public function testCreateExpectsSuccess(): void
    {
        $model = MessageGenerator::generateModel();

        $chat = $this->getEloquentMessageRepository()->createFromArray($model);

        $this->assertSame($model['user_id'], $chat->user_id);
        $this->assertDatabaseCount('messages', 1);
    }

    public function testGetMessagesByChatIdOffsetLimitNotEmpty(): void
    {
        $messages = MessageGenerator::generateCollection(30);

        $getMessages = $this->getEloquentMessageRepository()->getMessagesByChatIdOffsetLimit($messages[0]->chat_id, 0, 20);

        $this->assertNotEmpty($getMessages);
    }

    public function testGetMessagesByChatIdOffsetLimitEmpty(): void
    {
        $messages = MessageGenerator::generateCollection(30);

        $getMessages = $this->getEloquentMessageRepository()->getMessagesByChatIdOffsetLimit($messages[0]->chat_id, 40, 20);

        $this->assertEmpty($getMessages);
    }

    public function testSetReadStatusMessagesExpectsSuccess(): void
    {
        $message = MessageGenerator::generate();
        $receiverUserId = Random::generate(3, '0-9');

        $this->getEloquentMessageRepository()->setReadStatusMessages($message->chat_id, $receiverUserId);
        $message = $this->getEloquentMessageRepository()->find($message->id);

        $this->assertSame(1, $message->read_status);
    }

    public function testGetUnreadMessagesCount(): void
    {
        $message = MessageGenerator::generate();
        $receiverUserId = Random::generate(3, '0-9');

        $count = $this->getEloquentMessageRepository()->getUnreadMessagesCount($message->chat_id, $receiverUserId);

        $this->assertSame(1, $count);
    }
}
