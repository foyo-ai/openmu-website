<?php

namespace App\Services;

use RuntimeException;

/**
 * Thrown when the OpenMU api/v1 returns a non-2xx response (or is unreachable).
 * Carries the API's machine-readable error code so controllers can map it to a
 * localized message (e.g. character_online -> "log out of the game first").
 */
class OpenMuApiException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function isUnreachable(): bool
    {
        return $this->errorCode === 'unreachable';
    }
}
