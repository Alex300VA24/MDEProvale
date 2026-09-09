<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Parámetros del límite de consultas del asistente, editables por el
 * administrador desde Sistema > Asistente IA. Si no hay valores guardados se
 * usan los predeterminados (5 consultas cada 3 horas).
 */
class AssistantSettingsService
{
    public const CLAVE_MAX = 'assistant_max_consultas';
    public const CLAVE_VENTANA = 'assistant_ventana_horas';

    public const MAX_DEFECTO = 5;
    public const VENTANA_HORAS_DEFECTO = 3;

    public const MAX_MINIMO = 1;
    public const MAX_MAXIMO = 100;
    public const VENTANA_MINIMA = 1;
    public const VENTANA_MAXIMA = 72;

    public function maxConsultas(): int
    {
        $valor = (int) Setting::get(self::CLAVE_MAX, self::MAX_DEFECTO);

        return max(self::MAX_MINIMO, min(self::MAX_MAXIMO, $valor));
    }

    public function ventanaHoras(): int
    {
        $valor = (int) Setting::get(self::CLAVE_VENTANA, self::VENTANA_HORAS_DEFECTO);

        return max(self::VENTANA_MINIMA, min(self::VENTANA_MAXIMA, $valor));
    }

    public function ventanaSegundos(): int
    {
        return $this->ventanaHoras() * 3600;
    }

    /**
     * @return array{max_consultas:int,ventana_horas:int}
     */
    public function all(): array
    {
        return [
            'max_consultas' => $this->maxConsultas(),
            'ventana_horas' => $this->ventanaHoras(),
        ];
    }

    public function update(int $maxConsultas, int $ventanaHoras): array
    {
        $maxConsultas = max(self::MAX_MINIMO, min(self::MAX_MAXIMO, $maxConsultas));
        $ventanaHoras = max(self::VENTANA_MINIMA, min(self::VENTANA_MAXIMA, $ventanaHoras));

        Setting::put(self::CLAVE_MAX, $maxConsultas);
        Setting::put(self::CLAVE_VENTANA, $ventanaHoras);

        return ['max_consultas' => $maxConsultas, 'ventana_horas' => $ventanaHoras];
    }
}
