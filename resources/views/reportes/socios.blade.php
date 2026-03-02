@extends('reportes.layout')

@section('title', 'Reporte de Socios')

@section('content')
<div class="info-box">
    <h2>{{ $titulo }}</h2>
    <div class="info-row">
        <span class="info-label">Fecha de generación:</span>
        <span>{{ date('d/m/Y H:i:s') }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Total de registros:</span>
        <span>{{ $partners->count() }}</span>
    </div>
</div>

@if($tipo == 'estadistico')
<div class="summary-box">
    <h3>Resumen Estadístico</h3>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-value">{{ $partners->count() }}</div>
            <div class="summary-label">Total Socios</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $partners->where('state.title', 'Activo')->count() }}</div>
            <div class="summary-label">Activos</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $partners->groupBy('association_id')->count() }}</div>
            <div class="summary-label">Clubes</div>
        </div>
    </div>
</div>
@endif

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Socio</th>
            <th>DNI</th>
            <th>Club de Madres</th>
            <th>Fecha Inicio</th>
            <th>Estado</th>
            @if($tipo == 'beneficiarios')
            <th>Beneficiarios</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($partners as $partner)
        <tr>
            <td>#{{ $partner->id }}</td>
            <td>
                @if($partner->people)
                    {{ $partner->people->names }} {{ $partner->people->father_lastname }} {{ $partner->people->mother_lastname }}
                @else
                    Sin nombre
                @endif
            </td>
            <td>{{ $partner->people->dni ?? '-' }}</td>
            <td>{{ $partner->association->name ?? '-' }}</td>
            <td>{{ \Carbon\Carbon::parse($partner->date_begin)->format('d/m/Y') }}</td>
            <td>
                @if($partner->state && $partner->state->title == 'Activo')
                    <span class="badge badge-success">Activo</span>
                @else
                    <span class="badge badge-danger">Inactivo</span>
                @endif
            </td>
            @if($tipo == 'beneficiarios')
            <td>{{ $partner->beneficiaries->count() }}</td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>

@if($partners->isEmpty())
<p style="text-align: center; color: #8B7355; padding: 20px;">No se encontraron registros para este reporte.</p>
@endif
@endsection
