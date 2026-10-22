<?php

namespace App\Exceptions;

use App\Models\PvlDocument;
use RuntimeException;

/**
 * Regla de negocio incumplida en los reportes PVL. El controlador la traduce
 * a JSON con el código HTTP indicado.
 */
class PvlReportException extends RuntimeException
{
    public function __construct(
        string $message,
        private int $status = 422,
        private ?string $errorCode = null,
        private array $errors = [],
        private ?PvlDocument $document = null,
    ) {
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

    public function errors(): array
    {
        return $this->errors;
    }

    public function document(): ?PvlDocument
    {
        return $this->document;
    }
}
