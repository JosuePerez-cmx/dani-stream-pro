<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RealDebridService
{
    protected string $baseUrl = 'https://api.real-debrid.com/rest/1.0';

    public function addMagnetAndSelectFiles(string $apiToken, string $magnetLink): bool
    {
        try {
            $addResponse = Http::withToken($apiToken)->asForm()->post("{$this->baseUrl}/torrents/addMagnet", [
                'magnet' => $magnetLink,
            ]);

            if ($addResponse->failed()) return false;
            $torrentId = $addResponse->json('id');
            if (!$torrentId) return false;

            $selectResponse = Http::withToken($apiToken)->asForm()->post("{$this->baseUrl}/torrents/selectFiles/{$torrentId}", [
                'files' => 'all',
            ]);

            return $selectResponse->successful() || $selectResponse->status() === 204;
        } catch (\Exception $e) {
            Log::error('Excepción RD: ' . $e->getMessage());
            return false;
        }
    }

    public function generateUnrestrictLink(string $apiToken, string $link): ?string
    {
        $response = Http::withToken($apiToken)->asForm()->post("{$this->baseUrl}/unrestrict/link", [
            'link' => $link
        ]);

        return $response->successful() ? $response->json('download') : null;
    }
}