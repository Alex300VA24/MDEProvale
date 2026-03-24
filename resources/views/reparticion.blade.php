<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repartición - Vaso de Leche</title>
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
            width: 40px;
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
        
        .info-box {
            background-color: #e8f4e8;
            border: 1px solid #4caf50;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header-section">
        <div style="font-size: 8pt;">AÑO: {{ $year }}</div>
    </div>

    <!-- TITLE BOX -->
    <div class="title-box">
        <h2>REPARTICIÓN DE PRODUCTOS DEL PROGRAMA</h2>
        <h3>VASO DE LECHE (PERIODO DEL 01 AL {{ sprintf('%02d', $daysInMonth) }} DE {{ strtoupper($monthName) }} DEL {{ $year }})</h3>
        <div class="subtitle">LECHE EN POLVO Y COMPLEMENTACIÓN ALIMENTARIA (HOJUELAS DE AVENA)</div>
    </div>

    <!-- INFO BOX -->
    <div class="info-box">
        <strong>Parámetros de Cálculo:</strong> 
        Ración Leche: {{ $racionLecheMl }} ml x {{ $daysInMonth }} días = {{ $racionLecheMl * $daysInMonth }} ml/mes por beneficiario | 
        Ración Hojuelas: {{ $racionHojuelasGr }} g x {{ $daysInMonth }} días = {{ $racionHojuelasGr * $daysInMonth }} g/mes por beneficiario
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
                <th rowspan="2" style="width: 40px;">BENEF</th>
                <th colspan="2" style="background-color: #d0d0d0;">LECHE</th>
                <th colspan="2" style="background-color: #d0d0d0;">HOJUELAS</th>
                <th rowspan="2" style="width: 30px;"><div class="rotate-text">FECHA ENT.</div></th>
                <th rowspan="2" style="width: 30px;"><div class="rotate-text">RECIBE</div></th>
                <th rowspan="2" style="width: 30px;"><div class="rotate-text">DNI</div></th>
                <th rowspan="2" style="width: 30px;"><div class="rotate-text">FIRMA</div></th>
            </tr>
            <tr>
                <th style="width: 40px;">LITROS</th>
                <th style="width: 40px;">KILOS*</th>
                <th style="width: 40px;">KILOS</th>
                <th style="width: 40px;">BOLSAS</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_benef = 0;
                $total_leche_litros = 0;
                $total_leche_kilos = 0;
                $total_hojuelas_kg = 0;
                $total_hojuelas_bolsas = 0;
            @endphp
            
            @foreach($clubs as $index => $club)
                @php
                    $leche_kilos = round($club['leche_litros'], 2);
                    $hojuelas_bolsas = ceil($club['hojuelas_kg'] / 0.5);
                    
                    $total_benef += $club['beneficiarios'];
                    $total_leche_litros += $club['leche_litros'];
                    $total_leche_kilos += $leche_kilos;
                    $total_hojuelas_kg += $club['hojuelas_kg'];
                    $total_hojuelas_bolsas += $hojuelas_bolsas;
                @endphp
                <tr>
                    <td class="row-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="numeric-col">{{ $club['codigo'] }}</td>
                    <td class="club-name">{{ $club['nombre'] }}</td>
                    <td class="presidenta-col">{{ $club['presidenta'] }}</td>
                    <td class="direccion-col">{{ $club['direccion'] }}</td>
                    <td class="numeric-col text-bold">{{ $club['beneficiarios'] }}</td>
                    <td class="numeric-col text-bold">{{ round($club['leche_litros']) }}</td>
                    <td class="numeric-col">{{ number_format($leche_kilos, 2) }}</td>
                    <td class="numeric-col text-bold">{{ round($club['hojuelas_kg']) }}</td>
                    <td class="numeric-col">{{ $hojuelas_bolsas }}</td>
                    <td class="date-col"></td>
                    <td style="width: 80px; text-align: left; padding-left: 2px; font-size: 5pt;"></td>
                    <td class="numeric-col"></td>
                    <td style="width: 40px;"></td>
                </tr>
            @endforeach
            
            <!-- TOTAL ROW -->
            <tr class="total-row">
                <td colspan="5" style="text-align: right; padding-right: 10px; font-weight: bold;">TOTAL:</td>
                <td class="numeric-col">{{ $total_benef }}</td>
                <td class="numeric-col">{{ round($total_leche_litros) }}</td>
                <td class="numeric-col">{{ number_format($total_leche_kilos, 2) }}</td>
                <td class="numeric-col">{{ round($total_hojuelas_kg) }}</td>
                <td class="numeric-col">{{ $total_hojuelas_bolsas }}</td>
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
