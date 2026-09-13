<?php

namespace App\Exceptions;

use RuntimeException;

class ReniecException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus = 502,
        private readonly ?string $resultCode = null
    ) {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function resultCode(): ?string
    {
        return $this->resultCode;
    }
}
