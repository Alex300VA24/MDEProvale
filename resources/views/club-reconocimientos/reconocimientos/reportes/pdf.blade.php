@extends('reportes.layout')

@section('title', 'Reporte de Reconocimientos')

@section('content')
<div class="info-box">
    <h2>{{ $titulo }}</h2>
    <div class="info-row">
        <span class="info-label">Fecha de generación:</span>
        <span>{{ date('d/m/Y H:i:s') }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Total de registros:</span>
        <span>{{ $resolutions->count() }}</span>
    </div>
</div>

@if($tipo == 'estadistico')
<div class="summary-box">
    <h3>Resumen Estadístico</h3>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-value">{{ $resolutions->count() }}</div>
            <div class="summary-label">Total Reconocimientos</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $resolutions->filter(function($r) { return $r->state && $r->state->title == 'Activo'; })->count() }}</div>
            <div class="summary-label">Vigentes</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $resolutions->groupBy('association_id')->count() }}</div>
            <div class="summary-label">Clubes</div>
        </div>
    </div>
</div>
@endif

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Documento</th>
            <th>Club de Madres</th>
            <th>Fecha Documento</th>
            <th>Vigencia</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($resolutions as $resolution)
        <tr>
            <td>#{{ $resolution->id }}</td>
            <td>{{ $resolution->document }}</td>
            <td>{{ $resolution->association->name ?? '-' }}</td>
            <td>{{ \Carbon\Carbon::parse($resolution->date_document)->format('d/m/Y') }}</td>
            <td>
                {{ \Carbon\Carbon::parse($resolution->date_start)->format('d/m/Y') }} - 
                {{ \Carbon\Carbon::parse($resolution->date_end)->format('d/m/Y') }}
            </td>
            <td>
                @if($resolution->state && $resolution->state->title == 'Activo')
                    <span class="badge badge-success">Activo</span>
                @else
                    <span class="badge badge-danger">Inactivo</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($resolutions->isEmpty())
<p style="text-align: center; color: #8B7355; padding: 20px;">No se encontraron registros para este reporte.</p>
@endif
@endsection
