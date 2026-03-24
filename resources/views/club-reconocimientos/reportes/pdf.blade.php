@extends('reportes.layout_patron')

@section('title', 'Reporte de Club de Madres')
@section('titulo', 'PADRÓN DE CLUB DE MADRES')
@section('subtitulo', $titulo ?? '')

@section('content')
@php
    $numero_fila = 1;
    $totalClubes = $associations->count();
    $totalSocios = 0;
    if ($tipo == 'socios' || $tipo == 'estadistico') {
        foreach ($associations as $a) {
            $totalSocios += $a->partners_count ?? $a->partners->count();
        }
    }
@endphp

<table class="main-table">
    <thead>
        <tr>
            <th style="width: 30px;">N°</th>
            <th style="width: 50px;">CÓDIGO</th>
            <th style="width: 150px;">NOMBRE DEL CLUB</th>
            <th style="width: 100px;">DIRECCIÓN</th>
            <th style="width: 60px;">SECTOR</th>
            @if($tipo == 'socios' || $tipo == 'estadistico')
            <th style="width: 40px;">SOCIOS</th>
            @endif
            @if($tipo == 'reconocimientos')
            <th style="width: 60px;">RESOLUCIÓN</th>
            @endif
            <th style="width: 50px;">ESTADO</th>
        </tr>
    </thead>
    <tbody>
        @forelse($associations as $association)
        <tr>
            <td class="text-center">{{ $numero_fila }}</td>
            <td class="text-center">{{ str_pad($association->id, 4, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $association->name }}</td>
            <td>{{ $association->address ?? '-' }}</td>
            <td class="text-center">
                @if($association->placeSector && $association->placeSector->sector)
                    {{ $association->placeSector->sector->sector }}
                @else
                    -
                @endif
            </td>
            @if($tipo == 'socios' || $tipo == 'estadistico')
            <td class="text-center">{{ $association->partners_count ?? $association->partners->count() }}</td>
            @endif
            @if($tipo == 'reconocimientos')
            <td class="text-center">
                @if($association->resolution)
                    {{ $association->resolution->document }}
                @else
                    -
                @endif
            </td>
            @endif
            <td class="text-center">
                @if($association->state && $association->state->title == 'Activo')
                    ACTIVO
                @else
                    INACTIVO
                @endif
            </td>
        </tr>
        @php $numero_fila++; @endphp
        @empty
        <tr>
            <td colspan="{{ ($tipo == 'socios' || $tipo == 'estadistico') ? 7 : ($tipo == 'reconocimientos' ? 7 : 6) }}" class="text-center" style="padding: 20px;">
                No se encontraron registros para este reporte.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($associations->isNotEmpty())
<table class="totales-table" style="width: 40%; margin-top: 15px; border-collapse: collapse; border: 2px solid #000; margin-left: auto; margin-right: auto;">
    <tbody>
        <tr style="background-color: #e0e0e0;">
            <td colspan="2" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">
                CUADRO RESUMEN DE CLUBES
            </td>
        </tr>
        <tr style="background-color: #f5f5f5;">
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">TOTAL CLUBES</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">TOTAL SOCIOS</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; background-color: #e0e0e0;">{{ $totalClubes }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold;">{{ $totalSocios }}</td>
        </tr>
    </tbody>
</table>
@endif
@endsection
