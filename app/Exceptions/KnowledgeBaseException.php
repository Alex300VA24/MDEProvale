<?php

namespace App\Exceptions;

use App\Models\KnowledgeBaseDocument;
use RuntimeException;

/**
 * Regla de negocio de la base de conocimiento incumplida, con el estado HTTP
 * que la capa de presentación debe devolver y, si aplica, el documento afectado.
 */
class KnowledgeBaseException extends RuntimeException
{
    public function __construct(
        string $message,
        private int $status = 422,
        private ?KnowledgeBaseDocument $document = null,
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function document(): ?KnowledgeBaseDocument
    {
        return $this->document;
    }
}
