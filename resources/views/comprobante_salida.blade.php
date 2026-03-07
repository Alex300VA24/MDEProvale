<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido-Comprobante de Salida - Cereales</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            padding: 15px;
        }
        
        .header {
            display: table;
            width: 100%;
            border: 2px solid #000;
            margin-bottom: 10px;
        }
        
        .header-row {
            display: table-row;
        }
        
        .header-cell {
            display: table-cell;
            padding: 8px;
            vertical-align: middle;
            border-right: 1px solid #000;
        }
        
        .header-cell:last-child {
            border-right: none;
        }
        
        .logo-cell {
            width: 90px;
            text-align: center;
        }
        
        .title-cell {
            text-align: center;
            padding: 5px;
        }
        
        .title-cell h3 {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .title-cell h2 {
            font-size: 12pt;
            font-weight: bold;
            margin: 3px 0;
        }
        
        .title-cell .subtitle {
            font-size: 8pt;
            margin-top: 2px;
        }
        
        .info-boxes {
            display: table-cell;
            width: 250px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .info-table .label {
            background-color: #e8e8e8;
            font-size: 7pt;
        }
        
        .info-table .value {
            font-size: 10pt;
            height: 20px;
        }
        
        .info-row {
            border: 1px solid #000;
            padding: 5px;
            margin-bottom: 2px;
            font-size: 8pt;
        }
        
        .info-row-flex {
            display: flex;
            justify-content: space-between;
            border: 1px solid #000;
            margin-bottom: 2px;
        }
        
        .info-section {
            padding: 5px;
            flex: 1;
        }
        
        .info-section:first-child {
            border-right: 1px solid #000;
        }
        
        .label-bold {
            font-weight: bold;
            display: inline-block;
            margin-right: 5px;
        }
        
        .underline-field {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
            padding: 0 5px;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 10px;
        }
        
        .main-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 5px;
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
        }
        
        .main-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 8pt;
            text-align: center;
        }
        
        .main-table .section-header {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 10pt;
            text-align: center;
            padding: 6px;
        }
        
        .main-table .row-number {
            width: 30px;
            background-color: #f8f8f8;
            font-weight: bold;
        }
        
        .main-table .cantidad-col {
            width: 60px;
        }
        
        .main-table .descripcion-col {
            text-align: left;
            padding-left: 8px;
        }
        
        .main-table .unidad-col {
            width: 70px;
        }
        
        .main-table .unitario-col {
            width: 70px;
        }
        
        .main-table .total-col {
            width: 70px;
        }
        
        .total-row {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        
        .total-value {
            text-align: right;
            padding-right: 10px;
            font-size: 9pt;
        }
        
        .footer-note {
            border: 1px solid #000;
            padding: 8px;
            margin-bottom: 10px;
            font-size: 8pt;
            text-align: center;
        }
        
        .signatures {
            display: table;
            width: 100%;
            margin-top: 20px;
        }
        
        .signature-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 5px;
        }
        
        .signature-box {
            border: 1px solid #000;
            padding: 5px;
            min-height: 80px;
        }
        
        .signature-title {
            font-weight: bold;
            font-size: 8pt;
            margin-bottom: 5px;
            background-color: #f0f0f0;
            padding: 3px;
        }
        
        .signature-content {
            padding-top: 30px;
            font-size: 7pt;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 20px;
            padding-top: 3px;
            font-size: 7pt;
        }
        
        .date-location {
            text-align: right;
            font-size: 8pt;
            margin-bottom: 10px;
            padding-right: 10px;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <table style="width: 100%; border: 2px solid #000; margin-bottom: 5px;">
        <tr>
            <td style="width: 90px; text-align: center; padding: 5px; border-right: 1px solid #000;">
                <div style="width: 75px; height: 75px; border: 1px solid #000; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                    <!-- Logo aquí -->
                    <div style="font-size: 8pt;">LOGO</div>
                </div>
            </td>
            
            <td style="text-align: center; padding: 10px; border-right: 1px solid #000;">
                <div style="font-weight: bold; font-size: 9pt;">Municipalidad</div>
                <div style="font-size: 9pt;">Distrital de la Esperanza</div>
                <div style="font-size: 8pt;">Gerencia de Desarrollo Social</div>
                <div style="font-weight: bold; font-size: 13pt; margin: 5px 0; letter-spacing: 1px;">PEDIDO-COMPROBANTE DE SALIDA</div>
                <div style="font-weight: bold; font-size: 10pt;">CEREALES</div>
                <div style="font-size: 8pt; margin-top: 3px;">MES/AÑO: <span style="border-bottom: 1px solid #000; display: inline-block; width: 100px; margin-left: 5px;"></span></div>
            </td>
            
            <td style="width: 250px; padding: 5px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; background-color: #e8e8e8;">ZONA</td>
                        <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; background-color: #e8e8e8;">COMITÉ</td>
                        <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; background-color: #e8e8e8;">Nº MES</td>
                        <td style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; background-color: #e8e8e8;">RACIÓN SUPLEMENTO</td>
                        <td rowspan="2" style="border: 1px solid #000; padding: 2px; text-align: center; font-size: 7pt; font-weight: bold; background-color: #e8e8e8; vertical-align: middle;">NUMERO<br/>DE<br/>ORDEN</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10pt; font-weight: bold; height: 22px;">{{ $zona ?? '01' }}</td>
                        <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10pt; font-weight: bold;">{{ $comite ?? '005' }}</td>
                        <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10pt; font-weight: bold;">{{ $num_mes ?? '51' }}</td>
                        <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 9pt; font-weight: bold;">{{ $racion ?? '36.00 gr.' }}</td>
                        <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10pt; font-weight: bold;">{{ $numero_orden ?? '000001' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- INFORMACIÓN DEL SOLICITANTE -->
    <div style="border: 1px solid #000; padding: 5px; margin-bottom: 2px; font-size: 8pt;">
        <span style="font-weight: bold;">Se les entrega a la Sra. (Sr.):</span>
        <span style="border-bottom: 1px solid #000; display: inline-block; width: 350px; margin-left: 5px;">{{ $solicitante_nombre ?? 'MARIA NELLY RODRIGUEZ LOYOLA' }}</span>
    </div>

    <div style="display: flex; border: 1px solid #000; margin-bottom: 5px;">
        <div style="flex: 1; padding: 5px; border-right: 1px solid #000; font-size: 8pt;">
            <span style="font-weight: bold;">Con domicilio en (Urb./Modesto):</span>
            <span style="border-bottom: 1px solid #000; display: inline-block; width: 180px; margin-left: 5px;">{{ $domicilio ?? 'SANTA RITA DE CASSIA' }}</span>
        </div>
        <div style="flex: 1; padding: 5px; font-size: 8pt; text-align: right;">
            <span style="font-style: italic;">los siguientes artículos</span>
        </div>
    </div>

    <!-- FECHA Y LUGAR -->
    <div class="date-location">
        La Esperanza, {{ $fecha ?? '2 de Marzo del 2026' }}
    </div>

    <!-- TABLA PRINCIPAL -->
    <table class="main-table">
        <!-- HEADER -->
        <thead>
            <tr>
                <th colspan="2" class="section-header">SOLICITADO</th>
                <th colspan="3" class="section-header">DESPACHADO</th>
                <th colspan="2" class="section-header">VALORES</th>
            </tr>
            <tr>
                <th style="width: 30px;"></th>
                <th style="width: 60px;">CANTIDAD</th>
                <th style="width: 60px;">CANTIDAD<br/>DESPACHADO</th>
                <th style="width: 80px;">UNIDAD<br/>DE MEDIDA</th>
                <th style="width: 80px;">UNITARIO<br/>S/.</th>
                <th style="width: 80px;">TOTAL<br/>S/.</th>
            </tr>
            <tr>
                <th></th>
                <th colspan="6" style="text-align: left; padding-left: 8px; background-color: #fff;">ARTICULOS</th>
            </tr>
            <tr>
                <th></th>
                <th colspan="1"></th>
                <th colspan="5" style="text-align: center; font-weight: normal; font-size: 8pt; background-color: #fff;">DESCRIPCIÓN</th>
            </tr>
        </thead>
        
        <!-- BODY -->
        <tbody>
            @php
                $articulos = $articulos ?? [
                    ['numero' => '01', 'cantidad_solicitado' => '0.00', 'descripcion' => 'MEZCLAS CEREAL FORTIFICADO ESPECIAL INTEGRADO NIÑO(A) 36 MESES Y NIÑEZ GEST.', 'cantidad_despachado' => '0.00', 'unidad' => 'KILOS', 'unitario' => '6.55', 'total' => '0.00'],
                ];
                
                // Rellenar hasta 15 filas
                $rows_count = 15;
            @endphp
            
            @for ($i = 0; $i < $rows_count; $i++)
                @php
                    $articulo = $articulos[$i] ?? null;
                @endphp
                <tr>
                    <td class="row-number">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="cantidad-col">{{ $articulo['cantidad_solicitado'] ?? '' }}</td>
                    <td class="cantidad-col">{{ $articulo['cantidad_despachado'] ?? '' }}</td>
                    <td class="unidad-col">{{ $articulo['unidad'] ?? '' }}</td>
                    <td class="descripcion-col" colspan="1">{{ $articulo['descripcion'] ?? '' }}</td>
                    <td class="unitario-col">{{ $articulo['unitario'] ?? '' }}</td>
                    <td class="total-col">{{ $articulo['total'] ?? '' }}</td>
                </tr>
            @endfor
            
            <!-- TOTAL ROW -->
            <tr class="total-row">
                <td colspan="6" class="total-value">TOTAL:</td>
                <td style="text-align: center; font-size: 10pt;">{{ $total_general ?? '*****0.00' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- NOTA PIE -->
    <div class="footer-note">
        <div style="font-weight: bold;">FORMULARIO UTILIZADO PARA EL ALMACEN</div>
        <div>N°__________</div>
        <div style="font-style: italic; font-size: 7pt; margin-top: 3px;">(en letras)</div>
    </div>

    <!-- FIRMAS -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 33.33%; border: 1px solid #000; padding: 5px; vertical-align: top;">
                <div style="font-weight: bold; font-size: 8pt; text-align: center; background-color: #f0f0f0; padding: 3px; margin-bottom: 5px;">
                    Municipalidad Distrital de la Esperanza
                </div>
                <div style="height: 50px;"></div>
                <div style="border-top: 1px solid #000; padding-top: 3px; font-size: 7pt; text-align: center; margin-top: 5px;">
                    Sra. Municipalidad Pro Vaso de Cereales
                </div>
                <div style="text-align: center; font-size: 7pt;">
                    {{ $encargado_almacen ?? 'ENCARGADO DE PROVIDURIS' }}
                </div>
                <div style="text-align: center; font-size: 7pt; margin-top: 2px;">
                    D.N.I. {{ $dni_encargado ?? '18357683' }}
                </div>
            </td>
            
            <td style="width: 33.33%; border: 1px solid #000; border-left: none; padding: 5px; vertical-align: top;">
                <div style="font-weight: bold; font-size: 8pt; text-align: center; background-color: #f0f0f0; padding: 3px; margin-bottom: 5px;">
                    Municipalidad Distrital de la Esperanza
                </div>
                <div style="height: 50px;"></div>
                <div style="border-top: 1px solid #000; padding-top: 3px; font-size: 7pt; text-align: center; margin-top: 5px;">
                    Lic. Isabel María Puerta Aguilar
                </div>
                <div style="text-align: center; font-size: 7pt;">
                    {{ $control ?? 'JEFA DE ALMACÉN PROVALE' }}
                </div>
                <div style="text-align: center; font-size: 7pt; margin-top: 2px;">
                    D.N.I. {{ $dni_control ?? '40353394' }}
                </div>
            </td>
            
            <td style="width: 33.33%; border: 1px solid #000; border-left: none; padding: 5px; vertical-align: top;">
                <div style="height: 55px;"></div>
                <div style="border-top: 1px solid #000; padding-top: 3px; font-size: 7pt; text-align: center; margin-top: 5px;">
                    <span style="font-weight: bold;">RECIBI CONFORME</span>
                </div>
                <div style="text-align: center; font-size: 7pt; margin-top: 8px;">
                    D.N.I. <span style="border-bottom: 1px solid #000; display: inline-block; width: 100px;"></span>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>