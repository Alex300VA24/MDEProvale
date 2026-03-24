@extends('reportes.layout_patron')

@section('title', 'Reporte de Productos')
@section('titulo', 'PADRÓN DE PRODUCTOS')
@section('subtitulo', $titulo ?? '')

@section('content')
@php
    $numero_fila = 1;
    $totalProductos = $products->count();
    $stockTotal = $products->sum('stock');
    $valorTotal = $products->sum(function($p) { return $p->stock * $p->unit_price; });
    $stockBajo = $products->filter(function($p) { return $p->stock <= 10; })->count();
@endphp

<table class="main-table">
    <thead>
        <tr>
            <th style="width: 30px;">N°</th>
            <th style="width: 40px;">CÓDIGO</th>
            <th style="width: 120px;">PRODUCTO</th>
            <th style="width: 50px;">ABREV.</th>
            <th style="width: 40px;">STOCK</th>
            <th style="width: 40px;">UNIDAD</th>
            <th style="width: 50px;">PRECIO</th>
            @if($tipo == 'valorizacion')
            <th style="width: 50px;">VALOR</th>
            @endif
            <th style="width: 40px;">ESTADO</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $product)
        <tr>
            <td class="text-center">{{ $numero_fila }}</td>
            <td class="text-center">{{ str_pad($product->id, 4, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $product->title }}</td>
            <td class="text-center">{{ $product->abbreviation ?? '-' }}</td>
            <td class="text-center">
                @if($product->stock <= 10)
                    <span style="background-color: #FCE8E4; color: #E76F51; padding: 1px 4px; border-radius: 2px; font-size: 5pt; font-weight: bold;">{{ $product->stock }}</span>
                @elseif($product->stock <= 50)
                    <span style="background-color: #FEF3E2; color: #D97706; padding: 1px 4px; border-radius: 2px; font-size: 5pt; font-weight: bold;">{{ $product->stock }}</span>
                @else
                    {{ $product->stock }}
                @endif
            </td>
            <td class="text-center">{{ $product->uom->abbreviation ?? '-' }}</td>
            <td class="text-center">S/ {{ number_format($product->unit_price, 2) }}</td>
            @if($tipo == 'valorizacion')
            <td class="text-center">S/ {{ number_format($product->stock * $product->unit_price, 2) }}</td>
            @endif
            <td class="text-center">
                @if($product->state && $product->state->title == 'Activo')
                    ACTIVO
                @else
                    INACTIVO
                @endif
            </td>
        </tr>
        @php $numero_fila++; @endphp
        @empty
        <tr>
            <td colspan="{{ ($tipo == 'valorizacion') ? 9 : 8 }}" class="text-center" style="padding: 20px;">
                No se encontraron registros para este reporte.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($products->isNotEmpty())
<table class="totales-table" style="width: 50%; margin-top: 15px; border-collapse: collapse; border: 2px solid #000; margin-left: auto; margin-right: auto;">
    <tbody>
        <tr style="background-color: #e0e0e0;">
            <td colspan="{{ ($tipo == 'valorizacion') ? 4 : 3 }}" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">
                CUADRO RESUMEN DE PRODUCTOS
            </td>
        </tr>
        <tr style="background-color: #f5f5f5;">
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">TOTAL</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">STOCK</td>
            @if($tipo == 'valorizacion')
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">VALOR</td>
            @endif
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">STOCK BAJO</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; background-color: #e0e0e0;">{{ $totalProductos }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold;">{{ number_format($stockTotal, 0) }}</td>
            @if($tipo == 'valorizacion')
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold;">S/ {{ number_format($valorTotal, 2) }}</td>
            @endif
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; color: #E76F51;">{{ $stockBajo }}</td>
        </tr>
    </tbody>
</table>
@endif
@endsection
