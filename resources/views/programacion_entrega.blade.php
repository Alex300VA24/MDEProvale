<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programación de Entrega - Vaso de Leche</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: landscape;
            margin: 10mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.1;
            padding: 5px;
        }
        
        .header-section {
            text-align: right;
            margin-bottom: 10px;
        }
        
        .title-box {
            border: 2px solid #000;
            padding: 8px;
            text-align: center;
            margin-bottom: 10px;
            background-color: #f5f5f5;
        }
        
        .title-box h2 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .title-box h3 {
            font-size: 9pt;
            font-weight: bold;
        }
        
        .title-box .subtitle {
            font-size: 8pt;
            margin-top: 3px;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
        }
        
        .main-table th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 4px 2px;
            font-size: 6pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        
        .main-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            font-size: 6pt;
            vertical-align: middle;
            text-align: center;
        }
        
        .rotate-text {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            white-space: nowrap;
            font-size: 6pt;
            font-weight: bold;
        }
        
        .row-number {
            width: 25px;
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        .club-name {
            text-align: left;
            padding-left: 5px;
            max-width: 200px;
        }
        
        .presidenta-col {
            text-align: left;
            padding-left: 5px;
            max-width: 180px;
        }
        
        .direccion-col {
            text-align: left;
            padding-left: 5px;
            max-width: 150px;
        }
        
        .numeric-col {
            text-align: center;
            width: 30px;
        }
        
        .date-col {
            text-align: center;
            width: 60px;
            font-size: 6pt;
        }
        
        .signature-section {
            margin-top: 15px;
            display: table;
            width: 100%;
        }
        
        .signature-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 5px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 7pt;
        }
        
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .text-bold {
            font-weight: bold;
        }
        
        .bg-light {
            background-color: #f8f8f8;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header-section">
        <div style="font-size: 8pt;">SECTOR: {{ $sector ?? '' }}</div>
    </div>

    <!-- TITLE BOX -->
    <div class="title-box">
        <h2>PROGRAMACIÓN DE ENTREGA DE LOS PRODUCTOS DEL</h2>
        <h3>PROGRAMA VASO DE LECHE (PERIODO DEL 01 AL 28 DE FEBRERO DEL 2025)</h3>
        <div class="subtitle">LECHE EN POLVO, ATENCIÓN PRIORITARIA, Y RACIONES DE COMPLEMENTACIÓN (FORTALECER CEREAL)</div>
    </div>

    <!-- MAIN TABLE -->
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 20px;">N°</th>
                <th rowspan="2" style="width: 30px;">COD</th>
                <th rowspan="2" style="width: 180px;">CLUB DE MADRES</th>
                <th rowspan="2" style="width: 160px;">PRESIDENTA</th>
                <th rowspan="2" style="width: 130px;">DIRECCIÓN</th>
                <th colspan="4" style="background-color: #d0d0d0;">RACIONES</th>
                <th colspan="3" style="background-color: #d0d0d0;">LECHE</th>
                <th rowspan="2" style="width: 30px;"><div class="rotate-text">FECHA ENT.</div></th>
                <th rowspan="2" style="width: 30px;"><div class="rotate-text">RECIBE</div></th>
                <th rowspan="2" style="width: 30px;"><div class="rotate-text">DNI</div></th>
                <th rowspan="2" style="width: 30px;"><div class="rotate-text">FIRMA</div></th>
            </tr>
            <tr>
                <th style="width: 30px;">1RA<br/>PRIOR</th>
                <th style="width: 30px;">2DA<br/>PRIOR</th>
                <th style="width: 30px;">TOTAL</th>
                <th style="width: 30px;">BENEF</th>
                <th style="width: 30px;">BOLSAS</th>
                <th style="width: 30px;">KILOS</th>
                <th style="width: 30px;">RACION</th>
            </tr>
        </thead>
        <tbody>
            @php
                $clubes = $clubes ?? [];
                $total_1ra = 0;
                $total_2da = 0;
                $total_benef = 0;
                $total_bolsas = 0;
                $total_kilos = 0;
            @endphp
            
            @foreach($clubes as $index => $club)
                @php
                    $total_raciones = ($club['primera_prioridad'] ?? 0) + ($club['segunda_prioridad'] ?? 0);
                    $total_1ra += $club['primera_prioridad'] ?? 0;
                    $total_2da += $club['segunda_prioridad'] ?? 0;
                    $total_benef += $total_raciones;
                    $total_bolsas += $club['bolsas'] ?? 0;
                    $total_kilos += $club['kilos'] ?? 0;
                @endphp
                <tr>
                    <td class="row-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="numeric-col">{{ $club['codigo'] ?? '' }}</td>
                    <td class="club-name">{{ $club['nombre'] ?? '' }}</td>
                    <td class="presidenta-col">{{ $club['presidenta'] ?? '' }}</td>
                    <td class="direccion-col">{{ $club['direccion'] ?? '' }}</td>
                    <td class="numeric-col">{{ $club['primera_prioridad'] ?? '' }}</td>
                    <td class="numeric-col">{{ $club['segunda_prioridad'] ?? '' }}</td>
                    <td class="numeric-col">{{ $total_raciones }}</td>
                    <td class="numeric-col">{{ $total_raciones }}</td>
                    <td class="numeric-col">{{ $club['bolsas'] ?? '' }}</td>
                    <td class="numeric-col">{{ $club['kilos'] ?? '' }}</td>
                    <td class="numeric-col">{{ $club['racion'] ?? '' }}</td>
                    <td class="date-col">{{ $club['fecha_entrega'] ?? '' }}</td>
                    <td style="width: 80px; text-align: left; padding-left: 2px; font-size: 5pt;">{{ $club['recibe'] ?? '' }}</td>
                    <td class="numeric-col">{{ $club['dni'] ?? '' }}</td>
                    <td style="width: 40px;"></td>
                </tr>
            @endforeach
            
            <!-- TOTAL ROW -->
            <tr class="total-row">
                <td colspan="5" style="text-align: right; padding-right: 10px; font-weight: bold;">TOTAL:</td>
                <td class="numeric-col">{{ $total_1ra }}</td>
                <td class="numeric-col">{{ $total_2da }}</td>
                <td class="numeric-col">{{ $total_benef }}</td>
                <td class="numeric-col">{{ $total_benef }}</td>
                <td class="numeric-col">{{ $total_bolsas }}</td>
                <td class="numeric-col">{{ $total_kilos }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- SIGNATURES -->
    <div class="signature-section">
        <div class="signature-cell">
            <div class="signature-line">
                FIRMA Y SELLO
            </div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">
                JEFA DE PROGRAMA
            </div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">
                ENCARGADO DE ALMACÉN
            </div>
        </div>
    </div>

</body>
</html>