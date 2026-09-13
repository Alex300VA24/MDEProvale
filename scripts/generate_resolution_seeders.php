<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$source = resolvePath($argv[1] ?? dirname(__DIR__).'/resoluciones_comites.xlsx');
$outputDirectory = resolvePath($argv[2] ?? dirname(__DIR__).'/database/seeders', false);

if (!is_file($source)) {
    fail("No existe el archivo fuente: {$source}");
}

if (!is_dir($outputDirectory)) {
    fail("No existe el directorio de seeders: {$outputDirectory}");
}

try {
    $rows = readRows($source);
    [$resolutions, $links, $creationLinks, $skippedRows] = transformRows($rows);

    $resolutionPath = $outputDirectory.DIRECTORY_SEPARATOR.'ResolutionSeeder.php';
    $associationPath = $outputDirectory.DIRECTORY_SEPARATOR.'ResolutionAssociationSeeder.php';
    $committeePath = $outputDirectory.DIRECTORY_SEPARATOR.'AssociationSeeder.php';
    [$committeeSeeder, $synchronizedCommittees] = synchronizeCreationLookups($committeePath, $creationLinks);

    writeFile($resolutionPath, renderResolutionSeeder($resolutions));
    writeFile($associationPath, renderResolutionAssociationSeeder($links));
    writeFile($committeePath, $committeeSeeder);

    printf(
        "Generados %d resoluciones, %d asociaciones históricas y %d vínculos de creación desde %s.%s",
        count($resolutions),
        count($links),
        $synchronizedCommittees,
        basename($source),
        PHP_EOL,
    );

    foreach ($skippedRows as $rowNumber) {
        fwrite(STDERR, "Fila {$rowNumber} omitida: no contiene documento.".PHP_EOL);
    }
} catch (Throwable $exception) {
    fail($exception->getMessage());
}

/**
 * @return list<array{row:int, code:string, name:string, number:int, type:string, document:?string, date_start:mixed, date_end:mixed}>
 */
function readRows(string $source): array
{
    $reader = IOFactory::createReaderForFile($source);
    $reader->setReadDataOnly(true);
    $workbook = $reader->load($source);
    $sheet = $workbook->getSheetByName('Resoluciones') ?? $workbook->getActiveSheet();
    $data = $sheet->toArray(null, true, true, true);

    $expectedHeaders = [
        'A' => 'Comité N°',
        'B' => 'Nombre del Comité',
        'C' => 'N° Resolución',
        'D' => 'Tipo',
        'E' => 'Documento',
        'F' => 'Fecha Inicio',
        'G' => 'Fecha Fin',
    ];

    foreach ($expectedHeaders as $column => $expected) {
        $actual = trim((string) ($data[1][$column] ?? ''));
        if ($actual !== $expected) {
            throw new RuntimeException(
                "Cabecera inválida en {$sheet->getTitle()}!{$column}1: se esperaba '{$expected}' y se encontró '{$actual}'.",
            );
        }
    }

    $rows = [];
    foreach (array_slice($data, 1, null, true) as $rowNumber => $row) {
        $code = trim((string) ($row['A'] ?? ''));
        $name = trim((string) ($row['B'] ?? ''));

        if ($code === '' && $name === '') {
            continue;
        }

        $number = filter_var($row['C'] ?? null, FILTER_VALIDATE_INT);
        if ($number === false || $number < 1) {
            throw new RuntimeException("Número de resolución inválido en fila {$rowNumber}.");
        }

        $rows[] = [
            'row' => (int) $rowNumber,
            'code' => str_pad($code, 3, '0', STR_PAD_LEFT),
            'name' => normalizeWhitespace($name),
            'number' => $number,
            'type' => normalizeWhitespace(trim((string) ($row['D'] ?? ''))),
            'document' => nullableString($row['E'] ?? null),
            'date_start' => $row['F'] ?? null,
            'date_end' => $row['G'] ?? null,
        ];
    }

    return $rows;
}

/**
 * @param list<array{row:int, code:string, name:string, number:int, type:string, document:?string, date_start:mixed, date_end:mixed}> $rows
 * @return array{0:list<array{document:string,date_start:string,date_end:string}>,1:list<array{code:string,name:string,number:int,document:string,date_start:string,date_end:string}>,2:array<string,array{document:string,date_start:string,date_end:string}>,3:list<int>}
 */
function transformRows(array $rows): array
{
    $resolutionsByKey = [];
    $links = [];
    $creationLinks = [];
    $skippedRows = [];

    foreach ($rows as $row) {
        if ($row['document'] === null) {
            $skippedRows[] = $row['row'];
            continue;
        }

        $expectedType = $row['number'] === 1 ? 'Creación' : 'Posterior';
        if (strcasecmp($row['type'], $expectedType) !== 0) {
            throw new RuntimeException(
                "Tipo '{$row['type']}' inválido para resolución {$row['number']} en fila {$row['row']}.",
            );
        }

        $dateStart = parseDate($row['date_start'], $row['row'], 'Fecha Inicio');
        $dateEnd = parseDate($row['date_end'], $row['row'], 'Fecha Fin');

        if ($dateStart > $dateEnd) {
            throw new RuntimeException("Rango de fechas invertido en fila {$row['row']}.");
        }

        $resolution = [
            'document' => $row['document'],
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
        ];
        $key = implode('|', $resolution);
        $resolutionsByKey[$key] = $resolution;

        if ($row['number'] === 1) {
            if (isset($creationLinks[$row['code']])) {
                throw new RuntimeException("El comité {$row['code']} tiene más de una resolución de creación documentada.");
            }

            $creationLinks[$row['code']] = $resolution;
        } else {
            $links[] = [
                'code' => $row['code'],
                'name' => $row['name'],
                'number' => $row['number'],
                'document' => $row['document'],
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
            ];
        }
    }

    return [array_values($resolutionsByKey), $links, $creationLinks, $skippedRows];
}

/**
 * @param array<string,array{document:string,date_start:string,date_end:string}> $creationLinks
 * @return array{0:string,1:int}
 */
function synchronizeCreationLookups(string $path, array $creationLinks): array
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException("No se pudo leer {$path}.");
    }

    $lines = preg_split('/\R/', $contents);
    if ($lines === false) {
        throw new RuntimeException("No se pudo analizar {$path}.");
    }

    $currentCode = null;
    $synchronizedCodes = [];

    foreach ($lines as &$line) {
        if (preg_match("/'code'\s*=>\s*'([^']+)'/", $line, $matches) === 1) {
            $currentCode = $matches[1];
        }

        if (
            $currentCode === null
            || !isset($creationLinks[$currentCode])
            || !str_contains($line, "'resolution_id'")
            || !str_contains($line, "DB::table('resolutions')")
        ) {
            continue;
        }

        $resolution = $creationLinks[$currentCode];
        $indentation = substr($line, 0, strlen($line) - strlen(ltrim($line)));
        $line = sprintf(
            "%s'resolution_id'    => DB::table('resolutions')->where('document', %s)->whereDate('date_start', %s)->whereDate('date_end', %s)->value('id'),",
            $indentation,
            exportString($resolution['document']),
            exportString($resolution['date_start']),
            exportString($resolution['date_end']),
        );
        $synchronizedCodes[$currentCode] = true;
    }
    unset($line);

    $missingCodes = array_diff(array_keys($creationLinks), array_keys($synchronizedCodes));
    if ($missingCodes !== []) {
        throw new RuntimeException(
            'Faltan comités en AssociationSeeder.php: '.implode(', ', $missingCodes).'.',
        );
    }

    return [implode(PHP_EOL, $lines), count($synchronizedCodes)];
}

/** @param list<array{document:string,date_start:string,date_end:string}> $resolutions */
function renderResolutionSeeder(array $resolutions): string
{
    $entries = [];
    foreach ($resolutions as $resolution) {
        $entries[] = sprintf(
            "            ['document' => %s, 'date_start' => %s, 'date_end' => %s],",
            exportString($resolution['document']),
            exportString($resolution['date_start']),
            exportString($resolution['date_end']),
        );
    }

    return sprintf(<<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResolutionSeeder extends Seeder
{
    /**
     * Resoluciones generadas desde resoluciones_comites.xlsx.
     */
    public function run(): void
    {
        $currentStateId = DB::table('states')->where('abbreviation', 'VIG')->value('id');
        $expiredStateId = DB::table('states')->where('abbreviation', 'VEN')->value('id');

        if (!$currentStateId || !$expiredStateId) {
            throw new RuntimeException('Faltan los estados VIG o VEN.');
        }

        $resolutions = [
%s
        ];
        $today = now()->toDateString();
        $timestamp = now();

        foreach ($resolutions as $resolution) {
            DB::table('resolutions')->updateOrInsert(
                $resolution,
                [
                    'state_id' => $resolution['date_end'] >= $today ? $currentStateId : $expiredStateId,
                    'file_path' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            );
        }
    }
}

PHP, implode(PHP_EOL, $entries));
}

/** @param list<array{code:string,name:string,number:int,document:string,date_start:string,date_end:string}> $links */
function renderResolutionAssociationSeeder(array $links): string
{
    $entries = [];
    foreach ($links as $link) {
        $comment = sprintf(
            '            // Comité %s - %s | Resolución %d',
            $link['code'],
            str_replace(["\r", "\n"], ' ', $link['name']),
            $link['number'],
        );
        $entry = sprintf(
            "            ['code' => %s, 'document' => %s, 'date_start' => %s, 'date_end' => %s],",
            exportString($link['code']),
            exportString($link['document']),
            exportString($link['date_start']),
            exportString($link['date_end']),
        );
        $entries[] = $comment.PHP_EOL.$entry;
    }

    return sprintf(<<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResolutionAssociationSeeder extends Seeder
{
    /**
     * Resoluciones posteriores generadas desde resoluciones_comites.xlsx.
     * La resolución 1 se asigna en AssociationSeeder como resolution_id.
     */
    public function run(): void
    {
        $links = [
%s
        ];

        DB::table('resolution_associations')->delete();

        foreach ($links as $link) {
            $associationId = DB::table('associations')->where('code', $link['code'])->value('id');
            $resolutionId = DB::table('resolutions')
                ->where('document', $link['document'])
                ->whereDate('date_start', $link['date_start'])
                ->whereDate('date_end', $link['date_end'])
                ->value('id');

            if (!$associationId || !$resolutionId) {
                throw new RuntimeException(
                    "No se pudo vincular el comité {$link['code']} con la resolución {$link['document']}.",
                );
            }

            DB::table('resolution_associations')->updateOrInsert(
                [
                    'resolution_id' => $resolutionId,
                    'association_id' => $associationId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}

PHP, implode(PHP_EOL, $entries));
}

function parseDate(mixed $value, int $rowNumber, string $columnName): string
{
    if (is_numeric($value)) {
        return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
    }

    $text = trim((string) $value);
    $text = preg_replace('/^(\d{1,2})\/0(\d{2})\/(\d{4})$/', '$1/$2/$3', $text) ?? $text;

    foreach (['!d/m/Y', '!Y-m-d'] as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $text);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            return $date->format('Y-m-d');
        }
    }

    throw new RuntimeException("{$columnName} inválida en fila {$rowNumber}: '{$text}'.");
}

function nullableString(mixed $value): ?string
{
    $text = normalizeWhitespace(trim((string) $value));

    return $text === '' ? null : $text;
}

function normalizeWhitespace(string $value): string
{
    return preg_replace('/\s+/u', ' ', $value) ?? $value;
}

function exportString(string $value): string
{
    return var_export($value, true);
}

function writeFile(string $path, string $contents): void
{
    $bytes = file_put_contents($path, $contents);
    if ($bytes === false) {
        throw new RuntimeException("No se pudo escribir {$path}.");
    }
}

function resolvePath(string $path, bool $mustExist = true): string
{
    $resolved = realpath($path);
    if ($resolved !== false) {
        return $resolved;
    }

    if ($mustExist) {
        return $path;
    }

    return rtrim($path, '\\/');
}

function fail(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}
