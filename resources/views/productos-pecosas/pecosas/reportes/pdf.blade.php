@extends('reportes.layout')

@section('title', 'Reporte de Pecosas')

@section('content')
<div class="info-box">
    <h2>{{ $titulo }}</h2>
    <div class="info-row">
        <span class="info-label">Fecha de generación:</span>
        <span>{{ date('d/m/Y H:i:s') }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Total de registros:</span>
        <span>{{ $pecosas->count() }}</span>
    </div>
</div>

@if($tipo == 'estadistico')
<div class="summary-box">
    <h3>Resumen Estadístico</h3>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-value">{{ $pecosas->count() }}</div>
            <div class="summary-label">Total Pecosas</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $pecosas->groupBy('association_id')->count() }}</div>
            <div class="summary-label">Clubes Atendidos</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $pecosas->filter(function($p) { return $p->state && $p->state->title == 'Activo'; })->count() }}</div>
            <div class="summary-label">Activas</div>
        </div>
    </div>
</div>
@endif

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Número Pecosa</th>
            <th>Club de Madres</th>
            <th>Fecha Entrega</th>
            <th>Responsable</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($pecosas as $pecosa)
        <tr>
            <td>#{{ $pecosa->id }}</td>
            <td>{{ $pecosa->pecosa_number }}</td>
            <td>{{ $pecosa->association->name ?? '-' }}</td>
            <td>{{ \Carbon\Carbon::parse($pecosa->delivery_date)->format('d/m/Y') }}</td>
            <td>
                @if($pecosa->managingPartner && $pecosa->managingPartner->people)
                    {{ $pecosa->managingPartner->people->names }} {{ $pecosa->managingPartner->people->father_lastname }}
                @else
                    -
                @endif
            </td>
            <td>
                @if($pecosa->state && $pecosa->state->title == 'Activo')
                    <span class="badge badge-success">Activo</span>
                @else
                    <span class="badge badge-danger">Inactivo</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($tipo == 'detalle')
    @foreach($pecosas as $pecosa)
        @if($pecosa->detailPecosas && $pecosa->detailPecosas->count() > 0)
        <div style="page-break-before: always;">
            <h3 style="color: #4A7C59; margin: 20px 0 10px;">Detalle de Pecosa: {{ $pecosa->pecosa_number }}</h3>
            <p style="margin-bottom: 10px;"><strong>Club:</strong> {{ $pecosa->association->name ?? '-' }}</p>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pecosa->detailPecosas as $detail)
                    <tr>
                        <td>{{ $detail->product->title ?? '-' }}</td>
                        <td>{{ $detail->quantity ?? '-' }}</td>
                        <td>{{ $detail->product->uom->abbreviation ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endforeach
@endif

@if($pecosas->isEmpty())
<p style="text-align: center; color: #8B7355; padding: 20px;">No se encontraron registros para este reporte.</p>
@endif
@endsection
