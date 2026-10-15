<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;

class StremioController extends Controller
{
    // 1. El Manifest (identidad del addon)
    public function manifest()
    {
        return response()->json([
            'id' => 'com.dani.stream',
            'version' => '1.0.0',
            'name' => 'Dani Stream [Latino]',
            'description' => 'Catalogo privado de Dani en calidad 4K/1080p Latino.',
            'resources' => ['stream'], 
            'types' => ['movie'],
            'catalogs' => [],
            'idPrefixes' => ['tt']
        ])->header('Access-Control-Allow-Origin', '*');
    }

    // 2. La función Stream (la que Stremio busca y no encontraba)
    public function stream($type, $id)
    {
        // Limpiamos el ID (Stremio manda "tt22022452.json", lo dejamos en "tt22022452")
        $imdbId = str_replace('.json', '', $id);

        // Buscamos la película en tu base de datos
        $movie = Movie::where('imdb_id', $imdbId)->first();

        // Si no la tienes, le decimos a Stremio que no hay enlaces
        if (!$movie) {
            return response()->json(['streams' => []])->header('Access-Control-Allow-Origin', '*');
        }

        // Si la tienes, le enviamos tu botón personalizado
        return response()->json([
            'streams' => [
                [
                    'name' => '[Dani]',
                    'title' => $movie->title . "\n🚀 4K Latino / Real-Debrid",
                    'url' => url("/play/{$movie->id}") 
                ]
            ]
        ])->header('Access-Control-Allow-Origin', '*');
    }
}