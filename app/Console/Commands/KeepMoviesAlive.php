<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Movie;
use App\Services\RealDebridService;
use Illuminate\Console\Command;

class KeepMoviesAlive extends Command
{
    protected $signature = 'dani:keep-alive';

    protected $description = 'Refresca la caché de las películas [Dani] en Real-Debrid para que nunca sean borradas por inactividad';

    public function handle(RealDebridService $rdService): int
    {
        // Usamos el primer cliente activo (tu cuenta maestra) para refrescar la caché global
        $masterAccount = Client::where('is_active', true)->first();
        $movies = Movie::where('is_active', true)->get();

        if (!$masterAccount || $movies->isEmpty()) {
            $this->warn('Falta cuenta maestra activa o películas por refrescar.');
            return self::SUCCESS;
        }

        $this->info("Refrescando caché en Real-Debrid con la cuenta: {$masterAccount->name}...");

        foreach ($movies as $movie) {
            $this->output->write(" -> Manteniendo viva: {$movie->title}... ");

            $ok = $rdService->addMagnetAndSelectFiles($masterAccount->rd_api_token, $movie->magnet_link);

            if ($ok) {
                $movie->update(['last_kept_alive_at' => now()]);
                $this->info("¡Caché renovada!");
            } else {
                $this->error("FALLÓ");
            }

            usleep(500000);
        }

        $this->info('¡Todas las películas [Dani] tienen su tiempo de vida renovado!');
        return self::SUCCESS;
    }
}