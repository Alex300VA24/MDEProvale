<?php

namespace App\Console\Support;

use App\Services\Maintenance\ProgressReporter;
use Illuminate\Console\Command;

/** Adapta un comando Artisan al canal de salida que usan los servicios de mantenimiento. */
class CommandReporter implements ProgressReporter
{
    public function __construct(private Command $command)
    {
    }

    public function info(string $message): void
    {
        $this->command->info($message);
    }

    public function warn(string $message): void
    {
        $this->command->warn($message);
    }

    public function error(string $message): void
    {
        $this->command->error($message);
    }

    public function line(string $message): void
    {
        $this->command->line($message);
    }

    public function newLine(int $count = 1): void
    {
        $this->command->newLine($count);
    }

    public function table(array $headers, array $rows): void
    {
        $this->command->table($headers, $rows);
    }

    public function progressStart(int $total): void
    {
        $this->command->getOutput()->progressStart($total);
    }

    public function progressAdvance(): void
    {
        $this->command->getOutput()->progressAdvance();
    }

    public function progressFinish(): void
    {
        $this->command->getOutput()->progressFinish();
    }
}
