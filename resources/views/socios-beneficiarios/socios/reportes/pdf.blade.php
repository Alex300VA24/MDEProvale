@extends('reportes.layout_patron')

@section('title', 'Reporte de Socios')
@section('titulo', 'PADRÓN DE SOCIOS')
@section('subtitulo', $titulo ?? '')

@section('content')
@php
    $numero_fila = 1;
    $totalSocios = $partners->count();
    $activos = $partners->filter(function($p) { return $p->state && $p->state->title == 'Activo'; })->count();
    $inactivos = $totalSocios - $activos;
    $clubes = $partners->groupBy('association_id')->count();
@endphp

<table class="main-table">
    <thead>
        <tr>
            <th style="width: 30px;">N°</th>
            <th style="width: 50px;">CÓDIGO</th>
            <th style="width: 120px;">APELLIDOS Y NOMBRES</th>
            <th style="width: 50px;">DNI</th>
            <th style="width: 120px;">CLUB DE MADRES</th>
            <th style="width: 60px;">FECHA INICIO</th>
            <th style="width: 50px;">ESTADO</th>
            <th style="width: 30px;">BENEF.</th>
        </tr>
    </thead>
    <tbody>
        @forelse($partners as $partner)
        <tr>
            <td class="text-center">{{ $numero_fila }}</td>
            <td class="text-center">{{ str_pad($partner->id, 4, '0', STR_PAD_LEFT) }}</td>
            <td>
                @if($partner->people)
                    {{ $partner->people->father_lastname }} {{ $partner->people->mother_lastname }}, {{ $partner->people->names }}
                @else
                    Sin nombre
                @endif
            </td>
            <td class="text-center">{{ $partner->people->dni ?? '-' }}</td>
            <td>{{ $partner->association->name ?? '-' }}</td>
            <td class="text-center">
                @if($partner->date_begin)
                    {{ date('d/m/Y', strtotime($partner->date_begin)) }}
                @else
                    -
                @endif
            </td>
            <td class="text-center">
                @if($partner->state && $partner->state->title == 'Activo')
                    ACTIVO
                @else
                    INACTIVO
                @endif
            </td>
            <td class="text-center">
                @if($tipo == 'beneficiarios' && $partner->beneficiaries)
                    {{ $partner->beneficiaries->count() }}
                @else
                    -
                @endif
            </td>
        </tr>
        @php $numero_fila++; @endphp
        @empty
        <tr>
            <td colspan="8" class="text-center" style="padding: 20px;">No se encontraron registros para este reporte.</td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($partners->isNotEmpty())
<table class="totales-table" style="width: 50%; margin-top: 15px; border-collapse: collapse; border: 2px solid #000; margin-left: auto; margin-right: auto;">
    <tbody>
        <tr style="background-color: #e0e0e0;">
            <td colspan="4" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">
                CUADRO RESUMEN DE SOCIOS
            </td>
        </tr>
        <tr style="background-color: #f5f5f5;">
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">ACTIVOS</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">INACTIVOS</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">CLUBES</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold; background-color: #d0d0d0;">TOTAL</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 10pt; font-weight: bold;">{{ $activos }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 10pt; font-weight: bold;">{{ $inactivos }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 10pt; font-weight: bold;">{{ $clubes }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; background-color: #e0e0e0;">{{ $totalSocios }}</td>
        </tr>
    </tbody>
</table>
@endif
@endsection
