<?php

namespace App\Support;

use App\Models\User;

/**
 * Qué módulos puede consultar el Asistente PROVALE en nombre de un usuario.
 *
 * El asistente solo orienta y entrega datos de los módulos a los que el rol del
 * usuario tiene acceso; sin acceso responde con un aviso en lugar de la guía o
 * las cifras. `unrestricted()` existe para pruebas unitarias sin base de datos.
 */
final class AssistantAccess
{
    private function __construct(private ?User $user)
    {
    }

    public static function for(User $user): self
    {
        return new self($user);
    }

    public static function unrestricted(): self
    {
        return new self(null);
    }

    public function allows(string $slug): bool
    {
        return $this->user === null || $this->user->canModule($slug, 'view');
    }

    /** @param  string[]  $slugs  basta con uno */
    public function allowsAny(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->allows($slug)) {
                return true;
            }
        }

        return false;
    }

    /** Aviso de acceso denegado, o null si el usuario sí puede usar el módulo. */
    public function denial(string $slug): ?string
    {
        if ($this->allows($slug)) {
            return null;
        }

        return 'Tu rol no tiene acceso a '.ModuleCatalog::path($slug).".\n"
            .'Si necesitas usar esta opción, solicita el permiso al administrador.';
    }

    /** @return string[] hojas del catálogo que el usuario puede ver */
    public function modules(): array
    {
        return array_values(array_filter(ModuleCatalog::leaves(), fn (string $slug) => $this->allows($slug)));
    }
}
