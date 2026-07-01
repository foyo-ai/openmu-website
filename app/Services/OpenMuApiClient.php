<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Client for the OpenMU game-server api/v1. This is the ONLY way the website reads or writes
 * game data — it never touches the game database directly.
 */
class OpenMuApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            rtrim((string) config('server.openmu_api'), '/'),
            (string) config('server.api_key'),
        );
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl . '/api/v1')
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->acceptJson()
            ->timeout(8);
    }

    /** Throws OpenMuApiException on non-2xx (mapping the API's {error,message}). */
    private function request(string $method, string $path, array $options = []): array
    {
        try {
            $resp = $this->http()->send($method, $path, $options);
        } catch (\Throwable $e) {
            throw new OpenMuApiException('unreachable', 'The game server is not reachable.', 0, $e);
        }

        if ($resp->successful()) {
            return $resp->json() ?? [];
        }

        $body = $resp->json() ?? [];
        throw new OpenMuApiException(
            $body['error'] ?? 'error',
            $body['message'] ?? 'Request failed.',
            $resp->status(),
        );
    }

    // --- Reads ---

    public function ping(): array
    {
        return $this->request('GET', '/ping');
    }

    public function account(string $login): array
    {
        return $this->request('GET', '/accounts/' . rawurlencode($login));
    }

    public function characters(string $login): array
    {
        return $this->request('GET', '/accounts/' . rawurlencode($login) . '/characters');
    }

    public function character(string $name): array
    {
        return $this->request('GET', '/characters/' . rawurlencode($name));
    }

    public function rankings(string $type, int $limit = 100): array
    {
        return $this->request('GET', '/rankings', ['query' => ['type' => $type, 'limit' => $limit]]);
    }

    /** Live server rates/config (experience, master, level cap, reset) read from GameConfiguration. */
    public function serverInfo(): array
    {
        return $this->request('GET', '/server-info');
    }

    // --- Account writes (Component 4) ---

    public function register(array $data): array
    {
        return $this->request('POST', '/accounts', ['json' => $data]);
    }

    public function authenticate(string $login, string $password): array
    {
        return $this->request('POST', '/accounts/authenticate', ['json' => ['login' => $login, 'password' => $password]]);
    }

    public function updatePassword(string $login, string $currentPassword, string $newPassword): array
    {
        return $this->request('PATCH', '/accounts/' . rawurlencode($login) . '/password',
            ['json' => ['currentPassword' => $currentPassword, 'newPassword' => $newPassword]]);
    }

    public function updateEmail(string $login, string $currentPassword, string $email): array
    {
        return $this->request('PATCH', '/accounts/' . rawurlencode($login) . '/email',
            ['json' => ['currentPassword' => $currentPassword, 'email' => $email]]);
    }

    public function updateSecurityCode(string $login, string $currentPassword, string $securityCode): array
    {
        return $this->request('PATCH', '/accounts/' . rawurlencode($login) . '/security-code',
            ['json' => ['currentPassword' => $currentPassword, 'securityCode' => $securityCode]]);
    }

    // --- Character actions (Component 3) ---

    public function reset(string $login, string $name): array
    {
        return $this->request('POST', '/characters/' . rawurlencode($name) . '/reset', ['json' => ['login' => $login]]);
    }

    public function addPoints(string $login, string $name, array $points): array
    {
        return $this->request('POST', '/characters/' . rawurlencode($name) . '/add-points',
            ['json' => ['login' => $login] + $points]);
    }

    public function rename(string $login, string $name, string $newName): array
    {
        return $this->request('PATCH', '/characters/' . rawurlencode($name) . '/rename',
            ['json' => ['login' => $login, 'newName' => $newName]]);
    }

    public function clearPk(string $login, string $name): array
    {
        return $this->request('POST', '/characters/' . rawurlencode($name) . '/clear-pk', ['json' => ['login' => $login]]);
    }

    public function unstick(string $login, string $name): array
    {
        return $this->request('POST', '/characters/' . rawurlencode($name) . '/unstick', ['json' => ['login' => $login]]);
    }

    public function deleteCharacter(string $login, string $name, string $securityCode): array
    {
        return $this->request('DELETE', '/characters/' . rawurlencode($name),
            ['json' => ['login' => $login, 'securityCode' => $securityCode]]);
    }
}
