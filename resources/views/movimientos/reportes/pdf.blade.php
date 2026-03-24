@extends('reportes.layout_patron')

@section('title', 'Reporte de Movimientos')
@section('titulo', 'PADRÓN DE MOVIMIENTOS')
@section('subtitulo', $titulo ?? '')

@section('content')
@php
    $numero_fila = 1;
    $totalMovimientos = $transactions->count();
    $ingresos = $transactions->filter(function($t) { return $t->typeTransaction && $t->typeTransaction->title == 'Ingreso'; })->count();
    $salidas = $transactions->filter(function($t) { return $t->typeTransaction && $t->typeTransaction->title == 'Salida'; })->count();
    $valorTotal = $transactions->sum('total_price');
@endphp

<table class="main-table">
    <thead>
        <tr>
            <th style="width: 30px;">N°</th>
            <th style="width: 40px;">CÓDIGO</th>
            <th style="width: 100px;">PRODUCTO</th>
            <th style="width: 50px;">TIPO</th>
            <th style="width: 40px;">CANTIDAD</th>
            <th style="width: 50px;">PRECIO</th>
            <th style="width: 50px;">TOTAL</th>
            <th style="width: 50px;">FECHA</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $transaction)
        <tr>
            <td class="text-center">{{ $numero_fila }}</td>
            <td class="text-center">{{ str_pad($transaction->id, 4, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $transaction->product->title ?? 'Sin producto' }}</td>
            <td class="text-center">
                @if($transaction->typeTransaction && $transaction->typeTransaction->title == 'Ingreso')
                    INGRESO
                @else
                    SALIDA
                @endif
            </td>
            <td class="text-center">{{ $transaction->quantity }}</td>
            <td class="text-center">S/ {{ number_format($transaction->unit_price, 2) }}</td>
            <td class="text-center">S/ {{ number_format($transaction->total_price, 2) }}</td>
            <td class="text-center">
                @if($transaction->created_at)
                    {{ date('d/m/Y', strtotime($transaction->created_at)) }}
                @else
                    -
                @endif
            </td>
        </tr>
        @php $numero_fila++; @endphp
        @empty
        <tr>
            <td colspan="8" class="text-center" style="padding: 20px;">No se encontraron registros para este reporte.</td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($transactions->isNotEmpty())
<table class="totales-table" style="width: 50%; margin-top: 15px; border-collapse: collapse; border: 2px solid #000; margin-left: auto; margin-right: auto;">
    <tbody>
        <tr style="background-color: #e0e0e0;">
            <td colspan="3" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">
                CUADRO RESUMEN DE MOVIMIENTOS
            </td>
        </tr>
        <tr style="background-color: #f5f5f5;">
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">INGRESOS</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">SALIDAS</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">VALOR TOTAL</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; color: #4A7C59;">{{ $ingresos }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; color: #E76F51;">{{ $salidas }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; background-color: #e0e0e0;">S/ {{ number_format($valorTotal, 2) }}</td>
        </tr>
    </tbody>
</table>
@endif
@endsection
