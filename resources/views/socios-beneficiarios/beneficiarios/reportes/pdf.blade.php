@extends('reportes.layout')

@section('title', 'Reporte de Beneficiarios')

@section('content')
<div class="info-box">
    <h2>{{ $titulo }}</h2>
    <div class="info-row">
        <span class="info-label">Fecha de generación:</span>
        <span>{{ date('d/m/Y H:i:s') }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Total de registros:</span>
        <span>{{ $beneficiaries->count() }}</span>
    </div>
</div>

@if($tipo == 'estadistico')
<div class="summary-box">
    <h3>Resumen Estadístico</h3>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-value">{{ $beneficiaries->count() }}</div>
            <div class="summary-label">Total Beneficiarios</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $beneficiaries->groupBy('partner_id')->count() }}</div>
            <div class="summary-label">Socios con Beneficiarios</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $beneficiaries->groupBy('relationship_id')->count() }}</div>
            <div class="summary-label">Tipos de Relación</div>
        </div>
    </div>
</div>
@endif

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Beneficiario</th>
            <th>DNI</th>
            <th>Socio</th>
            <th>Relación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($beneficiaries as $beneficiary)
        <tr>
            <td>#{{ $beneficiary->id }}</td>
            <td>
                @if($beneficiary->person)
                    {{ $beneficiary->person->names }} {{ $beneficiary->person->father_lastname }} {{ $beneficiary->person->mother_lastname }}
                @else
                    Sin nombre
                @endif
            </td>
            <td>{{ $beneficiary->person->dni ?? '-' }}</td>
            <td>
                @if($beneficiary->partner && $beneficiary->partner->people)
                    {{ $beneficiary->partner->people->names }} {{ $beneficiary->partner->people->father_lastname }}
                @else
                    -
                @endif
            </td>
            <td>{{ $beneficiary->relationship->title ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($beneficiaries->isEmpty())
<p style="text-align: center; color: #8B7355; padding: 20px;">No se encontraron registros para este reporte.</p>
@endif
@endsection
