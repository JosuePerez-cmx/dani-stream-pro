<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Movie;
use App\Services\RealDebridService;
use Illuminate\Console\Command;

class SyncClientMovies extends Command
{
    /**
     * El nombre y firma del comando en la terminal.
     *
     * @var string
     */
    protected $signature = 'dani:sync-movies';

    /**
     * Descripción del comando.
     *
     * @var string
     */
    protected $description = 'Inyecta todas las películas activas [Dani] en las cuentas de Real-Debrid de los clientes activos';

    /**
     * Ejecutar la lógica del comando.
     */
    public function handle(RealDebridService $rdService): int
    {
        $clients = Client::where('is_active', true)->get();
        $movies = Movie::where('is_active', true)->get();

        if ($clients->isEmpty()) {
            $this->warn('No hay clientes activos registrados en la base de datos.');
            return self::SUCCESS;
        }

        if ($movies->isEmpty()) {
            $this->warn('No hay películas activas registradas en la base de datos.');
            return self::SUCCESS;
        }

        $this->info("Iniciando sincronización: {$movies->count()} películas para {$clients->count()} clientes...");

        foreach ($clients as $client) {
            $this->line("------------------------------------------------");
            $this->info("Sincronizando cliente: {$client->name}");

            foreach ($movies as $movie) {
                $this->output->write(" -> Enviando: {$movie->title}... ");

                $ok = $rdService->addMagnetAndSelectFiles($client->rd_api_token, $movie->magnet_link);

                if ($ok) {
                    $this->info("¡OK!");
                } else {
                    $this->error("FALLÓ (Revisar token o magnet)");
                }

                // Pequeña pausa de 0.5 segundos para cuidar el límite de peticiones de Real-Debrid
                usleep(500000);
            }

            $client->update(['last_synced_at' => now()]);
        }

        $this->line("------------------------------------------------");
        $this->info('¡Sincronización masiva completada con éxito!');

        return self::SUCCESS;
    }
}