<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Movie;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Cliente de prueba (Pega aquí tu API Token privado de Real-Debrid)
        Client::updateOrCreate(
            ['name' => 'Dani Master (Prueba)'],
            [
                'rd_api_token' => '4WUNKHDTIAPA26LRY5CDCWXOQO4LNJRLZG6MAIQOXI3QPUHLWL3Q',
                'is_active' => true,
            ]
        );


        // 2. Las 5 películas de la saga Crepúsculo con el identificador [Dani]
        $movies = [
            // Temporadas de Chucky
            [
                'title' => '[Dani] Chucky S01 1080p Dual Latino',
                'imdb_id' => 'tt8388390',
                'magnet_link' => 'magnet:?xt=urn:btih:cbd6f7011f2c71657b9708ba4d845affe0f3bf63&dn=%5BDani%5D%20Chucky%20S01%201080p%20Dual%20Latino&xl=15017604679&tr=udp%3A%2F%2Ftracker.opentrackr.org%3A1337%2Fannounce&tr=udp%3A%2F%2Ftracker.openbittorrent.com%3A6969%2Fannounce',
            ],
            [
                'title' => '[Dani] Chucky S02 1080p Dual Latino',
                'imdb_id' => 'tt8388390',
                'magnet_link' => 'magnet:?xt=urn:btih:28ff532c2d42b07907cca2dd55a6d52d53a76b57&dn=%5BDani%5D%20Chucky%20S02%201080p%20Dual%20Latino&xl=15387292177&tr=udp%3A%2F%2Ftracker.opentrackr.org%3A1337%2Fannounce&tr=udp%3A%2F%2Ftracker.openbittorrent.com%3A6969%2Fannounce',
            ],
            [
                'title' => '[Dani] Chucky S03 1080p Dual Latino',
                'imdb_id' => 'tt8388390',
                'magnet_link' => 'magnet:?xt=urn:btih:904f0fdaf8c8d0820dc87cec04c4b18e78d4df0a&dn=%5BDani%5D%20Chucky%20S03%201080p%20Dual%20Latino&xl=14559050016&tr=udp%3A%2F%2Ftracker.opentrackr.org%3A1337%2Fannounce&tr=udp%3A%2F%2Ftracker.openbittorrent.com%3A6969%2Fannounce',
            ],
        ];

        foreach ($movies as $movie) {
            Movie::updateOrCreate(
                ['title' => $movie['title']],
                [
                    'imdb_id' => $movie['imdb_id'],
                    'magnet_link' => $movie['magnet_link'],
                    'is_active' => true,
                ]
            );
        }
    }
}