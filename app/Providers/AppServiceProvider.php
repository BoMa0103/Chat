<?php

namespace App\Providers;

use App\Services\Chats\Repositories\ChatRepository;
use App\Services\Chats\Repositories\EloquentChatRepository;
use App\Services\Messages\Repositories\EloquentMessageRepository;
use App\Services\Messages\Repositories\MessageRepository;
use App\Services\Users\Repositories\EloquentUserRepository;
use App\Services\Users\Repositories\UserRepository;
use App\Services\Websocket\Repositories\RedisWebsocketRepository;
use App\Services\Websocket\Repositories\WebsocketRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WebsocketRepository::class, RedisWebsocketRepository::class);
        $this->app->bind(ChatRepository::class, EloquentChatRepository::class);
        $this->app->bind(MessageRepository::class, EloquentMessageRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
