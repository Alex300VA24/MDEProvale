@extends('reportes.layout_patron')

@section('title', 'Reporte de Beneficiarios')
@section('titulo', 'PADRÓN DE BENEFICIARIOS')
@section('subtitulo', $titulo ?? '')

@section('content')
@php
    $numero_fila = 1;
    $totalBeneficiarios = $beneficiaries->count();
    $relaciones = $beneficiaries->groupBy('relationship_id')->map->count();
@endphp

<table class="main-table">
    <thead>
        <tr>
            <th style="width: 30px;">N°</th>
            <th style="width: 50px;">CÓDIGO</th>
            <th style="width: 120px;">APELLIDOS Y NOMBRES</th>
            <th style="width: 50px;">DNI</th>
            <th style="width: 120px;">SOCIO TITULAR</th>
            <th style="width: 60px;">PARENTESCO</th>
            <th style="width: 50px;">FECHA NAC.</th>
            <th style="width: 80px;">SEXO</th>
        </tr>
    </thead>
    <tbody>
        @forelse($beneficiaries as $beneficiario)
        <tr>
            <td class="text-center">{{ $numero_fila }}</td>
            <td class="text-center">{{ str_pad($beneficiario->id, 4, '0', STR_PAD_LEFT) }}</td>
            <td>
                @if($beneficiario->person)
                    {{ $beneficiario->person->father_lastname }} {{ $beneficiario->person->mother_lastname }}, {{ $beneficiario->person->names }}
                @else
                    Sin nombre
                @endif
            </td>
            <td class="text-center">{{ $beneficiario->person->dni ?? '-' }}</td>
            <td>
                @if($beneficiario->partner && $beneficiario->partner->people)
                    {{ $beneficiario->partner->people->father_lastname }} {{ $beneficiario->partner->people->mother_lastname }}, {{ $beneficiario->partner->people->names }}
                @else
                    -
                @endif
            </td>
            <td class="text-center">{{ $beneficiario->relationship->title ?? '-' }}</td>
            <td class="text-center">
                @if($beneficiario->person && $beneficiario->person->birthdate)
                    {{ date('d/m/Y', strtotime($beneficiario->person->birthdate)) }}
                @else
                    -
                @endif
            </td>
            <td class="text-center">
                @if($beneficiario->person && $beneficiario->person->sex)
                    {{ $beneficiario->person->sex == 'M' ? 'MASCULINO' : 'FEMENINO' }}
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

@if($beneficiaries->isNotEmpty())
<table class="totales-table" style="width: 40%; margin-top: 15px; border-collapse: collapse; border: 2px solid #000; margin-left: auto; margin-right: auto;">
    <tbody>
        <tr style="background-color: #e0e0e0;">
            <td colspan="2" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">
                CUADRO RESUMEN DE BENEFICIARIOS
            </td>
        </tr>
        <tr style="background-color: #f5f5f5;">
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">TOTAL BENEFICIARIOS</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 8pt; font-weight: bold;">SOCIOS</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold; background-color: #e0e0e0;">{{ $totalBeneficiarios }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 10pt; font-weight: bold;">{{ $beneficiaries->groupBy('partner_id')->count() }}</td>
        </tr>
    </tbody>
</table>
@endif
@endsection
