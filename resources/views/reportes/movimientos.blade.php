@extends('reportes.layout')

@section('title', 'Reporte de Movimientos')

@section('content')
<div class="info-box">
    <h2>{{ $titulo }}</h2>
    <div class="info-row">
        <span class="info-label">Fecha de generación:</span>
        <span>{{ date('d/m/Y H:i:s') }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Total de registros:</span>
        <span>{{ $transactions->count() }}</span>
    </div>
</div>

@if($tipo == 'valorizacion' || $tipo == 'estadistico')
<div class="summary-box">
    <h3>Resumen</h3>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-value">{{ $transactions->count() }}</div>
            <div class="summary-label">Total Movimientos</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $transactions->where('typeTransaction.title', 'Ingreso')->count() }}</div>
            <div class="summary-label">Ingresos</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $transactions->where('typeTransaction.title', 'Salida')->count() }}</div>
            <div class="summary-label">Salidas</div>
        </div>
    </div>
</div>
@endif

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Producto</th>
            <th>Tipo</th>
            <th>Cantidad</th>
            <th>Precio Unit.</th>
            <th>Total</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $transaction)
        <tr>
            <td>#{{ $transaction->id }}</td>
            <td>{{ $transaction->product->title ?? 'Sin producto' }}</td>
            <td>
                @if($transaction->typeTransaction && $transaction->typeTransaction->title == 'Ingreso')
                    <span class="badge badge-success">Ingreso</span>
                @else
                    <span class="badge badge-danger">Salida</span>
                @endif
            </td>
            <td>{{ $transaction->quantity }}</td>
            <td>S/ {{ number_format($transaction->unit_price, 2) }}</td>
            <td>S/ {{ number_format($transaction->total_price, 2) }}</td>
            <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y') }}</td>
        </tr>
        @endforeach
    </tbody>
    @if($tipo == 'valorizacion')
    <tfoot>
        <tr style="background: #E8F5E9; font-weight: bold;">
            <td colspan="5" style="text-align: right;">TOTAL:</td>
            <td>S/ {{ number_format($transactions->sum('total_price'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

@if($transactions->isEmpty())
<p style="text-align: center; color: #8B7355; padding: 20px;">No se encontraron registros para este reporte.</p>
@endif
@endsection
