<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Movie;
use App\Services\RealDebridService;
use Illuminate\Console\Command;

class ImportMasterContent extends Command
{
    protected $signature = 'dani:import-master';
    protected $description = 'Importa automáticamente todo el contenido [Dani] desde la cuenta maestra de Real-Debrid';

    public function handle(RealDebridService $rdService): int
    {
        // Toma tu cuenta maestra (el primer cliente activo registrado)
        $masterAccount = Client::where('is_active', true)->first();

        if (!$masterAccount) {
            $this->error('No hay una cuenta maestra registrada en clients.');
            return self::FAILURE;
        }

        $this->info("Buscando contenido nuevo en la cuenta: {$masterAccount->name}...");

        $torrents = $rdService->getTorrents($masterAccount->rd_api_token, 100);

        if (empty($torrents)) {
            $this->warn('No se encontraron torrents en la cuenta maestra.');
            return self::SUCCESS;
        }

        $count = 0;

        foreach ($torrents as $torrent) {
            $filename = $torrent['filename'] ?? '';
            $hash = $torrent['hash'] ?? '';
            $status = $torrent['status'] ?? '';

            // Solo importa elementos completados al 100% que tengan el prefijo [Dani]
            if (str_starts_with($filename, '[Dani]') && $status === 'downloaded') {
                $magnetLink = "magnet:?xt=urn:btih:{$hash}";

                $movie = Movie::updateOrCreate(
                    ['title' => $filename],
                    [
                        'magnet_link' => $magnetLink,
                        'is_active' => true,
                    ]
                );

                if ($movie->wasRecentlyCreated) {
                    $this->info(" -> Nuevo contenido importado: {$filename}");
                    $count++;
                }
            }
        }

        $this->info("Proceso completado. Se importaron {$count} elementos nuevos.");
        return self::SUCCESS;
    }
}