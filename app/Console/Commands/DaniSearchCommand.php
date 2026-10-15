<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;

class DaniSearchCommand extends Command
{
    protected $signature = 'dani:search {query} {--min-gb=5} {--max-gb=25}';
    protected $description = 'Busca en Prowlarr, verifica caché en Real-Debrid y guarda en SQLite';

    public function handle()
    {
        $query = $this->argument('query');
        $minSize = $this->option('min-gb') * 1073741824; // Convertir GB a Bytes
        $maxSize = $this->option('max-gb') * 1073741824;
        $rdToken = env('REAL_DEBRID_TOKEN');

        $this->info("Buscando '{$query}' en Prowlarr (entre {$this->option('min-gb')}GB y {$this->option('max-gb')}GB)...");

        // Cambia el puerto/API_KEY por los de tu Prowlarr
        $prowlarrResponse = Http::get("http://127.0.0.1:9696/api/v1/search", [
            'query' => $query,
            'apikey' => '66a340ec72814af7be29c5945c256ccf',
            'type' => 'search'
        ]);

        if (!$prowlarrResponse->successful()) {
            $this->error('Error conectando a Prowlarr.');
            return;
        }

        $results = $prowlarrResponse->json();
        
        foreach ($results as $result) {
            // Filtrar por tamaño y asegurar que sea Magnet
            if ($result['size'] >= $minSize && $result['size'] <= $maxSize && isset($result['infoUrl']) && str_starts_with($result['infoUrl'], 'magnet:')) {
                
                // Extraer el Hash del Magnet
                preg_match('/xt=urn:btih:([^&]+)/', $result['infoUrl'], $matches);
                if (!isset($matches[1])) continue;
                $hash = strtoupper($matches[1]);

                $this->info("Verificando disponibilidad en RD para: " . $result['title']);

                // Magia: Preguntar a Real-Debrid si ya lo tiene cacheado al 100%
                $rdCheck = Http::withToken($rdToken)->get("https://api.real-debrid.com/rest/1.0/torrents/instantAvailability/{$hash}");
                $availability = $rdCheck->json();

                // Si está cacheado (RD devuelve datos dentro del hash)
                if (isset($availability[$hash]) && isset($availability[$hash]['rd']) && count($availability[$hash]['rd']) > 0) {
                    
                    // Lo guardamos en tu Bóveda
                    $movie = Movie::create([
                        'title' => $query,
                        'release_title' => $result['title'],
                        'magnet_link' => $result['infoUrl'],
                        'size_gb' => round($result['size'] / 1073741824, 2),
                        'is_active' => true,
                    ]);

                    $this->info("✅ ¡Éxito! Guardado en la bóveda (ID: {$movie->id}). Este enlace reproducirá al instante.");
                    $this->line("Tamaño: {$movie->size_gb} GB");
                    return; // Terminamos la búsqueda al encontrar el primer oro puro
                } else {
                    $this->warn("❌ No está cacheado en RD. Descartando y probando el siguiente...");
                }
            }
        }

        $this->error("No se encontraron versiones de ese tamaño que estén 100% listas en Real-Debrid.");
    }
}