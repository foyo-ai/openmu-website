<?php

namespace App\Auth;

use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * Authenticates and loads accounts through the game-server API — the website never
 * queries the account table directly. The session stores only the login name; each
 * request reconstructs the identity via GET /accounts/{login}.
 */
class ApiUserProvider implements UserProvider
{
    /** Rebuild the identity from the session-stored login (called every authed request). */
    public function retrieveById($identifier): ?Authenticatable
    {
        try {
            $account = OpenMuApiClient::fromConfig()->account((string) $identifier);
        } catch (OpenMuApiException $e) {
            return null;
        }

        return AccountIdentity::fromApi($account);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
    }

    /** Authenticate via the API (login + password) and return the identity, or null. */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $login = $credentials['LoginName'] ?? $credentials['login'] ?? null;
        $password = $credentials['password'] ?? null;
        if (! $login || ! $password) {
            return null;
        }

        try {
            $account = OpenMuApiClient::fromConfig()->authenticate((string) $login, (string) $password);
        } catch (OpenMuApiException $e) {
            return null;
        }

        return AccountIdentity::fromApi($account);
    }

    /** Credentials were already validated by the API in retrieveByCredentials. */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return true;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
    }
}
