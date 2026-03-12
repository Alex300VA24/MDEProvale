<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Padrón de Club de Madres - Resoluciones de Reconocimiento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: landscape;
            margin: 8mm;
            counter-increment: page;
        }
        
        @page :first {
            counter-reset: page 0;
        }
        
        tbody {
            display: table-row-group;
        }
        
        thead {
            display: table-header-group;
        }
        
        tr {
            page-break-inside: avoid;
        }
        
        .page-number::before {
            content: counter(page);
        }
        
        .header-table {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .header-cell {
            display: table-cell;
            vertical-align: middle;
        }
        
        .logo-cell {
            width: 70px;
            text-align: center;
        }
        
        .title-cell {
            text-align: center;
            padding: 5px;
        }
        
        .title-cell h2 {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .title-cell h3 {
            font-size: 9pt;
            font-weight: bold;
        }
        
        .info-cell {
            width: 120px;
            text-align: right;
            font-size: 7pt;
            padding-right: 10px;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 5px;
        }
        
        .main-table th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 3px 2px;
            font-size: 6pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        
        .main-table td {
            border: 1px solid #000;
            padding: 2px;
            font-size: 6pt;
            vertical-align: middle;
        }
        
        .section-header {
            background-color: #d0d0d0;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            font-size: 8pt;
        }
        
        .subsection-header {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: center;
            padding: 3px;
            font-size: 7pt;
        }
        
        .row-number {
            width: 28px;
            text-align: center;
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .codigo-col {
            width: 28px;
            text-align: center;
            font-weight: bold;
        }
        
        .tipo-col {
            width: 28px;
            text-align: center;
            font-size: 5pt;
        }
        
        .club-name {
            text-align: left;
            padding-left: 3px;
            max-width: 180px;
        }
        
        .direccion-col {
            text-align: left;
            padding-left: 3px;
            max-width: 150px;
        }
        
        .sector-col {
            text-align: center;
            width: 70px;
        }
        
        .benef-col {
            width: 35px;
            text-align: center;
            font-weight: bold;
        }
        
        .presidenta-col {
            text-align: left;
            padding-left: 3px;
            max-width: 120px;
        }
        
        .resolucion-col {
            width: 65px;
            text-align: center;
            font-size: 5pt;
        }
        
        .fecha-col {
            width: 65px;
            text-align: center;
            font-size: 5pt;
        }
        
        .local-col {
            width: 60px;
            text-align: center;
            font-size: 5pt;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-left {
            text-align: left;
        }
        
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .summary-section {
            margin-top: 5px;
            border: 1px solid #000;
            padding: 5px;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .summary-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 7pt;
        }
    </style>
</head>
<body>
    <!-- MAIN TABLE -->
    <table class="main-table">
        <thead>
            <tr>
                <td colspan="14" style="border: none; padding: 3px;">
                    <table class="header-table" style="width: 100%;">
                        <tr>
                            <td style="text-align: left; vertical-align: middle;">
                                <img src="{{ public_path('img/muni2.png') }}" style="width: 50px; height: auto; vertical-align: middle; margin-right: 5px;" alt="Logo">
                                <div style="display: inline-block; vertical-align: middle; text-align: left; width: 80px;">
                                    <div style="font-size: 6pt; font-weight: bold;">MUNICIPALIDAD DISTRITAL</div>
                                    <div style="font-size: 6pt; font-weight: bold;">DE LA ESPERANZA</div>
                                    <div style="font-size: 6pt;">O.F. Vaso de Leche</div>
                                </div>
                            </td>
                            <td style="text-align: center; vertical-align: middle; line-height: 1.2;">
                                <div style="font-size: 11pt; font-weight: bold; margin: 0;">
                                    PADRÓN DE CLUB DE MADRES Y/O COMITÉS DEL PROGRAMA VASO DE LECHE
                                </div>
                                <div style="font-size: 9pt; font-weight: bold; margin: 0;">RESOLUCIONES DE RECONOCIMIENTO</div>
                            </td>
                            <td style="width: 90px; text-align: right; vertical-align: top; font-size: 7pt;">
                                <div style="font-weight: bold; margin-bottom: 3px;">PÁG: <span class="page-number"></span></div>
                                <div>FECHA: {{ $fecha ?? date('d/m/Y') }}</div>
                                <div>HORA: {{ $hora ?? date('H:i:s') }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <th colspan="3" style="width: 80px;">Código</th>
                <th rowspan="2" style="width: 180px;">CLUB DE MADRES</th>
                <th rowspan="2" style="width: 150px;">DIRECCIÓN</th>
                <th rowspan="2" style="width: 70px;">SECTOR</th>
                <th rowspan="2" style="width: 35px;">BENEF.</th>
                <th rowspan="2" style="width: 120px;">PRESIDENTA</th>
                <th colspan="3" style="background-color: #d8d8d8;">RESOLUCIONES</th>
                <th colspan="2" style="background-color: #d8d8d8;">VIGENCIA</th>
                <th rowspan="2" style="width: 60px;">LOCAL</th>
            </tr>
            <tr>
                <th style="width: 28px;">ZONA</th>
                <th style="width: 28px;">C.S.</th>
                <th style="width: 28px;">R.S.</th>
                <th style="width: 70px;">Nº (Res.)</th>
                <th style="width: 65px;">Nº (Res. 2)</th>
                <th style="width: 65px;">Nº (Res. 3)</th>
                <th style="width: 65px;">Inicio</th>
                <th style="width: 65px;">Término</th>
            </tr>
        </thead>
        <tbody>
            @php
                $zonas = $zonas ?? [];
                $numero_fila = 1;
            @endphp
            
            @foreach($zonas as $zona)
                @php
                    $sectoresList = isset($zona['lista_sectores']) && is_array($zona['lista_sectores']) ? implode(', ', $zona['lista_sectores']) : '';
                    $todosLosClubes = [];
                    if (isset($zona['sectores'])) {
                        foreach ($zona['sectores'] as $sector) {
                            foreach ($sector['clubes'] as $club) {
                                $todosLosClubes[] = $club;
                            }
                        }
                    } elseif (isset($zona['clubes'])) {
                        $todosLosClubes = $zona['clubes'];
                    }
                    usort($todosLosClubes, function($a, $b) {
                        return strcmp($a['codigo'] ?? '', $b['codigo'] ?? '');
                    });
                    $numero_zona = 1;
                @endphp
                
                <!-- ZONA HEADER -->
                <tr>
                    <td colspan="14" class="section-header">
                        {{ str_pad($zona['numero'] ?? 0, 2, '0', STR_PAD_LEFT) }} - {{ $sectoresList }}
                    </td>
                </tr>
                
                @foreach($todosLosClubes as $club)
                    <tr>
                        <td class="row-number">{{ $numero_zona }}</td>
                        <td class="codigo-col">{{ $club['codigo'] ?? '' }}</td>
                        <td class="codigo-col">{{ $club['razon_social'] ?? '' }}</td>
                        <td class="club-name">{{ $club['nombre'] ?? '' }}</td>
                        <td class="direccion-col">{{ $club['direccion'] ?? '' }}</td>
                        <td class="sector-col">{{ $club['sector'] ?? '' }}</td>
                        <td class="benef-col">{{ $club['beneficiarios'] ?? '' }}</td>
                        <td class="presidenta-col">{{ $club['presidenta'] ?? '' }}</td>
                        <td class="resolucion-col">{{ $club['resolucion_1'] ?? '' }}</td>
                        <td class="resolucion-col">{{ $club['resolucion_2'] ?? '' }}</td>
                        <td class="resolucion-col">{{ $club['resolucion_3'] ?? '' }}</td>
                        <td class="fecha-col">{{ $club['fecha_inicio'] ?? '' }}</td>
                        <td class="fecha-col">{{ $club['fecha_termino'] ?? '' }}</td>
                        <td class="local-col">{{ $club['local'] ?? '' }}</td>
                    </tr>
                    @php $numero_zona++; @endphp
                @endforeach
            @endforeach
            
            <!-- TOTALES GENERALES -->
            @if(isset($totales_generales))
                <tr style="background-color: #e0e0e0; font-weight: bold;">
                    <td colspan="3" style="text-align: right; padding-right: 5px;">Total OSB:</td>
                    <td class="text-center">{{ $totales_generales['total_osb'] ?? '' }}</td>
                    <td style="text-align: right; padding-right: 5px;">Total CVL:</td>
                    <td class="text-center">{{ $totales_generales['total_cvl'] ?? '' }}</td>
                    <td style="text-align: right; padding-right: 5px;">Total CDM:</td>
                    <td class="text-center">{{ $totales_generales['total_cdm'] ?? '' }}</td>
                    <td colspan="2" style="text-align: center; padding: 3px; font-size: 8pt;">TOTAL ACUMULADO:</td>
                    <td colspan="4" style="text-align: center; font-size: 9pt;">{{ $totales_generales['total_acumulado'] ?? '' }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>