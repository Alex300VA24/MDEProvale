<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * La programación de entrega no puede generarse o guardarse para el período.
 * Lleva el estado HTTP y el código de error que la capa de presentación debe responder.
 */
class ProgramacionEntregaException extends RuntimeException
{
    public function __construct(string $message, private int $status = 422, private ?string $errorCode = null)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }
}
