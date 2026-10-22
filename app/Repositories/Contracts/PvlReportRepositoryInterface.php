<?php

namespace App\Repositories\Contracts;

use App\Models\DistributionPeriod;
use App\Models\PvlDocument;
use App\Models\PvlReportRun;
use App\Models\Racion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Acceso a datos de los reportes PVL / Ración A: corridas de análisis,
 * documentos de respaldo y los datos fuente que alimentan el contexto.
 */
interface PvlReportRepositoryInterface
{
    // Corridas de análisis

    public function recentRuns(int $limit): Collection;

    public function createRun(array $data): PvlReportRun;

    /** Asigna los atributos sin pasar por fillable, guarda y devuelve la corrida refrescada. */
    public function updateRun(PvlReportRun $run, array $attributes): PvlReportRun;

    public function deleteRun(PvlReportRun $run): void;

    public function freshRun(PvlReportRun $run): PvlReportRun;

    /** Última corrida reutilizable con la misma huella de fuentes. */
    public function cachedRun(string $reportType, int $year, int $month, ?int $userId, string $fingerprint): ?PvlReportRun;

    /** Última corrida PVL validada del mes anterior. */
    public function previousRun(int $year, int $month): ?PvlReportRun;

    // Documentos de respaldo

    public function recentDocuments(int $limit): Collection;

    public function findDuplicateDocument(string $period, string $documentType, string $hash): ?PvlDocument;

    public function createDocument(array $data): PvlDocument;

    public function deleteDocument(PvlDocument $document): void;

    public function freshDocument(PvlDocument $document): PvlDocument;

    public function documentsForPeriod(string $period): Collection;

    // Datos fuente del contexto

    public function productOptions(): Collection;

    public function productTitleLike(string $pattern): ?string;

    /** Ingresos del rango, con producto y tipo de movimiento. */
    public function incomeTransactions(Carbon $start, Carbon $end): Collection;

    public function racionForMonth(int $year, int $month): ?Racion;

    public function distributionPeriod(int $year, int $month): ?DistributionPeriod;

    /** Detalles de pecosas entregadas dentro del rango. */
    public function pecosaDetailsBetween(Carbon $start, Carbon $end): Collection;

    /** Historial de beneficios vigente en el rango, un registro por beneficiario. */
    public function beneficiaryHistoriesBetween(Carbon $start, Carbon $end): Collection;
}
