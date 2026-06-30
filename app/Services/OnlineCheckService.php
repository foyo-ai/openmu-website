<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Checks whether an account is currently online in the game, via OpenMU's
 * GET /api/is-online/{loginName} (returns a bare JSON bool, keyed on Account.LoginName).
 *
 * Used as a safety gate before writing to a character: we never modify a character
 * whose account is logged in, because the game caches it in memory and would
 * overwrite our change (or conflict).
 */
class OnlineCheckService
{
    /**
     * Is this account online? Fails CLOSED: if the API is configured but
     * unreachable/errors, we treat the account as online (block the action).
     * If no API is configured at all, the check is disabled (returns false).
     */
    public function isOnline(string $loginName): bool
    {
        $base = rtrim((string) config('server.openmu_api'), '/');
        if ($base === '') {
            return false; // check disabled (e.g. local dev with no game API)
        }

        try {
            $resp = Http::timeout(4)->get($base . '/api/is-online/' . rawurlencode($loginName));
            if (! $resp->successful()) {
                return true; // fail closed
            }
            // OpenMU returns a bare JSON boolean.
            return filter_var(trim($resp->body()), FILTER_VALIDATE_BOOLEAN);
        } catch (\Throwable $e) {
            Log::warning('is-online check failed for ' . $loginName . ': ' . $e->getMessage());
            return true; // fail closed
        }
    }
}
