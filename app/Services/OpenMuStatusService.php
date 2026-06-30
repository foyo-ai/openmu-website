<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads live server status from the OpenMU admin API (GET /api/status).
 * Fails soft: if the API is unreachable the site still renders.
 */
class OpenMuStatusService
{
    /**
     * Return ['online' => bool, 'players' => int|null]. Cached for 30s.
     */
    public function status(): array
    {
        $base = rtrim((string) config('server.openmu_api'), '/');
        if ($base === '') {
            return ['online' => false, 'players' => null];
        }

        return Cache::remember('openmu.status', 30, function () use ($base) {
            try {
                $resp = Http::timeout(3)->get($base . '/api/status');
                if (! $resp->ok()) {
                    return ['online' => false, 'players' => null];
                }
                $data = $resp->json();
                // OpenMU's status payload includes the online player count; be defensive about shape.
                $players = $data['playerCount']
                    ?? $data['onlineCount']
                    ?? (isset($data['onlinePlayers']) && is_array($data['onlinePlayers']) ? count($data['onlinePlayers']) : null);

                return ['online' => true, 'players' => is_numeric($players) ? (int) $players : null];
            } catch (\Throwable $e) {
                Log::info('OpenMU status check failed: ' . $e->getMessage());
                return ['online' => false, 'players' => null];
            }
        });
    }
}
