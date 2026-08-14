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
            margin: 1.5mm 3mm 1.5mm 3mm;
        }
        
        body {
            margin: 0;
            padding: 3mm;
            font-family: Arial, sans-serif;
        }

        /* Footer con número de página */
        footer {
            position: fixed;
            bottom: -4px;
            left: 0px;
            right: 0px;
            height: 20px;
            text-align: center;
            font-size: 8pt;
            font-family: Arial, sans-serif;
        }

        .pagenum:before {
            content: counter(page);
        }
        
        .page-container {
            width: 100%;
            padding: 10px;
        }
        
        tbody {
            display: table-row-group;
        }
        
        thead {
            display: table-header-group;
        }
        
        tbody {
            display: table-row-group;
        }
        
        tr {
            page-break-inside: avoid;
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
            border-right: none;
            border-bottom: none;
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
            background-color: #b0b0b0;
            font-weight: bold;
            text-align: center;
            padding: 8px 4px;
            font-size: 9pt;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
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
            max-width: 100px;
        }
        
        .sector-col {
            text-align: center;
            width: 70px;
        }
        
        .benef-col {
            width: 35px;
            text-align: center;
            font-weight: bold;
            padding: 0px;
        }
        
        .presidenta-col {
            text-align: left;
            padding-left: 3px;
            max-width: 120px;
        }

        /* Columnas de resoluciones */
        .resolucion-col {
            width: 40px !important;
            max-width: 40px !important;
            text-align: center;
            font-size: 5pt;
            padding: 0px !important;
            white-space: nowrap;
        }

        /* Columnas de fechas */
        .fecha-col {
            width: 45px !important;
            max-width: 45px !important;
            text-align: center;
            font-size: 5pt;
            padding: 0px !important;
            white-space: nowrap;
        }
        
        .local-col {
            width: 50px;
            text-align: center;
            font-size: 5pt;
            padding: 0px;
            border-right: 1px solid #000;
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
        
        .page-break {
            page-break-after: always;
        }
        
        .totales-table {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <!-- Footer con número de página -->
    <footer>
        <strong>PÁG <span class="pagenum"></span></strong>
    </footer>
    <div class="page-container">
    <!-- MAIN TABLE -->
    <table class="main-table">
        <thead>
            <!-- HEADER ROW - Se repite en cada página -->
            <tr>
                <td colspan="14" style="border: none; padding: 0;">
                    <table class="header-table" style="width: 100%; border-collapse: collapse; border-spacing: 0;">
                        <tr>
                            <!-- Logo y texto lateral izquierdo -->
                            <td style="width: 150px; text-align: left; vertical-align: middle; padding: 0;">
                                <img src="{{ public_path('img/muni2.png') }}" 
                                    style="width: 50px; height: auto; vertical-align: middle; margin-right: 5px;" 
                                    alt="Logo">
                                <div style="display: inline-block; vertical-align: middle; text-align: left; width: 80px;">
                                    <div style="font-size: 6pt; font-weight: bold;">MUNICIPALIDAD DISTRITAL</div>
                                    <div style="font-size: 6pt; font-weight: bold;">DE LA ESPERANZA</div>
                                    <div style="font-size: 6pt;">O.F. Vaso de Leche</div>
                                </div>
                            </td>

                            <!-- Título central -->
                            <td style="text-align: center; vertical-align: middle; line-height: 1.2; padding: 0; width: 60%;">
                                <div style="font-size: 11pt; font-weight: bold; margin: 0;">
                                    PADRÓN DE CLUB DE MADRES Y/O COMITÉS DEL PROGRAMA VASO DE LECHE
                                </div>
                                <div style="font-size: 9pt; font-weight: bold; margin: 0;">
                                    RESOLUCIONES DE RECONOCIMIENTO
                                </div>
                            </td>

                            <!-- Datos laterales derechos -->
                            <td style="width: 120px; text-align: right; vertical-align: top; font-size: 7pt; padding: 0;">
                                <!-- <div style="font-weight: bold; margin-bottom: 3px;">PÁG: </div> -->
                                <div>FECHA: {{ $fecha ?? date('d/m/Y') }}</div>
                                <div>HORA: {{ $hora ?? date('H:i:s') }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <!-- TÍTULOS DE COLUMNAS - Se repiten en cada página -->
            <tr>
                <th colspan="3" style="width: 80px;">Código</th>
                <th rowspan="2" style="width: 110px;">CLUB DE MADRES</th>
                <th rowspan="2" style="width: 90px;">DIRECCIÓN</th>
                <th rowspan="2" style="width: 70px;">SECTOR</th>
                <th rowspan="2" style="width: 35px;">BENEF.</th>
                <th rowspan="2" style="width: 110px;">PRESIDENTA</th>
                <th colspan="3" style="background-color: #d8d8d8; width: 90px;">RESOLUCIONES</th>
                <th colspan="2" style="background-color: #d8d8d8; width: 100px;">VIGENCIA</th>
                <th rowspan="2" style="width: 50px;">LOCAL</th>
                <th rowspan="2" style="width: 10px; border-top: none; border-bottom: none; border-right: none; border-left: 1px solid #000; background-color: white;">&nbsp;</th>
            </tr>
            <tr>
                <th style="width: 28px;">ZONA</th>
                <th style="width: 28px;">C.S.</th>
                <th style="width: 28px;">R.S.</th>
                <th style="width: 35px; max-width: 35px; padding: 0px;">N° (Res. 1)</th>
                <th style="width: 35px; max-width: 35px; padding: 0px;">N° (Res. 2)</th>
                <th style="width: 35px; max-width: 35px; padding: 0px;">N° (Res. 3)</th>
                <th style="width: 45px; max-width: 45px; padding: 0px;">Inicio</th>
                <th style="width: 45px; max-width: 45px; padding: 0px;">Término</th>
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
                    <td colspan="1" class="section-header">
                        {{ str_pad($zona['numero'] ?? 0, 2, '0', STR_PAD_LEFT) }}
                    </td>
                    <td colspan="13" class="section-header">
                        <strong>{{ mb_strtoupper($sectoresList) }}</strong>
                    </td>
                    <td style="border-top: none; border-bottom: none; border-right: none; border-left: 1px solid #000; background-color: white; width: 10px;">&nbsp;</td>
                </tr>
                
                @foreach($todosLosClubes as $club)
                    <tr>
                        <td class="row-number">{{ $numero_zona }}</td>
                        <td class="codigo-col">{{ $club['codigo'] ?? '' }}</td>
                        <td class="codigo-col">{{ $club['razon_social'] ?? '' }}</td>
                        <td class="club-name">{{ $club['nombre'] ?? '' }}</td>
                        <td class="direccion-col">{{ $club['direccion'] ?? '' }}</td>
                        <td class="sector-col">{{ mb_strtoupper($club['sector']) ?? '' }}</td>
                        <td class="benef-col">{{ $club['beneficiarios'] ?? '' }}</td>
                        <td class="presidenta-col">{{ $club['presidenta'] ?? '' }}</td>
                        <td class="resolucion-col">{{ $club['resolucion_1'] ?? '' }}</td>
                        <td class="resolucion-col">{{ $club['resolucion_2'] ?? '' }}</td>
                        <td class="resolucion-col">{{ $club['resolucion_3'] ?? '' }}</td>
                        <td class="fecha-col">{{ $club['fecha_inicio'] ?? '' }}</td>
                        <td class="fecha-col">{{ $club['fecha_termino'] ?? '' }}</td>
                        <td class="local-col">{{ mb_strtoupper($club['local']) ?? '' }}</td>
                        <td style="width: 10px; border-top: none; border-bottom: none; border-right: none; border-left: 1px solid #000; background-color: white;">&nbsp;</td>
                    </tr>
                    @php $numero_zona++; @endphp
                @endforeach
                
                <!-- TOTALES ZONA -->
                @php
                    $totalOsbZona = 0;
                    $totalCvlZona = 0;
                    $totalCdmZona = 0;
                    if (isset($zona['totales_rs'])) {
                        foreach ($zona['totales_rs'] as $rs => $count) {
                            $rsKey = strtoupper(trim($rs));
                            if ($rsKey === 'OSB') $totalOsbZona = $count;
                            elseif ($rsKey === 'CVL') $totalCvlZona = $count;
                            elseif ($rsKey === 'CDM') $totalCdmZona = $count;
                        }
                    }
                @endphp
                <tr style="background-color: #d0d0d0; font-weight: bold;">
                    <td colspan="14" style="text-align: center; padding: 4px; font-size: 7pt;">
                        Total OSB: {{ $totalOsbZona }} &nbsp;&nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;&nbsp; Total CVL: {{ $totalCvlZona }} &nbsp;&nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;&nbsp; Total CDM: {{ $totalCdmZona }} &nbsp;&nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;&nbsp; Total Beneficiarios: {{ $zona['total_beneficiarios'] ?? 0 }}
                    </td>
                    <td style="border-top: none; border-bottom: none; border-right: none; border-left: 1px solid #000; background-color: white; width: 10px;">&nbsp;</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <!-- TABLA DE TOTALES -->
    @if(isset($totales_generales))
    <table class="totales-table" style="width: 60%; margin-top: 15px; border-collapse: collapse; border: 2px solid #000; margin-left: auto; margin-right: auto;">

        <tbody>
            <tr style="background-color: #e0e0e0;">
                <td colspan="5" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 10pt; font-weight: bold;">
                    CUADRO RESUMEN DE ORGANIZACIONES
                </td>
            </tr>
            <tr style="background-color: #f5f5f5;">
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">OSB</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">CVL</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">CDM</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold;">BENEFICIARIOS</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 9pt; font-weight: bold; background-color: #d0d0d0;">TOTAL</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold;">{{ $totales_rs['OSB'] ?? 0 }}</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold;">{{ $totales_rs['CVL'] ?? 0 }}</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold;">{{ $totales_rs['CDM'] ?? 0 }}</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 11pt; font-weight: bold;">{{ $totales_generales['total_beneficiarios'] ?? 0 }}</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 12pt; font-weight: bold; background-color: #e0e0e0;">{{ $totales_generales['total_acumulado'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>
    @endif
    </div>
</body>
</html>