@extends('reportes.layout')

@section('title', 'Reporte de Productos')

@section('content')
<div class="info-box">
    <h2>{{ $titulo }}</h2>
    <div class="info-row">
        <span class="info-label">Fecha de generación:</span>
        <span>{{ date('d/m/Y H:i:s') }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Total de registros:</span>
        <span>{{ $products->count() }}</span>
    </div>
</div>

@if($tipo == 'valorizacion')
<div class="summary-box">
    <h3>Resumen de Valorización</h3>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-value">{{ $products->count() }}</div>
            <div class="summary-label">Total Productos</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ number_format($products->sum('stock'), 0) }}</div>
            <div class="summary-label">Stock Total</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">S/ {{ number_format($products->sum(function($p) { return $p->stock * $p->unit_price; }), 2) }}</div>
            <div class="summary-label">Valor Total</div>
        </div>
    </div>
</div>
@endif

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Producto</th>
            <th>Abreviatura</th>
            <th>Stock</th>
            <th>Unidad</th>
            <th>Precio Unit.</th>
            @if($tipo == 'valorizacion')
            <th>Valor Total</th>
            @endif
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
        <tr>
            <td>#{{ $product->id }}</td>
            <td>{{ $product->title }}</td>
            <td>{{ $product->abbreviation ?? '-' }}</td>
            <td>
                @if($product->stock <= 10)
                    <span class="badge badge-danger">{{ $product->stock }}</span>
                @elseif($product->stock <= 50)
                    <span class="badge badge-warning">{{ $product->stock }}</span>
                @else
                    <span class="badge badge-success">{{ $product->stock }}</span>
                @endif
            </td>
            <td>{{ $product->uom->abbreviation ?? '-' }}</td>
            <td>S/ {{ number_format($product->unit_price, 2) }}</td>
            @if($tipo == 'valorizacion')
            <td>S/ {{ number_format($product->stock * $product->unit_price, 2) }}</td>
            @endif
            <td>
                @if($product->state && $product->state->title == 'Activo')
                    <span class="badge badge-success">Activo</span>
                @else
                    <span class="badge badge-danger">Inactivo</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($products->isEmpty())
<p style="text-align: center; color: #8B7355; padding: 20px;">No se encontraron registros para este reporte.</p>
@endif
@endsection
