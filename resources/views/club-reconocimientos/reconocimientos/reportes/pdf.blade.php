@extends('reportes.layout_patron')

@section('title', 'Reporte de Reconocimientos')
@section('titulo', 'PADRÓN DE RECONOCIMIENTOS')
@section('subtitulo', $titulo ?? '')

@section('content')
@php
    $numero_fila = 1;
    $totalResoluciones = $resolutions->count();
    $vigentes = $resolutions->filter(function($r) { 
        return $r->date_end && $r->date_end >= date('Y-m-d'); 
    })->count();
    $vencidas = $totalResoluciones - $vigentes;
@endphp

<table class="main-table">
    <thead>
        <tr>
            <th style="width: 30px;">N°</th>
            <th style="width: 80px;">DOCUMENTO</th>
            <th style="width: 50px;">F. EMISIÓN</th>
            <th style="width: 50px;">F. INICIO</th>
            <th style="width: 50px;">F. TÉRMINO</th>
            <th style="width: 50px;">ESTADO</th>
            <th style="width: 150px;">COMITÉS ASOCIADOS</th>
        </tr>
    </thead>
    <tbody>
        @forelse($resolutions as $resolution)
        <tr>
            <td class="text-center">{{ $numero_fila }}</td>
            <td class="text-center">{{ $resolution->document }}</td>
            <td class="text-center">
                @if($resolution->date_document)
                    {{ date('d/m/Y', strtotime($resolution->date_document)) }}
                @else
                    -
                @endif
            </td>
            <td class="text-center">
                @if($resolution->date_start)
                    {{ date('d/m/Y', strtotime($resolution->date_start)) }}
                @else
                    -
                @endif
            </td>
            <td class="text-center">
                @if($resolution->date_end)
                    {{ date('d/m/Y', strtotime($resolution->date_end)) }}
                @else
                    -
                @endif
            </td>
            <td class="text-center">
                @if($resolution->state)
                    @if($resolution->state->abbreviation == 'A')
                        ACTIVO
                    @else
                        INACTIVO
                    @endif
                @else
                    -
                @endif
            </td>
            <td>
                @if($resolution->associations && $resolution->associations->count() > 0)
                    @foreach($resolution->associations as $assoc)
                        • {{ $assoc->name }}<br>
                    @endforeach
                @else
                    -
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

@if($resolutions->isNotEmpty())
<table class="totales-table" style="width: 40%; margin-top: 15px; border-collapse: collapse; border: 2px solid #000; margin-left: auto; margin-right: auto;">
    <tbody>
        <tr style="background-color: #e0e0e0;">
            <td colspan="2" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">
                CUADRO RESUMEN DE RECONOCIMIENTOS
            </td>
        </tr>
        <tr style="background-color: #f5f5f5;">
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">VIGENTES</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">VENCIDAS</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; background-color: #e0e0e0;">{{ $vigentes }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold;">{{ $vencidas }}</td>
        </tr>
    </tbody>
</table>
@endif
@endsection
