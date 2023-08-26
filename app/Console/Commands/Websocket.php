<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class Websocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'WebsocketService server run command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('WebsocketService server run');

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new \App\Services\Websocket\WebsocketService()
                )
            ),
            8080
        );

        $server->run();
    }
}
