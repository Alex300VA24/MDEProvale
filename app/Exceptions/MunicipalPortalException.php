<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Fallo al consultar el portal de la Municipalidad de La Esperanza.
 * Lleva un motivo estable (reason) para que el frontend muestre una alerta específica.
 */
class MunicipalPortalException extends RuntimeException
{
    public const TIMEOUT = 'timeout';
    public const CONNECTION = 'connection';
    public const DNS = 'dns';
    public const SSL = 'ssl';
    public const BLOCKED = 'blocked';
    public const SERVER_ERROR = 'server_error';
    public const UNAVAILABLE = 'unavailable';
    public const PDF_INVALID = 'pdf_invalid';

    public function __construct(
        private readonly string $reason,
        string $message,
        private readonly int $httpStatus = 502,
        private readonly ?string $hint = null,
    ) {
        parent::__construct($message);
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function hint(): ?string
    {
        return $this->hint;
    }

    public function toPayload(): array
    {
        return [
            'success' => false,
            'reason' => $this->reason,
            'message' => $this->getMessage(),
            'hint' => $this->hint,
            'retryable' => $this->reason !== self::BLOCKED,
        ];
    }

    public static function fromConnection(\Throwable $e): self
    {
        $msg = strtolower($e->getMessage());

        return match (true) {
            str_contains($msg, 'curl error 28'), str_contains($msg, 'timed out'), str_contains($msg, 'timeout') => new self(
                self::TIMEOUT,
                'El portal de la Municipalidad tardó demasiado en responder.',
                504,
                'Puede ser lentitud de tu internet o del portal. Revisa tu conexión e inténtalo de nuevo.',
            ),
            str_contains($msg, 'curl error 6'), str_contains($msg, 'could not resolve') => new self(
                self::DNS,
                'No se pudo ubicar la dirección del portal municipal (muniesperanza.gob.pe).',
                502,
                'Probablemente el servidor no tiene internet o el DNS falla. Verifica la conexión a internet.',
            ),
            str_contains($msg, 'curl error 60'), str_contains($msg, 'curl error 35'), str_contains($msg, 'ssl') => new self(
                self::SSL,
                'No se pudo establecer una conexión segura con el portal municipal.',
                502,
                'El certificado del portal puede estar vencido o mal configurado. Inténtalo más tarde.',
            ),
            default => new self(
                self::CONNECTION,
                'No se pudo conectar con el portal de la Municipalidad.',
                502,
                'Verifica tu conexión a internet; si persiste, el portal puede estar caído.',
            ),
        };
    }

    public static function fromStatus(int $status): self
    {
        return match (true) {
            $status === 403, $status === 429 => new self(
                self::BLOCKED,
                'El portal municipal rechazó la consulta (HTTP '.$status.').',
                502,
                'El portal puede estar bloqueando consultas automáticas. Espera unos minutos o abre el portal manualmente.',
            ),
            $status >= 500 => new self(
                self::SERVER_ERROR,
                'El portal municipal presenta un error interno (HTTP '.$status.').',
                502,
                'El problema está en el portal, no en tu conexión. Inténtalo más tarde.',
            ),
            default => new self(
                self::UNAVAILABLE,
                'El portal municipal no está disponible o cambió su dirección (HTTP '.$status.').',
                502,
                'Verifica que el portal muniesperanza.gob.pe abra desde el navegador.',
            ),
        };
    }
}
