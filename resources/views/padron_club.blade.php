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
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.1;
            padding: 5px;
        }
        
        .header {
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
            width: 25px;
            text-align: center;
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .codigo-col {
            width: 35px;
            text-align: center;
            font-weight: bold;
        }
        
        .tipo-col {
            width: 35px;
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
            width: 80px;
        }
        
        .benef-col {
            width: 35px;
            text-align: center;
            font-weight: bold;
        }
        
        .presidenta-col {
            text-align: left;
            padding-left: 3px;
            max-width: 140px;
        }
        
        .resolucion-col {
            width: 70px;
            text-align: center;
        }
        
        .fecha-col {
            width: 65px;
            text-align: center;
        }
        
        .local-col {
            width: 70px;
            text-align: center;
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
    <!-- HEADER -->
    <table style="width: 100%; margin-bottom: 8px;">
        <tr>
            <td style="width: 70px; text-align: center; vertical-align: middle;">
                <div style="width: 60px; height: 60px; border: 1px solid #000; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 7pt;">
                    LOGO
                </div>
            </td>
            
            <td style="text-align: center; padding: 5px; vertical-align: middle;">
                <div style="font-size: 9pt; font-weight: bold;">MUNICIPALIDAD</div>
                <div style="font-size: 9pt; font-weight: bold;">DISTRITAL DE LA ESPERANZA</div>
                <div style="font-size: 8pt; margin: 2px 0;">O.F. Vaso de Leche</div>
                <div style="font-size: 12pt; font-weight: bold; margin: 5px 0;">
                    PADRÓN DE CLUB DE MADRES Y/O COMITÉS DEL PROGRAMA VASO DE LECHE
                </div>
                <div style="font-size: 10pt; font-weight: bold;">RESOLUCIONES DE RECONOCIMIENTO</div>
            </td>
            
            <td style="width: 120px; text-align: right; vertical-align: top; padding-right: 10px; font-size: 7pt;">
                <div>PAGINA: {{ $pagina ?? '2' }}</div>
                <div>FECHA: {{ $fecha ?? date('d/m/Y') }}</div>
                <div>HORA: {{ $hora ?? date('H:i:s') }}</div>
            </td>
        </tr>
    </table>

    <!-- MAIN TABLE -->
    <table class="main-table">
        <thead>
            <tr>
                <th colspan="2" style="width: 60px;">Código</th>
                <th rowspan="2" style="width: 180px;">NOMBRE DEL CLUB DE MADRES</th>
                <th rowspan="2" style="width: 150px;">DIRECCIÓN</th>
                <th rowspan="2" style="width: 80px;">SECTOR</th>
                <th rowspan="2" style="width: 35px;">BENEF.</th>
                <th rowspan="2" style="width: 140px;">PRESIDENTA</th>
                <th colspan="3" style="background-color: #d8d8d8;">RESOLUCIONES</th>
                <th colspan="2" style="background-color: #d8d8d8;">VIGENCIA JUNTA DIRECTIVA</th>
                <th rowspan="2" style="width: 70px;">LOCAL</th>
            </tr>
            <tr>
                <th style="width: 30px;">ZONA</th>
                <th style="width: 30px;">COMITÉ SOCIAL</th>
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
                <!-- ZONA HEADER -->
                <tr>
                    <td colspan="13" class="section-header">
                        {{ str_pad($zona['numero'], 2, '0', STR_PAD_LEFT) }}&nbsp;&nbsp;&nbsp;{{ strtoupper($zona['nombre']) }}
                    </td>
                </tr>
                
                @if(isset($zona['supervisor']))
                    <tr>
                        <td colspan="13" class="subsection-header">SUPERVISOR/A:</td>
                    </tr>
                @endif
                
                @foreach($zona['clubes'] as $club)
                    <tr>
                        <td class="row-number">{{ $club['numero'] ?? $numero_fila }}</td>
                        <td class="codigo-col">{{ $club['codigo'] ?? '' }}</td>
                        <td class="tipo-col">{{ $club['tipo'] ?? 'CDM' }}</td>
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
                    @php $numero_fila++; @endphp
                @endforeach
                
                <!-- TOTALES POR ZONA -->
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; padding-right: 5px;">Total OSB:</td>
                    <td class="text-center">{{ $zona['total_osb'] ?? '' }}</td>
                    <td style="text-align: right; padding-right: 5px;">Total CVL:</td>
                    <td class="text-center">{{ $zona['total_cvl'] ?? '' }}</td>
                    <td style="text-align: right; padding-right: 5px;">Total CDM:</td>
                    <td class="text-center">{{ $zona['total_cdm'] ?? '' }}</td>
                    <td colspan="2" style="text-align: right; padding-right: 5px;">Total Zona:</td>
                    <td colspan="4" class="text-center" style="font-weight: bold;">{{ $zona['total_zona'] ?? '' }}</td>
                </tr>
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