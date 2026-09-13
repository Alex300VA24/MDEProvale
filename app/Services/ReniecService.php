<?php

namespace App\Services;

use App\Exceptions\ReniecException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ReniecService
{
    /**
     * Consulta individual de una persona natural en RENIEC WS2 mediante PIDE.
     */
    public function consultar(string $dni): array
    {
        $this->ensureConfigured();

        $response = $this->post((string) config('services.reniec.consultar_url'), [
            'PIDE' => [
                'nuDniConsulta' => $dni,
                'nuDniUsuario' => (string) config('services.reniec.dni_usuario'),
                'nuRucUsuario' => (string) config('services.reniec.ruc_usuario'),
                'password' => (string) config('services.reniec.password'),
            ],
        ]);

        $payload = $this->resultPayload($this->json($response));
        $code = (string) ($payload['coResultado'] ?? '');

        if ($code !== '0000') {
            throw $this->resultException($code, $payload['deResultado'] ?? null);
        }

        $person = is_array($payload['datosPersona'] ?? null) ? $payload['datosPersona'] : [];

        return [
            'dni' => $dni,
            'names' => $this->text($person['prenombres'] ?? null),
            'father_lastname' => $this->text($person['apPrimer'] ?? null),
            'mother_lastname' => $this->text($person['apSegundo'] ?? null),
            'marital_status' => $this->text($person['estadoCivil'] ?? null),
            'ubigeo' => $this->text($person['ubigeo'] ?? null),
            'address' => $this->text($person['direccion'] ?? null),
            'restriction' => $this->text($person['restriccion'] ?? null),
            'photo' => $this->photoDataUri($person['foto'] ?? null),
        ];
    }

    /**
     * Actualiza la credencial del usuario consultor en RENIEC WS2.
     * La aplicación debe actualizar RENIEC_PASSWORD después de una respuesta exitosa.
     */
    public function actualizarCredencial(string $credencialAnterior, string $credencialNueva): array
    {
        $this->ensureIdentityConfigured();

        $response = $this->post((string) config('services.reniec.actualizar_url'), [
            'PIDE' => [
                'credencialAnterior' => $credencialAnterior,
                'credencialNueva' => $credencialNueva,
                'nuDni' => (string) config('services.reniec.dni_usuario'),
                'nuRuc' => (string) config('services.reniec.ruc_usuario'),
            ],
        ]);

        $payload = $this->resultPayload($this->json($response));
        $code = isset($payload['coResultado']) ? (string) $payload['coResultado'] : null;

        if ($code !== null && $code !== '0000') {
            throw $this->resultException($code, $payload['deResultado'] ?? null);
        }

        return $payload;
    }

    private function post(string $url, array $payload): Response
    {
        if ($url === '') {
            throw new ReniecException('El servicio RENIEC no está configurado.', 503);
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->contentType('application/json; charset=UTF-8')
                ->connectTimeout((int) config('services.reniec.connect_timeout', 5))
                ->timeout((int) config('services.reniec.timeout', 15))
                ->post($url, $payload);
        } catch (ConnectionException) {
            throw new ReniecException('No se pudo conectar con RENIEC. Intente nuevamente.', 503);
        }

        if (! $response->successful()) {
            throw new ReniecException('RENIEC no pudo procesar la consulta. Intente nuevamente.', 502);
        }

        return $response;
    }

    private function json(Response $response): array
    {
        $json = $response->json();

        if (! is_array($json)) {
            throw new ReniecException('RENIEC devolvió una respuesta inválida.', 502);
        }

        return $json;
    }

    /**
     * Admite respuesta directa y envoltorios frecuentes de servicios PIDE.
     */
    private function resultPayload(array $response): array
    {
        $queue = [$response];

        while ($queue !== []) {
            $current = array_shift($queue);

            if (array_key_exists('coResultado', $current) || array_key_exists('datosPersona', $current)) {
                return $current;
            }

            foreach ($current as $value) {
                if (is_array($value)) {
                    $queue[] = $value;
                }
            }
        }

        throw new ReniecException('RENIEC devolvió una respuesta no reconocida.', 502);
    }

    private function ensureConfigured(): void
    {
        $this->ensureIdentityConfigured();

        if (blank(config('services.reniec.password'))) {
            throw new ReniecException('Las credenciales RENIEC no están configuradas.', 503);
        }
    }

    private function ensureIdentityConfigured(): void
    {
        if (blank(config('services.reniec.dni_usuario')) || blank(config('services.reniec.ruc_usuario'))) {
            throw new ReniecException('Las credenciales RENIEC no están configuradas.', 503);
        }
    }

    private function resultException(string $code, mixed $description): ReniecException
    {
        [$status, $message] = match ($code) {
            '0001' => [422, 'El DNI corresponde a una persona menor de edad.'],
            '0999' => [404, 'RENIEC no encontró información para el DNI ingresado.'],
            '1000' => [422, 'RENIEC rechazó uno o más datos de la consulta.'],
            '1001' => [503, 'Las credenciales RENIEC no son válidas.'],
            '1002' => [503, 'La contraseña RENIEC está caducada.'],
            '1003' => [429, 'Se alcanzó el límite diario de 1,000 consultas RENIEC.'],
            default => [502, $this->text($description) ?: 'RENIEC no pudo completar la consulta.'],
        };

        return new ReniecException($message, $status, $code ?: null);
    }

    private function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * RENIEC puede devolver la foto como Base64 o como un arreglo de bytes.
     * Solo aceptamos imágenes JPEG válidas y con un tamaño razonable.
     */
    private function photoDataUri(mixed $value): ?string
    {
        if (is_array($value)) {
            if ($value === []) {
                return null;
            }

            $isByteArray = collect($value)->every(
                fn ($byte) => is_numeric($byte) && (int) $byte >= 0 && (int) $byte <= 255
            );

            if ($isByteArray) {
                $binary = '';
                foreach ($value as $byte) {
                    $binary .= chr((int) $byte);
                }

                return $this->jpegDataUri($binary);
            }

            if (collect($value)->every(fn ($chunk) => is_string($chunk))) {
                $value = implode('', $value);
            } else {
                return null;
            }
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $base64 = preg_replace('/^data:image\/jpeg;base64,/i', '', trim($value));
        $base64 = preg_replace('/\s+/', '', (string) $base64);
        $binary = base64_decode($base64, true);

        return $binary === false ? null : $this->jpegDataUri($binary);
    }

    private function jpegDataUri(string $binary): ?string
    {
        if (strlen($binary) > 2 * 1024 * 1024 || !str_starts_with($binary, "\xFF\xD8\xFF")) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($binary);
    }
}
