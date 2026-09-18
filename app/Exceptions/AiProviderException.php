<?php

namespace App\Exceptions;

use RuntimeException;

class AiProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus = 503,
        private readonly string $reason = 'UNAVAILABLE',
        private readonly bool $transient = false,
        private readonly ?int $retryAfter = null,
        private readonly ?string $providerCode = null,
    ) {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function isTransient(): bool
    {
        return $this->transient;
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function providerCode(): ?string
    {
        return $this->providerCode;
    }
}