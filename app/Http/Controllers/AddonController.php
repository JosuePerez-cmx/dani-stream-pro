<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;

class AddonController extends Controller
{
    // Tu Token de Real-Debrid
    private $rdToken = 'OFYPGLL3NATRNMQ4RZT7BVEOAO2ZLDP75MSSVNSSVJAB3GR6YQUQ';

    public function manifest()
    {
        return response()->json([
            'id' => 'com.dani.premium',
            'version' => '2.0.0',
            'name' => 'Dani Cinema+',
            'description' => 'Catálogo privado de élite. Curado a mano con Real-Debrid.',
            'logo' => 'https://i.imgur.com/gKpmY0y.png',
            'resources' => ['stream'],
            'types' => ['movie'],
            'catalogs' => [],
            'idPrefixes' => ['tt']
        ])->header('Access-Control-Allow-Origin', '*');
    }

    public function stream($type, $id)
    {
        $imdbId = str_replace('.json', '', $id);

        $movies = Movie::where('imdb_id', $imdbId)
                       ->where('is_active', true)
                       ->get();

        $streams = [];

        foreach ($movies as $movie) {
            $directUrl = $this->getRealDebridLink($movie->info_hash);

            if ($directUrl) {
                $streams[] = [
                    'name' => "Dani Cinema+\n" . $movie->resolution,
                    'title' => $movie->filename . "\n⚡ RD+ Directo (Alta Velocidad)",
                    'url' => $directUrl
                ];
            } else {
                $streams[] = [
                    'name' => "Dani Cinema+\n" . $movie->resolution,
                    'title' => $movie->filename . "\n⚠️ RD Falló (Carga P2P)",
                    'infoHash' => $movie->info_hash
                ];
            }
        }

        return response()->json([
            'streams' => $streams
        ])->header('Access-Control-Allow-Origin', '*');
    }

    private function getRealDebridLink($infoHash)
    {
        $magnet = "magnet:?xt=urn:btih:{$infoHash}";

        $addResponse = Http::withToken($this->rdToken)
            ->asForm()
            ->post('https://api.real-debrid.com/rest/1.0/torrents/addMagnet', [
                'magnet' => $magnet
            ]);

        if (!$addResponse->successful()) return null;
        $torrentId = $addResponse->json('id');

        $infoResponse = Http::withToken($this->rdToken)
            ->get("https://api.real-debrid.com/rest/1.0/torrents/info/{$torrentId}");

        if (!$infoResponse->successful()) return null;
        
        Http::withToken($this->rdToken)
            ->asForm()
            ->post("https://api.real-debrid.com/rest/1.0/torrents/selectFiles/{$torrentId}", [
                'files' => 'all'
            ]);

        $linkResponse = Http::withToken($this->rdToken)
            ->get("https://api.real-debrid.com/rest/1.0/torrents/info/{$torrentId}");

        $links = $linkResponse->json('links');
        if (empty($links)) return null;

        $unrestrictResponse = Http::withToken($this->rdToken)
            ->asForm()
            ->post('https://api.real-debrid.com/rest/1.0/unrestrict/link', [
                'link' => $links[0]
            ]);

        if (!$unrestrictResponse->successful()) return null;

        return $unrestrictResponse->json('download');
    }

    // Inyector Web Secreto
    public function injectMagnet(Request $request)
    {
        if ($request->password !== 'dani_premium_2026') {
            return response()->json(['error' => 'Acceso denegado.'], 403);
        }

        $imdbId = $request->imdb_id;
        $title = $request->title;
        $year = $request->year;
        $resolution = $request->resolution;
        $magnet = $request->magnet;

        preg_match('/urn:btih:([a-zA-Z0-9]+)/i', $magnet, $matches);
        $hash = isset($matches[1]) ? strtolower($matches[1]) : null;

        if (!$hash) {
            return response()->json(['error' => 'Magnet no válido.'], 400);
        }

        // Etiqueta personalizada de tu marca forzada
        $filename = "[Dani] {$title} {$year} {$resolution} [🌎 Latino].mkv";

        $movie = Movie::updateOrCreate(
            ['imdb_id' => $imdbId, 'resolution' => $resolution],
            [
                'info_hash' => $hash,
                'title' => $title,
                'filename' => $filename,
                'size_bytes' => 0,
                'is_active' => true
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "¡Película inyectada con éxito!",
            'filename' => $filename
        ]);
    }
}