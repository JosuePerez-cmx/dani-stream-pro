<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;

class DaniTorrentioCommand extends Command
{
    protected $signature = 'dani:torrentio {imdb_id} {title} {year}';
    protected $description = 'Roba los Hashes directamente de la API de Torrentio y los inyecta en tu DB.';

    public function handle()
    {
        $imdbId = $this->argument('imdb_id');
        $title = $this->argument('title');
        $year = $this->argument('year');
        $rdToken = env('REAL_DEBRID_TOKEN');

        $this->warn("🦇 Consumiendo la API de Torrentio para '{$title}'...");

        // Llamamos a la API JSON oculta de Torrentio
        $response = Http::get("https://torrentio.strem.fun/stream/movie/{$imdbId}.json");

        if (!$response->successful()) {
            $this->error('❌ No se pudo conectar a Torrentio.');
            return;
        }

        $streams = $response->json()['streams'] ?? [];

        if (empty($streams)) {
            $this->error('❌ Torrentio no encontró nada.');
            return;
        }

        // Filtros para cazar el 1080p Latino
        $regexLatino = '/(?i)(latino|lat|spa|spanish|dual|multi)/';
        $regex1080 = '/(?i)(1080p)/';

        foreach ($streams as $stream) {
            $streamName = $stream['name'] ?? ''; // Ej: Torrentio\n1080p
            $streamTitle = $stream['title'] ?? ''; // Contiene el nombre del archivo
            $hash = $stream['infoHash'] ?? null;

            if (!$hash) continue;

            // Verificamos que sea 1080p y que diga algo de latino o español en el título
            if (preg_match($regex1080, $streamName) || preg_match($regex1080, $streamTitle)) {
                if (preg_match($regexLatino, $streamTitle)) {
                    
                    $this->line("🔍 Validando Hash en RD: " . $hash);

                    // Verificamos si Real-Debrid lo tiene cacheado
                    $rdCheck = Http::withToken($rdToken)->get("https://api.real-debrid.com/rest/1.0/torrents/instantAvailability/{$hash}");
                    $availability = $rdCheck->json();

                    if (isset($availability[$hash]) && isset($availability[$hash]['rd']) && count($availability[$hash]['rd']) > 0) {
                        
                        $filename = "[Dani] {$title} {$year} 1080p [🌎 Latino].mkv";

                        Movie::updateOrCreate(
                            ['imdb_id' => $imdbId, 'resolution' => '1080p'],
                            [
                                'info_hash' => $hash,
                                'title' => $title,
                                'filename' => $filename,
                                'size_bytes' => 0, // Ignoramos el peso exacto para hacerlo súper rápido
                                'is_active' => true
                            ]
                        );

                        $this->info("✅ ¡HACK EXITOSO! Guardado en SQLite directo desde Torrentio: {$filename}");
                        return;
                    }
                }
            }
        }
        $this->error("❌ Ni Torrentio tiene una versión 1080p Latina 100% cacheada para esta película.");
    }
}