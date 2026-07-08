<?php

namespace App\Services;

use App\DTOs\PecosaSnapshotDTO;
use App\Models\Association;
use App\Models\DetailPecosa;
use App\Models\Pecosa;
use App\Models\Responsible;
use App\Models\Partner;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Repositories\PartnerRepository;
use App\Repositories\PecosaRepository;
use App\Repositories\ProductRepository;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PecosaService
{
    private PecosaRepository $pecosaRepo;
    private ProductRepository $productRepo;
    private PartnerRepository $partnerRepo;
    private StockService $stockService;
    private PDFService $pdfService;

    public function __construct(PecosaRepository $pecosaRepo, ProductRepository $productRepo, PartnerRepository $partnerRepo, StockService $stockService, PDFService $pdfService)
    {
        $this->pecosaRepo = $pecosaRepo;
        $this->productRepo = $productRepo;
        $this->partnerRepo = $partnerRepo;
        $this->stockService = $stockService;
        $this->pdfService = $pdfService;
    }

    public function searchWithFilters(array $filters, int $perPage = 10)
    {
        return $this->pecosaRepo->searchWithFilters($filters, $perPage);
    }

    public function createPecosa(array $data): Pecosa
    {
        $association = Association::findOrFail($data['association_id']);
        if (!$association->isHabilitado() && empty($data['managing_partner_id'])) {
            throw new \DomainException('El comité no está habilitado. Debe asignar una presidenta primero.');
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

        return DB::transaction(function () use ($data, $detailProductsById) {
            $snapshot = $this->buildPecosaSnapshotDTO($data);
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

                $this->stockService->deductByDetailProduct($detail['detail_product_id'], $detail['quantity'], $pecosa->id);

                if ($typeSalida) {
                    Transaction::create([
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
                }
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

            $snapshot = $this->buildPecosaSnapshotDTO($data);
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

                $this->stockService->deductByDetailProduct($detail['detail_product_id'], $detail['quantity'], $pecosa->id);

                if ($typeSalida) {
                    Transaction::create([
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
                }
            }

            return $pecosa->fresh();
        });
    }

    public function generateComprobante(Pecosa $pecosa): \Barryvdh\DomPDF\PDF
    {
        $pecosa->load([
            'detailPecosas.detailProduct.product.uom',
            'association.placeSector.place',
            'association.partners.beneficiaries',
            'chief.person',
            'storekeeper.person'
        ]);

        $data = $this->buildComprobanteData($pecosa);
        return $this->pdfService->generate('comprobante_salida', $data, 'A4', 'landscape');
    }

    private function buildPecosaSnapshotDTO(array $data): PecosaSnapshotDTO
    {
        $chief = isset($data['chief_id']) ? Responsible::with('person')->find($data['chief_id']) : null;
        $storekeeper = isset($data['storekeeper_id']) ? Responsible::with('person')->find($data['storekeeper_id']) : null;
        $managingPartner = isset($data['managing_partner_id']) ? Partner::with('people')->find($data['managing_partner_id']) : null;
        $association = Association::with(['placeSector.place', 'placeSector.sector'])->find($data['association_id']);
        $president = $association ? $association->getPresidenta() : null;

        return new PecosaSnapshotDTO(
            $chief ? ($chief->person ? self::formatName($chief->person) : null) : null,
            $chief ? ($chief->person ? $chief->person->dni : null) : null,
            $storekeeper ? ($storekeeper->person ? self::formatName($storekeeper->person) : null) : null,
            $storekeeper ? ($storekeeper->person ? $storekeeper->person->dni : null) : null,
            $managingPartner ? ($managingPartner->people ? self::formatName($managingPartner->people) : null) : null,
            $managingPartner ? ($managingPartner->people ? $managingPartner->people->dni : null) : null,
            $president ? ($president->people ? self::formatName($president->people) : null) : null,
            $president ? ($president->people ? $president->people->dni : null) : null,
            $association ? $association->name : null,
            $association ? $association->code : null,
            $association ? $association->address : null,
            $association ? ($association->placeSector ? ($association->placeSector->place ? $association->placeSector->place->code : null) : null) : null,
            $association ? ($association->placeSector ? ($association->placeSector->place ? $association->placeSector->place->title : null) : null) : null,
            $association ? ($association->placeSector ? ($association->placeSector->sector ? $association->placeSector->sector->title : null) : null) : null,
            $association ? $this->partnerRepo->countBeneficiariesForAssociationAtDate($association->id, $data['delivery_date']) : 0,
        );
    }

    private static function formatName($person): string
    {
        return trim(collect([$person->names, $person->father_lastname, $person->mother_lastname])->filter()->implode(' '));
    }

    private function buildComprobanteData(Pecosa $pecosa): array
    {
        $association = $pecosa->association;
        $details = $pecosa->detailPecosas;
        
        return [
            'pecosa' => $pecosa,
            'association' => $association,
            'details' => $details,
            'president_name' => $pecosa->president_name,
            'chief_name' => $pecosa->chief_name,
            'storekeeper_name' => $pecosa->storekeeper_name,
            'managing_partner_name' => $pecosa->managing_partner_name,
            'beneficiaries_count' => $pecosa->beneficiaries_count,
        ];
    }
}