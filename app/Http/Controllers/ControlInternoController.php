<?php

namespace App\Http\Controllers;

use App\Services\ControlInternoService;
use App\Services\PDFService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Ficha de Control Interno (ingreso) y Tarjeta de Control Interno (kardex
 * mensual) de leche y hojuela. Los datos se editan en el modal y llegan al PDF
 * como un payload JSON, de modo que lo impreso es exactamente lo revisado.
 */
class ControlInternoController extends Controller
{
    public function __construct(private PDFService $pdfService, private ControlInternoService $control)
    {
    }

    /** Filas precargadas de la tarjeta: ingresos del mes y salidas por PECOSA. */
    public function tarjetaDatos(Request $request)
    {
        $validated = $request->validate([
            'product' => 'required|in:milk,oat',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        return response()->json(
            $this->control->cardData($validated['product'], (int) $validated['year'], (int) $validated['month']),
            200,
            [],
            JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    /** PDF de la ficha de control interno de ingreso (leche u hojuela). */
    public function fichaPdf(Request $request)
    {
        $kind = $this->kindFrom($request);
        $data = $this->payload($request);

        $normalize = fn ($list) => collect($list ?? [])->map(fn ($item) => [
            'label' => (string) ($item['label'] ?? ''),
            'value' => in_array($item['value'] ?? null, ['si', 'no'], true) ? $item['value'] : null,
        ])->all();

        $view = $kind === 'milk' ? 'movimientos.ficha_control_leche' : 'movimientos.ficha_control_hojuela';

        return $this->pdfService->stream($view, [
            'productName' => ControlInternoService::PRODUCTS[$kind]['name'],
            'data' => $data,
            'items' => $normalize($data['items'] ?? []),
            'extras' => $normalize($data['extras'] ?? []),
            'weighing' => in_array($data['weighing'] ?? null, ['si', 'no'], true) ? $data['weighing'] : null,
        ], "ficha-control-{$kind}.pdf", 'a4', 'portrait');
    }

    /** PDF de la tarjeta de control interno con las filas confirmadas en el modal. */
    public function tarjetaPdf(Request $request)
    {
        $kind = $this->kindFrom($request);
        $data = $this->payload($request);

        $rows = collect($data['rows'] ?? [])->map(fn ($row) => [
            'date' => $this->displayDate($row['date'] ?? null),
            'time' => (string) ($row['time'] ?? ''),
            'in' => $this->numberOrNull($row['in'] ?? null),
            'out' => $this->numberOrNull($row['out'] ?? null),
            'by' => (string) ($row['by'] ?? ''),
        ])->all();

        return $this->pdfService->stream('movimientos.tarjeta_control', [
            'productName' => ControlInternoService::PRODUCTS[$kind]['card_name'],
            'folio' => (string) ($data['folio'] ?? ''),
            'month' => (string) ($data['month'] ?? ''),
            'rows' => $rows,
            'minRows' => 18,
        ], "tarjeta-control-{$kind}.pdf", 'a4', 'portrait');
    }

    private function kindFrom(Request $request): string
    {
        return $request->validate(['product' => 'required|in:milk,oat'])['product'];
    }

    private function payload(Request $request): array
    {
        $request->validate(['payload' => 'required|string|max:200000']);
        $data = json_decode($request->input('payload'), true);
        abort_unless(is_array($data), 422, 'Los datos del documento no son válidos.');

        return $data;
    }

    private function numberOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $number = (float) str_replace(',', '', (string) $value);

        return is_finite($number) ? $number : null;
    }

    private function displayDate($value): string
    {
        if (! $value) {
            return '';
        }
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
