<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Verifica la regla de dependencia unidireccional de la arquitectura N capas:
 * Presentación (Http/Console) -> Negocio (Services) -> Datos (Repositories) -> Entidades (Models).
 *
 * Las excepciones viven en architecture-allowlist.php. Cada refactor de dominio
 * debe retirar sus archivos de esa lista; la lista nunca debe crecer.
 */
class ArchitectureTest extends TestCase
{
    private const EXPOSED_BUILDER_METHODS = [
        'query', 'where', 'whereIn', 'whereRaw', 'whereHas', 'whereKey', 'whereDate', 'whereNull',
        'find', 'findOrFail', 'findMany', 'create', 'insert', 'upsert', 'destroy', 'with', 'withCount',
        'firstOrCreate', 'firstOrNew', 'firstOrFail', 'first', 'updateOrCreate', 'select', 'selectRaw',
        'all', 'orderBy', 'latest', 'oldest', 'pluck', 'count', 'exists', 'has', 'join', 'truncate',
    ];

    public function test_presentation_layer_does_not_query_the_database(): void
    {
        $this->assertNoViolations(['app/Http/Controllers', 'app/Http/Middleware', 'app/Console/Commands'], 'presentation', true);
    }

    public function test_business_layer_does_not_query_the_database(): void
    {
        $this->assertNoViolations(['app/Services'], 'business', true);
    }

    public function test_business_and_data_layers_do_not_depend_on_http(): void
    {
        $offenders = [];
        foreach ($this->phpFiles(['app/Services', 'app/Repositories']) as $relative => $code) {
            if (preg_match('/^use\s+(Illuminate\\\\Http\\\\Request|Inertia\\\\|Illuminate\\\\Http\\\\JsonResponse);/m', $code)) {
                $offenders[] = $relative;
            }
        }

        $allowed = $this->allowlist()['http_in_lower_layers'] ?? [];
        $this->assertSame([], array_values(array_diff($offenders, $allowed)), 'Servicios/Repositorios dependen de HTTP.');
    }

    public function test_data_layer_does_not_depend_on_upper_layers(): void
    {
        $offenders = [];
        foreach ($this->phpFiles(['app/Repositories']) as $relative => $code) {
            if (preg_match('/^use\s+App\\\\(Services|Http)\\\\/m', $code)) {
                $offenders[] = $relative;
            }
        }

        $this->assertSame([], $offenders, 'Repositorios dependen de capas superiores.');
    }

    public function test_allowlist_has_no_stale_entries(): void
    {
        $stale = [];
        foreach (['presentation', 'business'] as $key) {
            foreach ($this->allowlist()[$key] ?? [] as $relative) {
                $path = dirname(__DIR__, 2) . '/' . $relative;
                if (! is_file($path) || ! $this->hasDirectQuery(file_get_contents($path))) {
                    $stale[] = $relative;
                }
            }
        }

        $this->assertSame([], $stale, 'Retira de architecture-allowlist.php los archivos ya refactorizados.');
    }

    private function assertNoViolations(array $dirs, string $key, bool $queries): void
    {
        $allowed = $this->allowlist()[$key] ?? [];
        $offenders = [];
        foreach ($this->phpFiles($dirs) as $relative => $code) {
            if ($this->hasDirectQuery($code) && ! in_array($relative, $allowed, true)) {
                $offenders[] = $relative;
            }
        }

        $this->assertSame([], $offenders, 'Acceso directo a BD fuera de la capa de datos.');
    }

    private function hasDirectQuery(string $code): bool
    {
        $methods = implode('|', self::EXPOSED_BUILDER_METHODS);
        $models = implode('|', $this->modelNames());

        // DB::transaction es la unidad de trabajo de la capa de negocio y se permite.
        return (bool) preg_match('/\bDB::(?!transaction|beginTransaction|commit|rollBack)/', $code)
            || (bool) preg_match('/\b(' . $models . ')::(' . $methods . ')\(/', $code);
    }

    /** @return string[] */
    private function modelNames(): array
    {
        static $names = null;
        if ($names === null) {
            $names = array_map(
                fn ($f) => basename($f, '.php'),
                glob(dirname(__DIR__, 2) . '/app/Models/*.php')
            );
        }

        return $names;
    }

    /** @return array<string,string> ruta relativa => código */
    private function phpFiles(array $dirs): array
    {
        $root = dirname(__DIR__, 2);
        $files = [];
        foreach ($dirs as $dir) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir", RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->getExtension() === 'php') {
                    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                    $files[$relative] = file_get_contents($file->getPathname());
                }
            }
        }

        return $files;
    }

    private function allowlist(): array
    {
        return require __DIR__ . '/architecture-allowlist.php';
    }
}
