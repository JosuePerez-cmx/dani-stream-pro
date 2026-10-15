<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;

class AddClient extends Command
{
    protected $signature = 'dani:add-client {name} {token}';
    protected $description = 'Registra un nuevo cliente para sincronización automática';

    public function handle(): int
    {
        $name = $this->argument('name');
        $token = $this->argument('token');

        $client = Client::updateOrCreate(
            ['name' => $name],
            ['rd_api_token' => $token, 'is_active' => true]
        );

        $this->info("Cliente '{$client->name}' guardado correctamente.");
        return self::SUCCESS;
    }
}