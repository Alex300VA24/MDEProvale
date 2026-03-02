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
        <span>{{ $awards->count() }}</span>
    </div>
</div>

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
        @foreach($awards as $award)
        <tr>
            <td>#{{ $award->id }}</td>
            <td>{{ $award->document }}</td>
            <td>{{ $award->association->name ?? '-' }}</td>
            <td>{{ \Carbon\Carbon::parse($award->date_document)->format('d/m/Y') }}</td>
            <td>
                {{ \Carbon\Carbon::parse($award->date_start)->format('d/m/Y') }} - 
                {{ \Carbon\Carbon::parse($award->date_end)->format('d/m/Y') }}
            </td>
            <td>
                @if($award->state && $award->state->title == 'Activo')
                    <span class="badge badge-success">Activo</span>
                @else
                    <span class="badge badge-danger">Inactivo</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($awards->isEmpty())
<p style="text-align: center; color: #8B7355; padding: 20px;">No se encontraron registros para este reporte.</p>
@endif
@endsection
