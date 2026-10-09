<?php

namespace App\Services;

use App\DTOs\PecosaSnapshotDTO;
use App\Models\Association;
use App\Models\AssociationRosterPeriod;
use App\Models\DetailPecosa;
use App\Models\Pecosa;
use App\Models\VerifiedDocument;
use App\Models\Responsible;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Repositories\PartnerRepository;
use App\Repositories\PecosaRepository;
use App\Repositories\ProductRepository;
use Barryvdh\DomPDF\Facade\PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PecosaService
{
    private PecosaRepository $pecosaRepo;
    private ProductRepository $productRepo;
    private PartnerRepository $partnerRepo;
    private StockService $stockService;
    private PDFService $pdfService;
    private VerifiedDocumentService $verifiedDocumentService;

    public function __construct(PecosaRepository $pecosaRepo, ProductRepository $productRepo, PartnerRepository $partnerRepo, StockService $stockService, PDFService $pdfService, VerifiedDocumentService $verifiedDocumentService)
    {
        $this->pecosaRepo = $pecosaRepo;
        $this->productRepo = $productRepo;
        $this->partnerRepo = $partnerRepo;
        $this->stockService = $stockService;
        $this->pdfService = $pdfService;
        $this->verifiedDocumentService = $verifiedDocumentService;
    }

    public function searchWithFilters(array $filters, int $perPage = 10)
    {
        return $this->pecosaRepo->searchWithFilters($filters, $perPage);
    }

    public function nextPecosaNumber(bool $lock = false): string
    {
        $prefix = now()->format('y');
        $query = Pecosa::query()
            ->where('pecosa_number', 'like', $prefix . '%')
            ->select('pecosa_number');

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastSequence = $query->get()
            ->pluck('pecosa_number')
            ->filter(fn ($number) => preg_match('/^' . $prefix . '\\d{4}$/', (string) $number))
            ->map(fn ($number) => (int) substr((string) $number, 2, 4))
            ->max() ?? 0;

        if ($lastSequence >= 9999) {
            throw new \DomainException('Se agotó el correlativo de PECOSAs para este año.');
        }

        return $prefix . str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
    }

    public function createPecosa(array $data): Pecosa
    {
        $association = Association::findOrFail($data['association_id']);
        $president = $this->resolvePresidentForDeliveryPeriod($data);
        if (!$association->isHabilitado()
            && empty($data['managing_partner_id'])
            && empty($president['name'])) {
            throw new \DomainException('La asociación no está vigente. Renueve su resolución antes de registrar la PECOSA.');
        }

        $detailProductIds = collect($data['details'])->pluck('detail_product_id');
        if ($detailProductIds->count() !== $detailProductIds->unique()->count()) {
            throw new \DomainException('No se permiten productos duplicados en la misma PECOSA.');
        }

        $detailProductsById = $this->productRepo->getDetailProductsByIds($detailProductIds);

        foreach ($data['details'] as $detail) {
            $dp = $detailProductsById->get($detail['detail_product_id']);
            if (!$dp) throw new \DomainException('Detalle de producto no encontrado.');
            $available = $dp->quantity - ($dp->used_quantity ?? 0);
            if ($available < $detail['quantity']) {
                throw new \DomainException("Stock insuficiente para {$dp->product->title}. Disponible: {$available}, Solicitado: {$detail['quantity']}");
            }
        }

        return DB::transaction(function () use ($data, $detailProductsById, $president) {
            $data['pecosa_number'] = $this->nextPecosaNumber(true);
            $data['president_id'] = $president['partner_id'];
            $data['managing_partner_id'] = $president['partner_id'];
            $snapshot = $this->buildPecosaSnapshotDTO($data, $president);
            $pecosa = Pecosa::create(array_merge($data, $snapshot->toArray()));

            $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->first();

            foreach ($data['details'] as $index => $detail) {
                $dp = $detailProductsById->get($detail['detail_product_id']);
                $unitPrice = $dp->unit_price;
                $subtotal = $detail['quantity'] * $unitPrice;

                DetailPecosa::create([
                    'pecosa_id' => $pecosa->id,
                    'detail_product_id' => $detail['detail_product_id'],
                    'quantity' => $detail['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'priority' => $index + 1,
                    'product_name' => $dp->product->title,
                    'product_abbreviation' => $dp->product->abbreviation,
                    'uom_title' => $dp->product->uom->title ?? null,
                ]);

                $transaction = Transaction::create([
                    'detail_product_id' => $detail['detail_product_id'],
                    'type_transaction_id' => $typeSalida->id,
                    'quantity' => $detail['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $subtotal,
                    'document_number' => $data['pecosa_number'],
                    'transaction_date' => $data['delivery_date'],
                    'product_name' => $dp->product->title,
                    'uom_title' => $dp->product->uom->title ?? null,
                ]);

                $this->stockService->deductByDetailProduct(
                    $detail['detail_product_id'],
                    $detail['quantity'],
                    $pecosa->id,
                    $transaction->id
                );
            }

            return $pecosa;
        });
    }

    public function updatePecosa(int $id, array $data): Pecosa
    {
        $pecosa = Pecosa::findOrFail($id);

        return DB::transaction(function () use ($pecosa, $data) {
            $this->stockService->revertStockByPecosa($pecosa->id);
            DetailPecosa::where('pecosa_id', $pecosa->id)->delete();
            Transaction::where('document_number', $pecosa->pecosa_number)->delete();

            $president = $this->resolvePresidentForDeliveryPeriod($data);
            $data['president_id'] = $president['partner_id'];
            $data['managing_partner_id'] = $president['partner_id'];
            $snapshot = $this->buildPecosaSnapshotDTO($data, $president);
            $pecosa->update(array_merge($data, $snapshot->toArray()));

            $detailProductsById = $this->productRepo->getDetailProductsByIds(collect($data['details'])->pluck('detail_product_id'));
            $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->first();

            foreach ($data['details'] as $index => $detail) {
                $dp = $detailProductsById->get($detail['detail_product_id']);
                $unitPrice = $dp->unit_price;
                $subtotal = $detail['quantity'] * $unitPrice;

                DetailPecosa::create([
                    'pecosa_id' => $pecosa->id,
                    'detail_product_id' => $detail['detail_product_id'],
                    'quantity' => $detail['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'priority' => $index + 1,
                    'product_name' => $dp->product->title,
                    'product_abbreviation' => $dp->product->abbreviation,
                    'uom_title' => $dp->product->uom->title ?? null,
                ]);

                $transaction = Transaction::create([
                    'detail_product_id' => $detail['detail_product_id'],
                    'type_transaction_id' => $typeSalida->id,
                    'quantity' => $detail['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $subtotal,
                    'document_number' => $data['pecosa_number'],
                    'transaction_date' => $data['delivery_date'],
                    'product_name' => $dp->product->title,
                    'uom_title' => $dp->product->uom->title ?? null,
                ]);

                $this->stockService->deductByDetailProduct(
                    $detail['detail_product_id'],
                    $detail['quantity'],
                    $pecosa->id,
                    $transaction->id
                );
            }

            return $pecosa->fresh();
        });
    }

    public function generateComprobante(Pecosa $pecosa, ?int $createdBy = null): array
    {
        $pecosa->load([
            'detailPecosas.detailProduct.product.uom',
            'association.placeSector.place',
        ]);

        $data = $this->buildComprobanteData($pecosa, false);
        $identifier = 'PEC-' . Str::upper(Str::slug((string) $pecosa->pecosa_number)) . '-' . Str::upper(Str::random(6));

        return $this->verifiedDocumentService->issue(
            VerifiedDocument::TYPE_PECOSA_RECEIPT,
            $identifier,
            [
                'pecosa' => $pecosa->pecosa_number,
                'comite' => $pecosa->association_name ?: ($pecosa->association->name ?? ''),
                'fecha_entrega' => optional($pecosa->delivery_date)->format('Y-m-d'),
            ],
            'comprobante_salida',
            $data,
            'comprobante-salida-' . $pecosa->pecosa_number . '.pdf',
            $createdBy,
            'a4',
            'landscape'
        );
    }

    public function generatePecosaCompleta(Pecosa $pecosa): array
    {
        $pecosa->load([
            'detailPecosas.detailProduct.product.uom',
            'association.placeSector.place',
        ]);

        $data = $this->buildComprobanteData($pecosa, true);
        $pdf = $this->pdfService->generate('comprobante_salida', $data, 'a4', 'landscape');
        $filename = Str::slug('pecosa-completa-' . $pecosa->pecosa_number) . '.pdf';

        return [null, $pdf->output(), $filename];
    }

    /**
     * Recopila los datos de comprobante de todas las PECOSAs del período de
     * repartición indicado, listos para renderizarse en un único PDF.
     */
    public function getMonthlyComprobantesData(int $year, int $month): array
    {
        $pecosas = Pecosa::forDeliveryPeriod($year, $month)
            ->with([
                'detailPecosas.detailProduct.product.uom',
                'association.placeSector.place',
            ])
            ->orderBy('pecosa_number')
            ->get();

        return $pecosas->map(fn (Pecosa $pecosa) => $this->buildComprobanteData($pecosa, false))->all();
    }

    private function buildPecosaSnapshotDTO(array $data, array $president): PecosaSnapshotDTO
    {
        $chief = isset($data['chief_id']) ? Responsible::with('person')->find($data['chief_id']) : null;
        $storekeeper = isset($data['storekeeper_id']) ? Responsible::with('person')->find($data['storekeeper_id']) : null;
        $association = Association::with(['placeSector.place', 'placeSector.sector'])->find($data['association_id']);

        return new PecosaSnapshotDTO(
            $chief ? ($chief->person ? self::formatName($chief->person) : null) : null,
            $chief ? ($chief->person ? $chief->person->dni : null) : null,
            $storekeeper ? ($storekeeper->person ? self::formatName($storekeeper->person) : null) : null,
            $storekeeper ? ($storekeeper->person ? $storekeeper->person->dni : null) : null,
            $president['name'],
            $president['dni'],
            $president['name'],
            $president['dni'],
            $association ? $association->name : null,
            $association ? $association->code : null,
            $association ? $association->address : null,
            $association ? ($association->placeSector ? ($association->placeSector->place ? $association->placeSector->place->code : null) : null) : null,
            $association ? ($association->placeSector ? ($association->placeSector->place ? $association->placeSector->place->title : null) : null) : null,
            $association ? ($association->placeSector ? ($association->placeSector->sector ? $association->placeSector->sector->title : null) : null) : null,
            $association ? $this->partnerRepo->countBeneficiariesForAssociationAtDate($association->id, $data['delivery_date']) : 0,
        );
    }

    /**
     * Resuelve la presidenta del comité en el mes efectivo de reparto.
     * El padrón mensual es la fuente principal; las directivas son respaldo
     * para fechas sin padrón importado.
     *
     * @return array{partner_id:?int,name:?string,dni:?string}
     */
    private function resolvePresidentForDeliveryPeriod(array $data): array
    {
        $association = Association::findOrFail($data['association_id']);
        $effectiveDate = Pecosa::effectiveDeliveryDate($data['delivery_date']);
        $period = $effectiveDate->copy()->startOfMonth()->toDateString();

        $roster = AssociationRosterPeriod::with('presidentPartner.people')
            ->where('association_id', $association->id)
            ->whereDate('period', $period)
            ->first();

        if ($roster) {
            $partner = $roster->presidentPartner;
            $name = trim((string) $roster->president_name);

            if ($name === '' && $partner?->people) {
                $name = self::formatName($partner->people);
            }

            return [
                'partner_id' => $partner?->id,
                'name' => $name !== '' ? $name : null,
                'dni' => $partner?->people?->dni,
            ];
        }

        $partner = $association->getPresidentaAt($effectiveDate->toDateString());

        return [
            'partner_id' => $partner?->id,
            'name' => $partner?->people ? self::formatName($partner->people) : null,
            'dni' => $partner?->people?->dni,
        ];
    }

    private static function formatName($person): string
    {
        return trim(collect([$person->names, $person->father_lastname, $person->mother_lastname])->filter()->implode(' '));
    }

    /**
     * Descripción con período y ración por día de un artículo del comprobante.
     * Leche y hojuela llevan el período (mes efectivo de reparto) entre
     * paréntesis; el resto de productos conserva su descripción original.
     *
     * @return array{descripcion:?string,racion_dia:string}
     */
    public static function periodArticleInfo(?string $productName, $quantity, $deliveryDate): array
    {
        $effective = Pecosa::effectiveDeliveryDate($deliveryDate);
        $name = mb_strtoupper(trim((string) $productName));
        $isMilk = str_contains($name, 'LECHE');
        $isOat = str_contains($name, 'HOJUELA') || str_contains($name, 'AVENA');

        if (! $effective || (! $isMilk && ! $isOat)) {
            return ['descripcion' => null, 'racion_dia' => ''];
        }

        $meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $days = $effective->daysInMonth;
        $period = sprintf('DEL 01 AL %02d %s %d', $days, $meses[$effective->month], $effective->year);
        $label = $isOat ? 'HOJUELA DE QUINUA AVENA CON AZUCAR FORTIFICADA CON VITAMINAS Y MINERALES' : $name;

        return [
            'descripcion' => "{$label} ({$period})",
            'racion_dia' => number_format((float) $quantity / $days, 2),
        ];
    }

    private function buildComprobanteData(Pecosa $pecosa, bool $mostrarValores = true): array
    {
        $formatQuantity = static function ($value): string {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        };

        $articulos = $pecosa->detailPecosas->map(function (DetailPecosa $detail, int $index) use ($formatQuantity, $pecosa, $mostrarValores) {
            $product = $detail->detailProduct ? $detail->detailProduct->product : null;
            $name = trim((string) ($detail->product_name ?: ($product ? $product->title : '')));
            $abbreviation = trim((string) ($detail->product_abbreviation ?: ($product ? $product->abbreviation : '')));
            $description = $abbreviation !== '' ? "{$name} ({$abbreviation})" : $name;
            $info = self::periodArticleInfo($name, $detail->quantity, $pecosa->delivery_date);

            return [
                'numero' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'cantidad_solicitado' => $formatQuantity($detail->quantity),
                'descripcion' => $info['descripcion'] ?? $description,
                'cantidad_despachado' => $formatQuantity($detail->quantity),
                'racion_dia' => $info['racion_dia'],
                'unidad' => $detail->uom_title ?: ($product && $product->uom ? $product->uom->title : ''),
                'unitario' => $mostrarValores ? number_format((float) $detail->unit_price, 2) : '',
                'total' => $mostrarValores ? number_format((float) $detail->quantity * (float) $detail->unit_price, 2) : '',
            ];
        })->all();

        $total = $pecosa->detailPecosas->sum(
            fn (DetailPecosa $detail) => (float) $detail->quantity * (float) $detail->unit_price
        );

        return [
            'zona' => $pecosa->association_zone_code
                ?: ($pecosa->association && $pecosa->association->placeSector && $pecosa->association->placeSector->place
                    ? $pecosa->association->placeSector->place->code
                    : ''),
            'comite' => $pecosa->association_code ?: ($pecosa->association->code ?? ''),
            'num_mes' => $pecosa->beneficiaries_count ?? '',
            'numero_orden' => $pecosa->pecosa_number ?? '',
            'fecha' => $pecosa->delivery_date
                ? Carbon::parse($pecosa->delivery_date)->locale('es')->translatedFormat('j \\d\\e F \\d\\e Y')
                : '',
            'solicitante_nombre' => $pecosa->managing_partner_name ?: ($pecosa->president_name ?? ''),
            'domicilio' => $pecosa->association_name ?: ($pecosa->association->name ?? ''),
            'articulos' => $articulos,
            'total_general' => $mostrarValores ? 'S/. ' . number_format($total, 2) : '',
            'mostrar_valores' => $mostrarValores,
            'encargado_almacen' => $pecosa->chief_name ?? '',
            'dni_encargado' => $pecosa->chief_dni ?? '',
            'control' => $pecosa->storekeeper_name ?? '',
            'dni_control' => $pecosa->storekeeper_dni ?? '',
        ];
    }
}
