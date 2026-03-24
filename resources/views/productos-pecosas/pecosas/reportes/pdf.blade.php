@extends('reportes.layout_patron')

@section('title', 'Reporte de Pecosas')
@section('titulo', 'PADRÓN DE PECOSAS')
@section('subtitulo', $titulo ?? '')

@section('content')
@php
    $numero_fila = 1;
    $totalPecosas = $pecosas->count();
    $clubesAtendidos = $pecosas->groupBy('association_id')->count();
    $ativas = $pecosas->filter(function($p) { return $p->state && $p->state->title == 'Activo'; })->count();
@endphp

<table class="main-table">
    <thead>
        <tr>
            <th style="width: 30px;">N°</th>
            <th style="width: 50px;">CÓDIGO</th>
            <th style="width: 60px;">N° PECOSA</th>
            <th style="width: 120px;">CLUB DE MADRES</th>
            <th style="width: 50px;">F. ENTREGA</th>
            <th style="width: 100px;">RESPONSABLE</th>
            <th style="width: 40px;">ESTADO</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pecosas as $pecosa)
        <tr>
            <td class="text-center">{{ $numero_fila }}</td>
            <td class="text-center">{{ str_pad($pecosa->id, 4, '0', STR_PAD_LEFT) }}</td>
            <td class="text-center">{{ $pecosa->pecosa_number }}</td>
            <td>{{ $pecosa->association->name ?? '-' }}</td>
            <td class="text-center">
                @if($pecosa->delivery_date)
                    {{ date('d/m/Y', strtotime($pecosa->delivery_date)) }}
                @else
                    -
                @endif
            </td>
            <td>
                @if($pecosa->managingPartner && $pecosa->managingPartner->people)
                    {{ $pecosa->managingPartner->people->names }} {{ $pecosa->managingPartner->people->father_lastname }}
                @else
                    -
                @endif
            </td>
            <td class="text-center">
                @if($pecosa->state && $pecosa->state->title == 'Activo')
                    ACTIVO
                @else
                    INACTIVO
                @endif
            </td>
        </tr>
        @php $numero_fila++; @endphp
        @empty
        <tr>
            <td colspan="7" class="text-center" style="padding: 20px;">No se encontraron registros para este reporte.</td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($pecosas->isNotEmpty())
<table class="totales-table" style="width: 40%; margin-top: 15px; border-collapse: collapse; border: 2px solid #000; margin-left: auto; margin-right: auto;">
    <tbody>
        <tr style="background-color: #e0e0e0;">
            <td colspan="2" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">
                CUADRO RESUMEN DE PECOSAS
            </td>
        </tr>
        <tr style="background-color: #f5f5f5;">
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">TOTAL PECOSAS</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">CLUBES ATENDIDOS</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; background-color: #e0e0e0;">{{ $totalPecosas }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold;">{{ $clubesAtendidos }}</td>
        </tr>
    </tbody>
</table>
@endif
@endsection
