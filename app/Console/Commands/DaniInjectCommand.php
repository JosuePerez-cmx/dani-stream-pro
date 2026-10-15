<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;

class DaniInjectCommand extends Command
{
    protected $signature = 'dani:inject {imdb_id} {title} {year} {resolution} {magnet}';
    protected $description = 'Iniectio manualis pro magnetibus in repositorium.';

    public function handle()
    {
        $imdbId = $this->argument('imdb_id');
        $title = $this->argument('title');
        $year = $this->argument('year');
        $resolution = $this->argument('resolution');
        $magnet = $this->argument('magnet');

        preg_match('/urn:btih:([a-zA-Z0-9]+)/i', $magnet, $matches);
        $hash = isset($matches[1]) ? strtolower($matches[1]) : null;

        if (!$hash) {
            $this->error('Error: Magnet non validus.');
            return;
        }

        $filename = "[Dani] {$title} {$year} {$resolution} [🌎 Latino].mkv";

        Movie::updateOrCreate(
            ['imdb_id' => $imdbId, 'resolution' => $resolution],
            [
                'info_hash' => $hash,
                'title' => $title,
                'filename' => $filename,
                'size_bytes' => 0,
                'is_active' => true
            ]
        );

        $this->info("Successus! {$filename} in repositorium iniectus est cum infoHash: {$hash}");
    }
}