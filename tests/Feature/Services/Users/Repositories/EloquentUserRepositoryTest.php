<?php

namespace Tests\Feature\Services\Users\Repositories;

use App\Services\Users\Repositories\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nette\Utils\Random;
use Tests\Generators\UserGenerator;
use Tests\TestCase;

class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function getEloquentUserRepository(): EloquentUserRepository
    {
        return app(EloquentUserRepository::class);
    }

    public function testFindExpectsNotNull(): void
    {
        $model = UserGenerator::generate();

        $message = $this->getEloquentUserRepository()->find($model->id);

        $this->assertNotNull($message);
    }

    public function testFindExpectsNull(): void
    {
        $id = Random::generate(3, '0-9');

        $category = $this->getEloquentUserRepository()->find($id);

        $this->assertNull($category);
    }
}
