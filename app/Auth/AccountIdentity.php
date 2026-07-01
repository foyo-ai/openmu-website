<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A lightweight authenticated account, backed by the game-server API (not the DB).
 * Exposes the same property names the views/middleware used on the old Eloquent User
 * (LoginName, EMail, State) so nothing downstream needs to change.
 */
class AccountIdentity implements Authenticatable
{
    public function __construct(
        public string $LoginName,
        public ?string $EMail = null,
        public int $State = 0,
    ) {
    }

    public static function fromApi(array $account): self
    {
        return new self(
            $account['login'] ?? '',
            $account['email'] ?? null,
            (int) ($account['state'] ?? 0),
        );
    }

    public function getAuthIdentifierName(): string
    {
        return 'LoginName';
    }

    public function getAuthIdentifier(): string
    {
        return $this->LoginName;
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
