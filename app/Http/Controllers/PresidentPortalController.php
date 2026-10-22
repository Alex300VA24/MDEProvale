<?php

namespace App\Http\Controllers;

use App\Models\Pecosa;
use App\Models\VerifiedDocument;
use App\Repositories\Contracts\PartnerRepositoryInterface;
use App\Repositories\Contracts\PecosaRepositoryInterface;
use App\Services\PresidentCommitteeResolver;
use App\Services\ReparticionService;
use App\Services\VerifiedDocumentService;
use Barryvdh\DomPDF\Facade\PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PresidentPortalController extends Controller
{
    public function __construct(
        private PresidentCommitteeResolver $committeeResolver,
        private ReparticionService $reparticionService,
        private VerifiedDocumentService $verifiedDocumentService,
        private PecosaRepositoryInterface $pecosas,
        private PartnerRepositoryInterface $partners
    ) {
    }

    public function index(Request $request)
    {
        $assignment = $this->committeeResolver->resolveAssignment($request->user());
        $association = $assignment?->partner?->association;
        [$defaultYear, $defaultMonth] = Pecosa::currentDeliveryPeriod();
        $year = min(max((int) $request->integer('year', $defaultYear), now()->year - 5), now()->year + 2);
        $month = min(max((int) $request->integer('month', $defaultMonth), 1), 12);
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        [$deliveryFrom, $deliveryTo] = Pecosa::deliveryPeriodRange($year, $month);
        [$currentYear, $currentMonth] = Pecosa::currentDeliveryPeriod();
        $periodIsPast = ($year * 12 + $month) < ($currentYear * 12 + $currentMonth);

        $scheduledPecosa = null;
        $allocation = null;
        $history = null;

        if ($association) {
            $scheduled = $this->pecosas->firstScheduledInRange(
                $association->id,
                $deliveryFrom->toDateString(),
                $deliveryTo->toDateString(),
            );

            if ($scheduled) {
                $scheduledPecosa = [
                    'id' => $scheduled->id,
                    'pecosa_number' => $scheduled->pecosa_number,
                    'delivery_date' => $scheduled->delivery_date?->format('d/m/Y'),
                    'vigencia' => $scheduled->vigencia,
                    'vigente' => $scheduled->isVigente(),
                ];
            }

            $racion = $this->reparticionService->getActiveRacion($year, $month);
            if ($racion && $this->reparticionService->hasIngresoForPeriod($year, $month)) {
                $allocation = $this->reparticionService
                    ->buildReport($racion, $year, $month)['associations']
                    ->firstWhere('id', $association->id);
            }

            $history = $this->pecosas->paginateForAssociation($association->id, 8)
                ->withQueryString()
                ->through(fn (Pecosa $pecosa) => [
                    'id' => $pecosa->id,
                    'pecosa_number' => $pecosa->pecosa_number,
                    'delivery_date' => $pecosa->delivery_date?->format('d/m/Y'),
                    'products_count' => $pecosa->detailPecosas->count(),
                    'quantity' => (float) $pecosa->detailPecosas->sum('quantity'),
                    'vigente' => $pecosa->isVigente(),
                ]);
        }

        return Inertia::render('Portal/Index', [
            'presidenta' => trim((string) $request->user()->names) ?: $request->user()->username,
            'association' => $association ? [
                'name' => $association->name,
                'code' => $association->code,
                'address' => $association->address,
                'sector' => $association->placeSector?->sector?->title,
            ] : null,
            'scheduledPecosa' => $scheduledPecosa,
            'allocation' => $allocation,
            'history' => $history,
            'filters' => ['year' => $year, 'month' => $month],
            'periodIsPast' => $periodIsPast,
            'periodLabel' => ucfirst($periodStart->locale('es')->monthName) . ' ' . $year,
            'months' => collect(range(1, 12))->map(fn ($m) => [
                'value' => $m,
                'label' => ucfirst(Carbon::create()->month($m)->locale('es')->monthName),
            ])->all(),
            'years' => collect(range(now()->year + 1, now()->year - 5))->values()->all(),
        ]);
    }

    public function showPecosa(Request $request, Pecosa $pecosa)
    {
        $association = $this->committeeResolver->resolve($request->user());

        // Solo se puede consultar una PECOSA del comité asignado a la presidenta.
        if (! $association || (int) $pecosa->association_id !== (int) $association->id) {
            throw new NotFoundHttpException();
        }

        $pecosa->load(['detailPecosas', 'association.placeSector.sector']);

        $details = $pecosa->detailPecosas->map(fn ($detail) => [
            'product' => $detail->product_name ?: $detail->product_abbreviation ?: '—',
            'uom' => $detail->uom_title ?: '—',
            'quantity' => (float) $detail->quantity,
            'delivered_quantity' => (float) ($detail->delivered_quantity ?? 0),
        ])->all();

        return Inertia::render('Portal/Pecosa', [
            'association' => [
                'name' => $association->name,
                'code' => $association->code,
            ],
            'pecosa' => [
                'id' => $pecosa->id,
                'pecosa_number' => $pecosa->pecosa_number,
                'delivery_date' => $pecosa->delivery_date?->format('d/m/Y'),
                'vigente' => $pecosa->isVigente(),
                'association_name' => $pecosa->association_name ?: $association->name,
                'association_code' => $pecosa->association_code ?: $association->code,
                'association_address' => $pecosa->association_address,
                'association_sector_name' => $pecosa->association_sector_name,
                'association_zone_code' => $pecosa->association_zone_code,
                'association_zone_name' => $pecosa->association_zone_name,
                'beneficiaries_count' => $pecosa->beneficiaries_count,
                'president_name' => $pecosa->president_name,
                'president_dni' => $pecosa->president_dni,
                'managing_partner_name' => $pecosa->managing_partner_name,
                'managing_partner_dni' => $pecosa->managing_partner_dni,
                'chief_name' => $pecosa->chief_name,
                'chief_dni' => $pecosa->chief_dni,
                'storekeeper_name' => $pecosa->storekeeper_name,
                'storekeeper_dni' => $pecosa->storekeeper_dni,
                'observation' => $pecosa->observation,
                'details' => $details,
                'totals' => [
                    'quantity' => (float) $pecosa->detailPecosas->sum('quantity'),
                    'delivered_quantity' => (float) $pecosa->detailPecosas->sum('delivered_quantity'),
                ],
            ],
        ]);
    }

    /**
     * Comprobante de salida (PECOSA) en PDF, restringido al comité de la presidenta.
     */
    public function pecosaPdf(Request $request, Pecosa $pecosa)
    {
        $association = $this->committeeResolver->resolve($request->user());

        if (! $association || (int) $pecosa->association_id !== (int) $association->id) {
            throw new NotFoundHttpException();
        }

        $pecosa->load([
            'detailPecosas.detailProduct.product.uom',
            'association.placeSector.place',
            'association.partners.beneficiaries',
        ]);

        $formatCantidad = function ($value) {
            return floor($value) == $value
                ? number_format($value, 0, '.', '')
                : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        };

        $articulos = [];
        foreach ($pecosa->detailPecosas as $index => $detail) {
            $product = $detail->detailProduct->product ?? null;
            $productName = $detail->product_name ?? ($product ? $product->title : '-');
            $productAbbreviation = $detail->product_abbreviation ?? ($product ? $product->abbreviation : null);
            $info = \App\Services\PecosaService::periodArticleInfo($productName, $detail->quantity, $pecosa->delivery_date);
            $articulos[] = [
                'numero' => str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'cantidad_solicitado' => $formatCantidad($detail->quantity),
                'descripcion' => $info['descripcion'] ?? ($productAbbreviation ? $productName . ' (' . $productAbbreviation . ')' : $productName),
                'cantidad_despachado' => $formatCantidad($detail->quantity),
                'racion_dia' => $info['racion_dia'],
                'unidad' => $detail->uom_title ?? ($product && $product->uom ? $product->uom->title : 'UNIDAD'),
            ];
        }

        $associationModel = $pecosa->association;
        $zonaCode = $pecosa->association_zone_code ?: ($associationModel && $associationModel->placeSector && $associationModel->placeSector->place
            ? ($associationModel->placeSector->place->code ?? '01')
            : '01');
        $totalBeneficiarios = $pecosa->beneficiaries_count ?? ($associationModel
            ? $associationModel->partners->sum(function ($partner) {
                return $partner->beneficiaries->count();
            })
            : 0);
        $fechaLarga = Carbon::parse($pecosa->delivery_date)
            ->locale('es')
            ->translatedFormat('l, j \d\e F \d\e Y');

        $data = [
            'zona' => $zonaCode,
            'comite' => $pecosa->association_code ?? ($associationModel ? $associationModel->code : 'N/A'),
            'num_mes' => $totalBeneficiarios,
            'racion' => 'N/A',
            'numero_orden' => $pecosa->pecosa_number,
            'solicitante_nombre' => $pecosa->managing_partner_name ?? $pecosa->president_name ?? '',
            'domicilio' => $pecosa->association_name ?? ($associationModel ? $associationModel->name : 'N/A'),
            'fecha' => $fechaLarga,
            'articulos' => $articulos,
            'total_general' => '',
            'mostrar_valores' => false,
            'encargado_almacen' => $pecosa->chief_name ?? '',
            'dni_encargado' => $pecosa->chief_dni ?? '',
            'control' => $pecosa->storekeeper_name ?? '',
            'dni_control' => $pecosa->storekeeper_dni ?? '',
        ];

        [, $contents, $filename] = $this->verifiedDocumentService->issue(
            VerifiedDocument::TYPE_PECOSA_RECEIPT,
            'PEC-' . Str::upper(Str::slug((string) $pecosa->pecosa_number)) . '-' . Str::upper(Str::random(6)),
            [
                'pecosa' => $pecosa->pecosa_number,
                'comite' => $pecosa->association_name ?: ($associationModel->name ?? ''),
                'fecha_entrega' => optional($pecosa->delivery_date)->format('Y-m-d'),
            ],
            'comprobante_salida',
            $data,
            'comprobante-salida-' . $pecosa->pecosa_number . '.pdf',
            $request->user()->id,
            'a4',
            'landscape'
        );

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function socios(Request $request)
    {
        $association = $this->committeeResolver->resolve($request->user());
        $search = trim((string) $request->input('search', ''));

        $partners = collect();

        if ($association) {
            $partners = $this->partners->forCommitteePortal($association->id, $search)
                ->map(function ($partner) {
                    $person = $partner->people;

                    return [
                        'id' => $partner->id,
                        'name' => $person
                            ? trim($person->names . ' ' . $person->father_lastname . ' ' . $person->mother_lastname)
                            : 'Sin nombre',
                        'dni' => $person?->dni,
                        'gender' => $person?->gender,
                        'birthdate' => $person?->birthdate ? Carbon::parse($person->birthdate)->format('d/m/Y') : null,
                        'age' => $person?->birthdate ? $person->age_formatted : null,
                        'phone' => $person?->phone_number ?: $person?->telephone_number,
                        'address' => $person?->address,
                        'date_begin' => $partner->date_begin ? Carbon::parse($partner->date_begin)->format('d/m/Y') : null,
                        'date_end' => $partner->date_end ? Carbon::parse($partner->date_end)->format('d/m/Y') : null,
                        'state' => $partner->state?->title,
                        'observations' => $partner->observations,
                        'beneficiaries' => $partner->beneficiaries->map(function ($beneficiary) {
                            $bp = $beneficiary->person;

                            return [
                                'name' => $bp
                                    ? trim($bp->names . ' ' . $bp->father_lastname . ' ' . $bp->mother_lastname)
                                    : 'Sin nombre',
                                'dni' => $bp?->dni,
                                'relationship' => $beneficiary->relationship?->title,
                                'gender' => $bp?->gender,
                                'age' => $bp?->birthdate ? $bp->age_formatted : null,
                            ];
                        })->all(),
                    ];
                });
        }

        return Inertia::render('Portal/Socios', [
            'association' => $association ? ['name' => $association->name] : null,
            'partners' => $partners->all(),
            'filters' => ['search' => $search],
        ]);
    }

    public function documentos(Request $request)
    {
        return Inertia::render('Portal/Documentos', [
            'association' => $this->committeeResolver->resolve($request->user())?->only(['name']),
        ]);
    }

    private const DOWNLOADABLE_DOCUMENTS = [
        'ficha' => ['view' => 'ficha_beneficiario', 'file' => 'ficha-beneficiarios.pdf'],
        'declaracion-domicilio' => ['view' => 'actas.declaracion_domicilio', 'file' => 'declaracion-jurada-domicilio.pdf'],
        'declaracion-parentesco' => ['view' => 'actas.declaracion_parentesco', 'file' => 'declaracion-jurada-parentesco.pdf'],
        'acta-renuncia' => ['view' => 'actas.renuncia', 'file' => 'acta-renuncia.pdf'],
    ];

    /**
     * Formato en blanco para que el comité lo descargue, imprima y llene a mano.
     */
    public function documentoDescargable(Request $request, string $slug)
    {
        $document = self::DOWNLOADABLE_DOCUMENTS[$slug] ?? null;

        if (! $document || ! $this->committeeResolver->resolve($request->user())) {
            throw new NotFoundHttpException();
        }

        $data = [
            'partner' => null,
            'logoPath' => public_path('img/muni2.png'),
            'partnerName' => null,
            'partnerDni' => null,
            'partnerAddress' => null,
            'committeeName' => null,
            'committeeCode' => null,
            'presidentDni' => null,
            'beneficiaries' => [],
            'dateLong' => null,
        ];

        return PDF::loadView($document['view'], $data)
            ->setPaper('a4', 'portrait')
            ->download($document['file']);
    }
}
