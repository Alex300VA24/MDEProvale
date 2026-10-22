<?php

namespace App\Services\Maintenance;

/**
 * Canal de salida de los procesos de mantenimiento. La capa de presentación
 * (comandos Artisan) lo implementa; los servicios no conocen la consola.
 */
interface ProgressReporter
{
    public function info(string $message): void;

    public function warn(string $message): void;

    public function error(string $message): void;

    public function line(string $message): void;

    public function newLine(int $count = 1): void;

    public function table(array $headers, array $rows): void;

    public function progressStart(int $total): void;

    public function progressAdvance(): void;

    public function progressFinish(): void;
}
