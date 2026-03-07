@extends('reportes.layout')

@section('title', 'Reporte de Club de Madres')

@section('content')
<div class="info-box">
    <h2>{{ $titulo }}</h2>
    <div class="info-row">
        <span class="info-label">Fecha de generación:</span>
        <span>{{ date('d/m/Y H:i:s') }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Total de registros:</span>
        <span>{{ $associations->count() }}</span>
    </div>
</div>

@if($tipo == 'estadistico')
<div class="summary-box">
    <h3>Resumen Estadístico</h3>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-value">{{ $associations->count() }}</div>
            <div class="summary-label">Total Clubes</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $associations->sum('partners_count') }}</div>
            <div class="summary-label">Total Socios</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $associations->filter(function($a) { return $a->partners->count() > 0; })->count() }}</div>
            <div class="summary-label">Clubes Activos</div>
        </div>
    </div>
</div>
@endif

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre del Club</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            @if($tipo == 'socios' || $tipo == 'estadistico')
            <th>Cant. Socios</th>
            @endif
            @if($tipo == 'reconocimientos')
            <th>Reconocimientos</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($associations as $association)
        <tr>
            <td>#{{ $association->id }}</td>
            <td>{{ $association->name }}</td>
            <td>{{ $association->address ?? '-' }}</td>
            <td>{{ $association->phone ?? '-' }}</td>
            @if($tipo == 'socios' || $tipo == 'estadistico')
            <td>{{ $association->partners_count ?? $association->partners->count() }}</td>
            @endif
            @if($tipo == 'reconocimientos')
            <td>{{ $association->resolutions->count() }}</td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>

@if($tipo == 'socios')
    @foreach($associations as $association)
        @if($association->partners->count() > 0)
        <div style="page-break-before: always;">
            <h3 style="color: #4A7C59; margin: 20px 0 10px;">Socios del Club: {{ $association->name }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Socio</th>
                        <th>DNI</th>
                        <th>Fecha Inicio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($association->partners as $partner)
                    <tr>
                        <td>
                            @if($partner->people)
                                {{ $partner->people->names }} {{ $partner->people->father_lastname }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $partner->people->dni ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($partner->date_begin)->format('d/m/Y') }}</td>
                        <td>
                            @if($partner->state && $partner->state->title == 'Activo')
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endforeach
@endif

@if($associations->isEmpty())
<p style="text-align: center; color: #8B7355; padding: 20px;">No se encontraron registros para este reporte.</p>
@endif
@endsection
