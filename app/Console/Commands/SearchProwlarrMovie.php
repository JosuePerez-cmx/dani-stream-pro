<?php

namespace App\Console\Commands;

use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SearchProwlarrMovie extends Command
{
    protected $signature = 'dani:search {query : Titulo de la pelicula} {--min-gb=2.5} {--max-gb=15}';
    protected $description = 'Rastrea en Prowlarr EXCLUSIVAMENTE versiones con Audio Latino / Dual';

    public function handle(): int
    {
        $query = $this->argument('query');
        $minGb = (float) $this->option('min-gb');
        $maxGb = (float) $this->option('max-gb');

        $baseUrl = rtrim(env('PROWLARR_URL', 'http://127.0.0.1:9696'), '/');
        $apiKey = env('PROWLARR_API_KEY');

        if (!$apiKey) {
            $this->error('Configura PROWLARR_API_KEY en tu archivo .env');
            return self::FAILURE;
        }

        $this->info("Buscando en Prowlarr contenido Latino/Dual: '{$query}'...");

        $response = Http::withHeaders([
            'X-Api-Key' => $apiKey,
        ])->get("{$baseUrl}/api/v1/search", [
            'query' => $query,
            'categories' => [2000],
            'type' => 'search',
        ]);

        if (!$response->successful()) {
            $this->error("Error conectando a Prowlarr: HTTP {$response->status()}");
            return self::FAILURE;
        }

        $results = collect($response->json());

        if ($results->isEmpty()) {
            $this->warn('No se encontraron resultados en los indexadores activos.');
            return self::SUCCESS;
        }

        // CANDADO ESTRICTO: Solo títulos con indicación explícita de audio en español latino / dual
        $latinoResults = $results->filter(function ($item) use ($minGb, $maxGb) {
            $title = strtolower($item['title'] ?? '');
            $sizeGb = ($item['size'] ?? 0) / (1024 * 1024 * 1024);

            $hasLatinoTag = str_contains($title, 'latino') 
                || str_contains($title, 'audio latino')
                || str_contains($title, 'dual')
                || str_contains($title, 'multi-audio')
                || str_contains($title, 'es-la');

            $isOptimalSize = ($sizeGb >= $minGb && $sizeGb <= $maxGb);

            return $hasLatinoTag && $isOptimalSize;
        });

        if ($latinoResults->isEmpty()) {
            $this->warn("Se encontraron {$results->count()} torrents, pero NINGUNO tiene audio latino confirmado.");
            $this->line("Descartado automaticamente. No se guardo nada en la base de datos.");
            return self::SUCCESS;
        }

        // Seleccionar el que tenga más semillas
        $best = $latinoResults->sortByDesc(fn($item) => $item['seeders'] ?? 0)->first();
$sizeFormatted = number_format(($best['size'] ?? 0) / (1024 * 1024 * 1024), 2);
        $seeders = $best['seeders'] ?? 0;
        
        // Extraemos el infoHash para construir un magnet puro indestructible
        $infoHash = $best['infoHash'] ?? null;
        $magnetUrl = $best['magnetUrl'] ?? null;

        if (!$magnetUrl && $infoHash) {
            $encodedTitle = urlencode($best['title']);
            $magnetUrl = "magnet:?xt=urn:btih:{$infoHash}&dn={$encodedTitle}";
        } elseif (!$magnetUrl) {
            $magnetUrl = $best['downloadUrl'] ?? null;
        }

        $this->info("✔ Release Latino encontrado:");

        if (!$magnetUrl) {
            $this->error('El indexador no entrego un enlace magnet directo.');
            return self::FAILURE;
        }

        $movie = Movie::updateOrCreate(
            ['title' => $query],
            [
                'release_title' => "[Dani] " . $best['title'],
                'magnet_link' => $magnetUrl,
                'size_gb' => $sizeFormatted,
                'is_active' => true,
            ]
        );

        $this->info("Registrado en SQLite con ID: {$movie->id} (Latino confirmado)");
        return self::SUCCESS;
    }
}