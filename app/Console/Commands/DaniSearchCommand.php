<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;

class DaniSearchCommand extends Command
{
    // Soporte para búsqueda manual exacta y parámetros limpios
    protected $signature = 'dani:search {imdb_id} {title} {year} {--manual=}';
    protected $description = 'Busca, filtra, valida caché en RD y guarda. Usa --manual para búsquedas exactas.';

    public function handle()
    {
        $imdbId = $this->argument('imdb_id');
        $title = $this->argument('title');
        $year = $this->argument('year');
        $manualQuery = $this->option('manual');
        
        // Si mandas --manual, usa esa frase en Prowlarr; si no, el default (Título + Año)
        $searchQuery = $manualQuery ? $manualQuery : "{$title} {$year}";
        $rdToken = env('REAL_DEBRID_TOKEN');

        $this->info("Buscando '{$searchQuery}' en Prowlarr...");

        $prowlarrResponse = Http::get("http://127.0.0.1:9696/api/v1/search", [
            'query' => $searchQuery,
            'apikey' => '66a340ec72814af7be29c5945c256ccf',
            'type' => 'search'
        ]);

        if (!$prowlarrResponse->successful()) {
            $this->error('Error conectando a Prowlarr.');
            return;
        }

        $results = $prowlarrResponse->json();
        
        // 4K: Ahora de 5GB a 20GB. | 1080p: de 1GB a 8GB.
        $this->procesarResolucion($results, '2160p', $imdbId, $title, $year, $rdToken, 5368709120, 21474836480); 
        $this->procesarResolucion($results, '1080p', $imdbId, $title, $year, $rdToken, 1073741824, 8589934592); 
    }

    private function procesarResolucion($results, $resolucion, $imdbId, $title, $year, $rdToken, $minBytes, $maxBytes)
    {
        $this->warn("--- Buscando versión {$resolucion} ---");
        
        // Filtro de idiomas expandido
        $regexLatino = '/(?i)(latino|lat|dual|multi|spa|spanish)/';
        $regexRes = $resolucion === '2160p' ? '/(?i)(2160p|4k)/' : '/(?i)(1080p)/';

        foreach ($results as $result) {
            $nombreOriginal = $result['title'];
            $size = $result['size'];
            $magnet = $result['infoUrl'] ?? '';

            // El chivato para ver todo en la consola
            $this->line("🔍 Prowlarr vio: " . $nombreOriginal . " | Peso: " . round($size / 1073741824, 2) . " GB");

            if (str_starts_with($magnet, 'magnet:') && $size >= $minBytes && $size <= $maxBytes) {
                if (preg_match($regexRes, $nombreOriginal)) {
                    
                    // Magia: Validar el idioma O saltarse el filtro si usaste --manual
                    if (preg_match($regexLatino, $nombreOriginal) || $this->option('manual')) {
                        
                        preg_match('/xt=urn:btih:([^&]+)/', $magnet, $matches);
                        if (!isset($matches[1])) continue;
                        $hash = strtoupper($matches[1]);

                        // Verificar en Real-Debrid
                        $rdCheck = Http::withToken($rdToken)->get("https://api.real-debrid.com/rest/1.0/torrents/instantAvailability/{$hash}");
                        $availability = $rdCheck->json();

                        if (isset($availability[$hash]) && isset($availability[$hash]['rd']) && count($availability[$hash]['rd']) > 0) {
                            
                            $filename = "[Dani] {$title} {$year} {$resolucion} [🌎 Latino].mkv";

                            Movie::updateOrCreate(
                                ['imdb_id' => $imdbId, 'resolution' => $resolucion],
                                [
                                    'info_hash' => $hash,
                                    'title' => $title,
                                    'filename' => $filename,
                                    'size_bytes' => $size,
                                    'is_active' => true
                                ]
                            );

                            $sizeGb = round($size / 1073741824, 2);
                            $this->info("✅ Guardado {$resolucion}: {$filename} ({$sizeGb} GB)");
                            return; 
                        }
                    }
                }
            }
        }
        $this->error("❌ No se encontró versión {$resolucion} cacheada en RD con esos criterios.");
    }
}