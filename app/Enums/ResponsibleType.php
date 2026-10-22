<?php

namespace App\Enums;

/**
 * Cargos de responsable que firman la PECOSA y los reportes.
 */
enum ResponsibleType: string
{
    case Chief = 'chief';
    case Storekeeper = 'storekeeper';

    public function label(): string
    {
        return match ($this) {
            self::Chief => 'Subgerente de Programas Sociales',
            self::Storekeeper => 'Encargado de PROVALE',
        };
    }

    /** Mapa valor => etiqueta, usado por las vistas y los filtros. */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
