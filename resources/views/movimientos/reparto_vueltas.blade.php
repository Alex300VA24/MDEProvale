<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reparto por vueltas PVL</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: landscape; margin: 1.5mm 3mm; }
        body { margin: 0; padding: 3mm; font-family: Arial, sans-serif; font-size: 7pt; line-height: 1.15; }
        footer { position: fixed; bottom: -4px; left: 0; right: 0; height: 20px; text-align: center; font-size: 8pt; }
        .pagenum:before { content: counter(page); }
        .page-container { width: 100%; padding: 10px; }
        .route { page-break-after: always; }
        .route:last-child { page-break-after: auto; }
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tr { page-break-inside: avoid; }
        .main-table { width: 98%; border-collapse: collapse; border: 2px solid #000; margin-left: 1%; margin-bottom: 5px; table-layout: fixed; }
        .header-table { width: 98%; margin-left: 1%; border-collapse: collapse; border-spacing: 0; margin-bottom: 8px; }
        .main-table th { background-color: #d8d8d8; border: 1px solid #000; padding: 4px 2px; font-size: 7.5pt; font-weight: bold; text-align: center; vertical-align: middle; line-height: 1.15; }
        .main-table th.yellow, .main-table td.yellow { background-color: #ffff00; }
        .main-table td { border: 1px solid #000; padding: 4px 3px; font-size: 7pt; vertical-align: middle; text-align: center; height: 9mm; overflow-wrap: break-word; }
        .col-n { background-color: #f0f0f0; font-weight: bold; }
        .left { text-align: left !important; padding-left: 3px; }
        .numeric { white-space: nowrap; }
        .bold { font-weight: bold; }
        .route-title { width: 98%; margin-left: 1%; font-size: 9pt; font-weight: bold; margin-bottom: 3px; }
        .subtotal td { font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; height: 7mm; }
        .subtotal td.label { text-align: right !important; padding-right: 8px; background: #fff; }
        .summary { width: 45%; margin-left: 1%; margin-top: 12px; border-collapse: collapse; }
        .summary th, .summary td { border: 1px solid #000; padding: 4px 6px; font-size: 7.5pt; text-align: center; }
        .summary th { background-color: #d8d8d8; }
        .summary td.label { font-weight: bold; text-align: left; }
    </style>
</head>
<body>
@php
    $meses_es = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $nombreMes = strtoupper($meses_es[(int) $month] ?? '');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);
    $issued = $issuedAt ?? now();
    $correlative = 0;
    $totalRoutes = count($vueltas);
@endphp
<footer><strong>PÁG <span class="pagenum"></span></strong></footer>

@forelse($vueltas as $routeIndex => $route)
<section class="route">
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
                    LECHE EVAPORADA ENTERA Y HOJUELA DE QUINUA AVENA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES
                </div>
            </td>
            <td style="width: 130px; text-align: right; vertical-align: top; font-size: 6.5pt; line-height: 1.4; padding: 0;">
                <div style="font-weight: bold;">FECHA: {{ $issued->format('d/m/Y') }}</div>
                <div style="font-weight: bold; margin-top: 4px;">HORA: {{ $issued->format('H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="route-title">VUELTA N° {{ $route['vuelta'] }}</div>
    <table class="main-table">
        <colgroup><col style="width:3%"><col style="width:4.5%"><col style="width:17%"><col style="width:15%"><col style="width:18%"><col style="width:10%"><col style="width:5%"><col style="width:5.5%"><col style="width:5%"><col style="width:5%"><col style="width:5.5%"><col style="width:5%"><col style="width:5%"></colgroup>
        <thead>
            <tr>
                <th style="width:3%">N.°</th>
                <th style="width:4.5%">CÓD.</th>
                <th style="width:17%">CLUB DE MADRES</th>
                <th style="width:15%">PRESIDENTA</th>
                <th style="width:18%">DIRECCIÓN</th>
                <th style="width:10%">SECTOR</th>
                <th style="width:5%">BENEF.</th>
                <th class="yellow" style="width:5.5%">LECHE</th>
                <th style="width:5%">CAJAS</th>
                <th style="width:5%">TARROS</th>
                <th class="yellow" style="width:5.5%">HOJUELA</th>
                <th style="width:5%">SACOS</th>
                <th style="width:5%">KILOS</th>
            </tr>
        </thead>
        <tbody>
        @foreach($route['clubs'] as $club)
            @php $correlative++; @endphp
            <tr>
                <td class="col-n">{{ str_pad($correlative, 2, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $club['codigo'] }}</td>
                <td class="left">{{ $club['nombre'] }}</td>
                <td class="left">{{ $club['presidenta'] }}</td>
                <td class="left">{{ $club['direccion'] }}</td>
                <td class="left">{{ $club['sector'] }}</td>
                <td class="numeric bold">{{ $club['beneficiarios'] }}</td>
                <td class="numeric bold yellow">{{ $club['leche_total'] }}</td>
                <td class="numeric bold">{{ $club['leche_cajas'] }}</td>
                <td class="numeric bold">{{ $club['leche_tarros'] }}</td>
                <td class="numeric bold yellow">{{ $club['hojuelas_kg'] }}</td>
                <td class="numeric bold">{{ $club['hojuelas_sacos'] }}</td>
                <td class="numeric bold">{{ $club['hojuelas_kilos'] }}</td>
            </tr>
        @endforeach
            <tr class="subtotal">
                <td colspan="6" class="label">SUBTOTAL VUELTA {{ $route['vuelta'] }}</td>
                <td class="numeric">{{ $route['total_beneficiarios'] }}</td>
                <td class="numeric yellow">{{ $route['total_leche'] }}</td>
                <td class="numeric yellow">{{ $route['leche_cajas'] }}</td>
                <td class="numeric yellow">{{ $route['leche_tarros'] }}</td>
                <td class="numeric yellow">{{ $route['total_hojuelas'] }}</td>
                <td class="numeric yellow">{{ $route['hojuelas_sacos'] }}</td>
                <td class="numeric yellow">{{ $route['hojuelas_kilos'] }}</td>
            </tr>
        @if($routeIndex === $totalRoutes - 1)
            <tr class="subtotal">
                <td colspan="6" class="label">TOTAL GENERAL</td>
                <td class="numeric">{{ $total_beneficiarios }}</td>
                <td class="numeric yellow">{{ $total_leche_tarros }}</td>
                <td class="numeric yellow">{{ $total_leche_cajas }}</td>
                <td class="numeric yellow">{{ $total_leche_sueltos }}</td>
                <td class="numeric yellow">{{ $total_hojuelas_kg }}</td>
                <td class="numeric yellow">{{ $total_hojuelas_sacos }}</td>
                <td class="numeric yellow">{{ $total_hojuelas_sueltos }}</td>
            </tr>
        @endif
        </tbody>
    </table>

    @if($routeIndex === $totalRoutes - 1)
    <table class="summary">
        <thead>
            <tr>
                <th style="width:34%"></th>
                <th>TOTAL</th>
                <th>CAJAS ({{ $configuration['milk_cans_per_box'] }} TARROS) / SACOS ({{ $configuration['oat_kg_per_sack'] }} KG)</th>
                <th>TARROS / KILOS SUELTOS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label">TARROS DE LECHE</td>
                <td>{{ number_format($total_leche_tarros) }}</td>
                <td>{{ $total_leche_cajas }}</td>
                <td>{{ $total_leche_sueltos }}</td>
            </tr>
            <tr>
                <td class="label">BOLSAS DE AVENA</td>
                <td>{{ number_format($total_hojuelas_kg) }}</td>
                <td>{{ $total_hojuelas_sacos }}</td>
                <td>{{ $total_hojuelas_sueltos }}</td>
            </tr>
        </tbody>
    </table>
    @endif
</div>
</section>
@empty
<div class="page-container">
    <p style="text-align:center">No hay comités con beneficiarios para este período.</p>
</div>
@endforelse
</body>
</html>
