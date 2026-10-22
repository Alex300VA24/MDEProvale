<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Regla de negocio del módulo Sistema incumplida. La capa de presentación la
 * traduce a la respuesta HTTP con `status`.
 */
class SistemaException extends RuntimeException
{
    public function __construct(string $message, private int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
