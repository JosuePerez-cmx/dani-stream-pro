<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RealDebridService
{
    protected string $baseUrl = 'https://api.real-debrid.com/rest/1.0';

    /**
     * Inyecta un enlace Magnet en la cuenta de Real-Debrid del token proporcionado
     * y selecciona los archivos automáticamente para que aparezcan en Stremio.
     */
    public function addMagnetAndSelectFiles(string $apiToken, string $magnetLink): bool
    {
        try {
            // 1. Agregar el Magnet a Real-Debrid
            $addResponse = Http::withToken($apiToken)
                ->asForm()
                ->post("{$this->baseUrl}/torrents/addMagnet", [
                    'magnet' => $magnetLink,
                ]);

            if ($addResponse->failed()) {
                Log::error('Error al agregar Magnet en RD: ' . $addResponse->body());
                return false;
            }

            $torrentId = $addResponse->json('id');

            if (!$torrentId) {
                return false;
            }

            // 2. Seleccionar todos los archivos del torrent para activarlo de inmediato
            $selectResponse = Http::withToken($apiToken)
                ->asForm()
                ->post("{$this->baseUrl}/torrents/selectFiles/{$torrentId}", [
                    'files' => 'all',
                ]);

            return $selectResponse->successful() || $selectResponse->status() === 204;

        } catch (\Exception $e) {
            Log::error('Excepción en RealDebridService: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Obtiene la lista de torrents de la cuenta del token indicado.
     */
    public function getTorrents(string $apiToken, int $limit = 50): array
    {
        try {
            $response = Http::withToken($apiToken)
                ->get("{$this->baseUrl}/torrents", [
                    'limit' => $limit,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error al obtener torrents de RD: ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('Excepción al listar torrents en RD: ' . $e->getMessage());
            return [];
        }
    }
}