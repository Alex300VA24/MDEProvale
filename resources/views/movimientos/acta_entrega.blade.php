<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta condicional PVL</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: landscape; margin: 1.5mm 3mm; }
        body { margin: 0; padding: 3mm; font-family: Arial, sans-serif; font-size: 7pt; line-height: 1.15; }
        footer { position: fixed; bottom: -4px; left: 0; right: 0; height: 20px; text-align: center; font-size: 8pt; }
        .pagenum:before { content: counter(page); }
        .page-container { width: 100%; padding: 10px; }
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tr { page-break-inside: avoid; }
        .main-table { width: 98%; border-collapse: collapse; border: 2px solid #000; margin-left: 1%; margin-bottom: 5px; table-layout: fixed; }
        .header-table { width: 98%; margin-left: 1%; border-collapse: collapse; border-spacing: 0; margin-bottom: 8px; }
        .main-table th { background-color: #d8d8d8; border: 1px solid #000; padding: 4px 2px; font-size: 7.5pt; font-weight: bold; text-align: center; vertical-align: middle; line-height: 1.15; }
        .main-table td { border: 1px solid #000; padding: 4px 3px; font-size: 7pt; vertical-align: middle; text-align: center; height: 9mm; overflow-wrap: break-word; }
        .col-n { background-color: #f0f0f0; font-weight: bold; }
        .left { text-align: left !important; padding-left: 3px; }
        .numeric { white-space: nowrap; }
        .warning { width: 98%; margin-left: 1%; margin-top: 6px; padding: 5px 8px; border: 1px solid #000; font-size: 7pt; }
    </style>
</head>
<body>
@php
    $meses_es = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $nombreMes = strtoupper($meses_es[(int) $month] ?? '');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);
    $issued = $issuedAt ?? now();
    $isComplete = $productMode === 'complete';
    $showMilk = $productMode !== 'oat';
    $showOat = $productMode !== 'milk';
    $productTitle = $isComplete
        ? 'LECHE EVAPORADA ENTERA Y HOJUELA DE QUINUA AVENA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES'
        : ($showMilk ? 'LECHE EVAPORADA ENTERA' : 'HOJUELA DE QUINUA AVENA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES');
    $productWidth = $isComplete ? 6.5 : 6.5;
    $signWidth = 27.5 - $productWidth * ($isComplete ? 2 : 1);
@endphp
<footer><strong>PÁG <span class="pagenum"></span></strong></footer>

<div class="page-container">
    <table class="header-table">
        <tr>
            <td style="width: 150px; text-align: left; vertical-align: middle; padding: 0;">
                <img src="{{ public_path('img/muni2.png') }}" style="width: 50px; height: auto; vertical-align: middle; margin-right: 5px;" alt="Logo">
                <div style="display: inline-block; vertical-align: middle; text-align: left; width: 80px;">
                    <div style="font-size: 6pt; font-weight: bold;">MUNICIPALIDAD DISTRITAL</div>
                    <div style="font-size: 6pt; font-weight: bold;">DE LA ESPERANZA</div>
                    <div style="font-size: 6pt;">O.F. Vaso de Leche</div>
                </div>
            </td>
            <td style="text-align: center; vertical-align: middle; line-height: 1.2; padding: 0;">
                <div style="font-size: 11pt; font-weight: bold; text-transform: uppercase;">
                    PROGRAMACIÓN DE ENTREGA DE LOS PRODUCTOS DEL PROGRAMA VASO DE LECHE (PERIODO DEL 01 AL {{ sprintf('%02d', $daysInMonth) }} {{ $nombreMes }} {{ $year }})
                </div>
                <div style="font-size: 8pt; font-weight: bold; margin-top: 2px; text-transform: uppercase;">
                    {{ $productTitle }}
                </div>
            </td>
            <td style="width: 130px; text-align: right; vertical-align: top; font-size: 6.5pt; line-height: 1.4; padding: 0;">
                <div style="font-weight: bold;">FECHA: {{ $issued->format('d/m/Y') }}</div>
                <div style="font-weight: bold; margin-top: 4px;">HORA: {{ $issued->format('H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width:3%">N.°</th>
                <th style="width:4.5%">CÓD.</th>
                <th style="width:17%">CLUB DE MADRES</th>
                <th style="width:15%">PRESIDENTA</th>
                <th style="width:18%">DIRECCIÓN</th>
                <th style="width:10%">SECTOR</th>
                <th style="width:5%">BENEF.</th>
                @if($showMilk)<th style="width:{{ $productWidth }}%">TOTAL LECHE (TARROS)</th>@endif
                @if($showOat)<th style="width:{{ $productWidth }}%">TOTAL HOJUELA (KG)</th>@endif
                <th style="width:{{ $signWidth }}%">FIRMA</th>
            </tr>
        </thead>
        <tbody>
        @foreach($associations as $index => $club)
            <tr>
                <td class="col-n">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $club['codigo'] }}</td>
                <td class="left">{{ $club['nombre'] }}</td>
                <td class="left">{{ $club['presidenta'] }}</td>
                <td class="left">{{ $club['direccion'] }}</td>
                <td class="left">{{ $club['sector'] }}</td>
                <td class="numeric" style="font-weight: bold;">{{ $club['beneficiarios'] }}</td>
                @if($showMilk)<td class="numeric">{{ $club['leche_total'] }}</td>@endif
                @if($showOat)<td class="numeric">{{ $club['hojuelas_kg'] }}</td>@endif
                <td></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if(!$isComplete)
    <div class="warning"><strong>Constancia de contingencia:</strong> esta acta acredita recepción exclusiva de {{ $showMilk ? 'leche evaporada entera' : 'hojuela' }}. No acredita recepción del producto pendiente.</div>
    @endif
</div>
</body>
</html>
