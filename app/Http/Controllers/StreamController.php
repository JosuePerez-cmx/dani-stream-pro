<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StreamController extends Controller
{
    public function play($id)
    {
        $movie = Movie::findOrFail($id);
        $magnetOrUrl = $movie->magnet_link;
        $token = env('REAL_DEBRID_TOKEN');

        if (!$token) {
            return response()->json(['error' => 'Falta el REAL_DEBRID_TOKEN en el .env'], 500);
        }

        if (str_starts_with($magnetOrUrl, 'http')) {
            // Evitamos que Laravel siga la redirección automáticamente
            $prowlarrResponse = Http::withoutRedirecting()->get($magnetOrUrl);
            
            // Verificamos si Prowlarr nos devolvió un redireccionamiento
            if ($prowlarrResponse->redirect() || $prowlarrResponse->hasHeader('Location')) {
                $redirectUrl = $prowlarrResponse->header('Location');
                
                if (str_starts_with($redirectUrl, 'magnet:')) {
                    $addResponse = Http::withToken($token)->asForm()->post('https://api.real-debrid.com/rest/1.0/torrents/addMagnet', [
                        'magnet' => $redirectUrl,
                    ]);
                } else {
                    return response()->json(['error' => 'Redirección desconocida: ' . $redirectUrl], 500);
                }
            } elseif ($prowlarrResponse->successful()) {
                $addResponse = Http::withToken($token)->withBody($prowlarrResponse->body(), 'application/x-bittorrent')->put('https://api.real-debrid.com/rest/1.0/torrents/addTorrent');
            } else {
                return response()->json(['error' => 'No se pudo obtener respuesta válida de Prowlarr'], 500);
            }
        } else {
            // Ya era un magnet desde el principio
            $addResponse = Http::withToken($token)->asForm()->post('https://api.real-debrid.com/rest/1.0/torrents/addMagnet', [
                'magnet' => $magnetOrUrl,
            ]);
        }

        if (!$addResponse->successful()) {
            return response()->json([
                'error' => 'Error al añadir a Real-Debrid',
                'status' => $addResponse->status(),
                'rd_response' => $addResponse->json()
            ], 500);
        }

        $torrentId = $addResponse->json('id');

        // Seleccionar todos los archivos del torrent
        Http::withToken($token)->asForm()->post("https://api.real-debrid.com/rest/1.0/torrents/selectFiles/{$torrentId}", [
            'files' => 'all'
        ]);

        // Obtener la info y los links generados
        $infoResponse = Http::withToken($token)->get("https://api.real-debrid.com/rest/1.0/torrents/info/{$torrentId}");
        $links = $infoResponse->json('links');

        if (empty($links)) {
            return response()->json(['error' => 'El torrent se añadio, pero aún no está cacheado en los servidores de Real-Debrid al 100%.'], 404);
        }

        $rdLink = $links[0];

        // Desbloquear el link para obtener la URL del video MP4/MKV
        $unrestrictResponse = Http::withToken($token)->asForm()->post('https://api.real-debrid.com/rest/1.0/unrestrict/link', [
            'link' => $rdLink
        ]);

        // AQUI ESTA LA MAGIA: En lugar de devolver texto, redirigimos el reproductor de Stremio directo al archivo de video.
        return redirect($unrestrictResponse->json('download'));
    }
}