<?php

namespace App\Support;

/**
 * Catálogo oficial de módulos y submódulos del sistema.
 *
 * Es la única fuente de verdad del árbol de navegación/permisos: la usan el
 * seeder y los tests. Los permisos (ver/crear/editar/eliminar) se asignan a las
 * hojas; un módulo "grupo" (con hijos) no guarda permisos propios, su acceso se
 * deriva de los submódulos que el rol puede ver.
 *
 * La migración 2026_11_17_000001 mantiene una copia literal de este árbol para
 * no depender de código que pueda cambiar después.
 */
final class ModuleCatalog
{
    // Socios y Beneficiarios.
    public const SOCIOS_BENEFICIARIOS = 'socios-beneficiarios';
    public const REGISTRO_SOCIOS_BENEFICIARIOS = 'registro-socios-beneficiarios';
    public const PADRONES = 'padrones';

    // Módulos independientes (sin submódulos).
    public const SUPERVISION_COMITES = 'supervision-comites';
    public const EVALUACION_NUTRICIONAL = 'evaluacion-nutricional';
    public const RESPONSABLES_RACIONES = 'responsables-raciones';
    public const REPORTES = 'reportes';
    public const PERFIL = 'perfil';
    public const SISTEMA = 'sistema';
    public const AYUDA = 'ayuda';
    public const BASE_CONOCIMIENTO = 'base-conocimiento';

    // Comités y Resoluciones (Club de Madres = Comité; Reconocimiento = Resolución).
    public const COMITES_RESOLUCIONES = 'comites-resoluciones';
    public const COMITES = 'club-madres';
    public const RESOLUCIONES = 'reconocimientos';

    // Productos y Programación de Entrega.
    public const PRODUCTOS_PROGRAMACION = 'productos-programacion';
    public const PRODUCTOS = 'productos';
    public const REGISTRO_INGRESO = 'registro-ingreso';
    public const PROGRAMACION_ENTREGA = 'programacion-entrega';

    // Pecosas y Repartición.
    public const PECOSAS_REPARTICION = 'pecosas-reparticion';
    public const PECOSAS = 'pecosas';
    public const REPARTICION = 'reparticion';

    /** Slug del módulo retirado: sus permisos se repartieron entre Registro de Ingreso y Repartición. */
    public const LEGACY_MOVIMIENTOS = 'movimientos';

    /**
     * @return array<int, array{slug:string,name:string,description:string,icon:string,route:?string,order:int,parent:?string}>
     */
    public static function modules(): array
    {
        return [
            ['slug' => self::SOCIOS_BENEFICIARIOS, 'name' => 'Socios y Beneficiarios', 'description' => 'Registro familiar y gestión histórica de padrones', 'icon' => 'fa-users', 'route' => 'socios-beneficiarios', 'order' => 1, 'parent' => null],
            ['slug' => self::REGISTRO_SOCIOS_BENEFICIARIOS, 'name' => 'Registro de Socios y Beneficiarios', 'description' => 'Gestión de socios, beneficiarios, fichas y documentos familiares', 'icon' => 'fa-people-roof', 'route' => 'socios-beneficiarios', 'order' => 1, 'parent' => self::SOCIOS_BENEFICIARIOS],
            ['slug' => self::PADRONES, 'name' => 'Padrones', 'description' => 'Generación, cierre, revisión e historial de padrones mensuales', 'icon' => 'fa-clipboard-list', 'route' => 'socios-beneficiarios', 'order' => 2, 'parent' => self::SOCIOS_BENEFICIARIOS],

            ['slug' => self::COMITES_RESOLUCIONES, 'name' => 'Comités y Resoluciones', 'description' => 'Comités (Club de Madres) y resoluciones de reconocimiento', 'icon' => 'fa-people-group', 'route' => 'comites', 'order' => 2, 'parent' => null],
            ['slug' => self::COMITES, 'name' => 'Comités', 'description' => 'Gestión de comités (Club de Madres)', 'icon' => 'fa-female', 'route' => 'club-reconocimientos', 'order' => 1, 'parent' => self::COMITES_RESOLUCIONES],
            ['slug' => self::RESOLUCIONES, 'name' => 'Resoluciones', 'description' => 'Gestión de resoluciones de reconocimiento', 'icon' => 'fa-award', 'route' => 'club-reconocimientos', 'order' => 2, 'parent' => self::COMITES_RESOLUCIONES],

            ['slug' => self::PRODUCTOS_PROGRAMACION, 'name' => 'Productos y Programación de Entrega', 'description' => 'Productos, ingresos al almacén y programación de entrega', 'icon' => 'fa-boxes-stacked', 'route' => 'productos', 'order' => 3, 'parent' => null],
            ['slug' => self::PRODUCTOS, 'name' => 'Productos', 'description' => 'Gestión de productos', 'icon' => 'fa-box', 'route' => 'productos-pecosas', 'order' => 1, 'parent' => self::PRODUCTOS_PROGRAMACION],
            ['slug' => self::REGISTRO_INGRESO, 'name' => 'Registro de Ingreso', 'description' => 'Registro de ingresos de productos al almacén', 'icon' => 'fa-dolly', 'route' => 'movimientos', 'order' => 2, 'parent' => self::PRODUCTOS_PROGRAMACION],
            ['slug' => self::PROGRAMACION_ENTREGA, 'name' => 'Programación de Entrega', 'description' => 'Programación mensual de entrega por comité', 'icon' => 'fa-calendar-check', 'route' => 'productos-pecosas', 'order' => 3, 'parent' => self::PRODUCTOS_PROGRAMACION],

            ['slug' => self::PECOSAS_REPARTICION, 'name' => 'Pecosas y Repartición', 'description' => 'Pecosas y repartición mensual de raciones', 'icon' => 'fa-file-signature', 'route' => 'pecosas', 'order' => 4, 'parent' => null],
            ['slug' => self::PECOSAS, 'name' => 'Pecosas', 'description' => 'Gestión de pecosas', 'icon' => 'fa-file-alt', 'route' => 'productos-pecosas', 'order' => 1, 'parent' => self::PECOSAS_REPARTICION],
            ['slug' => self::REPARTICION, 'name' => 'Repartición', 'description' => 'Repartición mensual de raciones por comité', 'icon' => 'fa-truck-ramp-box', 'route' => 'movimientos', 'order' => 2, 'parent' => self::PECOSAS_REPARTICION],

            ['slug' => self::SUPERVISION_COMITES, 'name' => 'Supervisión de Comités', 'description' => 'Visitas de supervisión a los comités', 'icon' => 'fa-clipboard-check', 'route' => 'supervision-comites', 'order' => 5, 'parent' => null],
            ['slug' => self::EVALUACION_NUTRICIONAL, 'name' => 'Evaluación Nutricional', 'description' => 'Evaluación nutricional y clasificación poblacional', 'icon' => 'fa-heart-pulse', 'route' => 'evaluacion-nutricional', 'order' => 6, 'parent' => null],
            ['slug' => self::RESPONSABLES_RACIONES, 'name' => 'Responsables y Raciones', 'description' => 'Gestión de responsables del programa y raciones por año', 'icon' => 'fa-sliders', 'route' => 'responsables-raciones', 'order' => 7, 'parent' => null],
            ['slug' => self::REPORTES, 'name' => 'Reportes', 'description' => 'Reportes del sistema', 'icon' => 'fa-chart-bar', 'route' => null, 'order' => 8, 'parent' => null],
            ['slug' => self::PERFIL, 'name' => 'Perfil', 'description' => 'Datos y contraseña del usuario', 'icon' => 'fa-user', 'route' => 'perfil', 'order' => 9, 'parent' => null],
            ['slug' => self::SISTEMA, 'name' => 'Sistema', 'description' => 'Configuración del sistema', 'icon' => 'fa-cogs', 'route' => 'sistema', 'order' => 10, 'parent' => null],
            ['slug' => self::AYUDA, 'name' => 'Ayuda', 'description' => 'Guías de uso del sistema', 'icon' => 'fa-circle-question', 'route' => 'ayuda', 'order' => 11, 'parent' => null],

            // Vive dentro de Sistema (pestaña) y no tiene ítem propio en el menú.
            ['slug' => self::BASE_CONOCIMIENTO, 'name' => 'Base de Conocimiento IA', 'description' => 'Documentos indexados con IA para consultas tipo RAG', 'icon' => 'fa-database', 'route' => 'base-conocimiento', 'order' => 12, 'parent' => null],
        ];
    }

    /** @return string[] slugs de todos los módulos del catálogo */
    public static function slugs(): array
    {
        return array_column(self::modules(), 'slug');
    }

    /** Los módulos del catálogo están cableados en rutas, políticas y menú: no se renombran ni se eliminan. */
    public static function isBuiltIn(string $slug): bool
    {
        return in_array($slug, self::slugs(), true);
    }

    /** Slug del grupo al que pertenece un submódulo del catálogo, o null. */
    public static function parentOf(string $slug): ?string
    {
        foreach (self::modules() as $module) {
            if ($module['slug'] === $slug) {
                return $module['parent'];
            }
        }

        return null;
    }

    /** Ruta de menú legible: "Grupo > Submódulo" (o solo el nombre si es de primer nivel). */
    public static function path(string $slug): string
    {
        $byslug = array_column(self::modules(), null, 'slug');
        $module = $byslug[$slug] ?? null;
        if (! $module) {
            return $slug;
        }

        return $module['parent'] ? $byslug[$module['parent']]['name'].' > '.$module['name'] : $module['name'];
    }

    /** @return string[] slugs de módulos que agrupan submódulos */
    public static function groups(): array
    {
        return array_values(array_unique(array_filter(array_column(self::modules(), 'parent'))));
    }

    /** @return string[] slugs de las hojas (módulos que sí guardan permisos) */
    public static function leaves(): array
    {
        $groups = self::groups();

        return array_values(array_diff(array_column(self::modules(), 'slug'), $groups));
    }

    /** @return string[] slugs de los submódulos de un grupo */
    public static function childrenOf(string $group): array
    {
        return array_values(array_column(
            array_filter(self::modules(), fn (array $m) => $m['parent'] === $group),
            'slug'
        ));
    }

    /** Hojas operativas: todo salvo Sistema y la Base de Conocimiento (administración). */
    public static function operationalLeaves(): array
    {
        return array_values(array_diff(self::leaves(), [self::SISTEMA, self::BASE_CONOCIMIENTO]));
    }
}
