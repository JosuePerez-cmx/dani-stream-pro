<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        Movie::updateOrCreate(
            ['imdb_id' => 'tt2096673'], // ID de Intensamente en IMDb
            [
                'title' => 'Intensamente',
                // Agrega aquí los campos exactos de tu tabla (por ejemplo link de RD o stream_url)
            ]
        );
    }
}