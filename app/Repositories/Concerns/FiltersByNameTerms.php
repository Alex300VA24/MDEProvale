<?php

namespace App\Repositories\Concerns;

trait FiltersByNameTerms
{
    /**
     * Filtra por nombre completo (nombres + apellidos) buscando cada palabra
     * en cualquiera de las columnas de nombre/DNI, en cualquier orden.
     */
    private function applyNameTerms($query, array $terms, string $prefix = ''): void
    {
        if ($terms === []) {
            return;
        }

        $query->where(function ($outer) use ($terms, $prefix) {
            foreach ($terms as $term) {
                $pattern = '%' . addcslashes($term, '\\%_') . '%';

                $outer->where(function ($qb) use ($pattern, $prefix) {
                    $qb->where("{$prefix}names", 'like', $pattern)
                        ->orWhere("{$prefix}father_lastname", 'like', $pattern)
                        ->orWhere("{$prefix}mother_lastname", 'like', $pattern)
                        ->orWhere("{$prefix}dni", 'like', $pattern);
                });
            }
        });
    }
}
