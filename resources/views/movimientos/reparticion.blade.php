<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repartición - Programa Vaso de Leche</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page {
            size: landscape;
            margin: 1.5mm 3mm 1.5mm 3mm;
        }
        body {
            margin: 0;
            padding: 3mm;
            font-family: Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.15;
        }

        footer {
            position: fixed;
            bottom: -4px;
            left: 0;
            right: 0;
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
            height: 100%;
            padding: 10px;
        }

        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tr { page-break-inside: avoid; }

        /* MAIN TABLE */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            border-right: none;
            border-bottom: none;
            margin-bottom: 5px;
        }
        .header-table { width: 100%; border-collapse: collapse; border-spacing: 0; margin-bottom: 8px; }
        .header-spacer {
            width: 1px;
            border-top: none !important;
            border-bottom: none !important;
            border-right: none !important;
            border-left: 1px solid #000 !important;
            background-color: #fff !important;
        }
        .main-table th {
            background-color: #d8d8d8;
            border: 1px solid #000;
            padding: 3px 2px;
            font-size: 6pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 2px 2px;
            font-size: 6pt;
            vertical-align: middle;
            text-align: center;
        }
        .main-table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }
        .rotate-text {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            white-space: nowrap;
            font-size: 5.5pt;
            font-weight: bold;
        }
        .col-n { width: 18px; background-color: #f0f0f0; font-weight: bold; }
        .col-cod { width: 28px; }
        .col-club { text-align: left; padding-left: 3px; width: 170px; }
        .col-pres { text-align: left; padding-left: 3px; width: 150px; }
        .col-dir { text-align: left; padding-left: 3px; width: 110px; }
        .col-num { width: 38px; }
        .col-sm { width: 30px; }
        .col-highlight-leche { background-color: #dff3e4; font-weight: bold; }
        .col-highlight-hojuelas { background-color: #fff0c2; font-weight: bold; }
        .total-row { background-color: #e8e8e8; font-weight: bold; }
        .total-label { text-align: right; padding-right: 8px; vertical-align: middle; }

        /* SIGNATURES */
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .sig-table td { width: 33.33%; text-align: center; padding: 4px; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #000; margin-top: 35px; padding-top: 4px; font-size: 7pt; }
    </style>
</head>
<body>
    <footer>
        <strong>PAG <span class="pagenum"></span></strong>
    </footer>
    <div class="page-container">
    @php
        $meses_es = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $nombreMes = strtoupper($meses_es[$currentMonth] ?? $monthName);
    @endphp
    <table class="main-table">
        <thead>
            <tr>
                <td colspan="13" style="border: none; padding: 0 0 4px 0;">
                    <table class="header-table">
                        <tr>
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
                            <td style="text-align: center; vertical-align: middle; line-height: 1.2; padding: 0; width: 60%;">
                                <div style="font-size: 11pt; font-weight: bold; margin: 0;">
                                    PROGRAMACIÓN DE ENTREGA DE LOS PRODUCTOS DEL PROGRAMA VASO DE LECHE
                                </div>
                                <div style="font-size: 8.5pt; font-weight: bold; margin: 2px 0 0;">
                                    (PERÍODO DEL 01 AL {{ sprintf('%02d', $daysInMonth) }} DE {{ $nombreMes }} DEL {{ $currentYear }})
                                </div>
                                <div style="font-size: 7pt; margin-top: 2px;">
                                    <strong>LECHE EVAPORADA ENTERA Y HOJUELAS DE QUINUA AVENA CON AZÚCAR FORTIFICADO CON VITAMINAS Y MINERALES</strong>
                                </div>
                            </td>
                            <td style="width: 120px; text-align: right; vertical-align: top; font-size: 7pt; padding: 0;">
                                <div style="font-weight: bold; margin-top: 4px;">AÑO: {{ $currentYear }}</div>
                                <div>FECHA: {{ date('d/m/Y') }}</div>
                                <div>HORA: {{ date('H:i:s') }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <th style="width: 30px;">N°</th>
                <th style="width: 30px;">COD</th>
                <th style="width: 180px;">CLUB DE MADRES</th>
                <th style="width: 160px;">PRESIDENTA</th>
                <th style="width: 130px;">DIRECCIÓN</th>
                <th style="width: 130px;">SECTOR</th>
                <th style="width: 40px;">BENEF</th>
                <th class="col-highlight-leche">LECHE</th>
                <th style="width: 40px;">CAJAS</th>
                <th style="width: 40px;">TARROS</th>
                <th class="col-highlight-hojuelas">HOJUELAS</th>
                <th style="width: 40px;">SACOS</th>
                <th style="width: 40px;">KILOS</th>
                <th class="header-spacer">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_benef = 0;
                $total_leche_tarros = 0;
                $total_leche_cajas = 0;
                $total_leche_tarros_sueltos = 0;
                $total_hojuelas_kg = 0;
                $total_hojuelas_sacos = 0;
                $total_hojuelas_kilos = 0;
            @endphp
            @foreach($clubs as $index => $club)
                @php
                    $total_benef += $club['beneficiarios'];
                    $total_leche_tarros += $club['leche_litros'];
                    $total_leche_cajas += $club['leche_cajas'] ?? 0;
                    $total_leche_tarros_sueltos += $club['leche_tarros'] ?? 0;
                    $total_hojuelas_kg += $club['hojuelas_kg'];
                    $total_hojuelas_sacos += $club['hojuelas_sacos'] ?? 0;
                    $total_hojuelas_kilos += $club['hojuelas_kilos'] ?? 0;
                @endphp
                <tr>
                    <td class="col-n">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="col-cod">{{ $club['codigo'] }}</td>
                    <td class="col-club">{{ $club['nombre'] }}</td>
                    <td class="col-pres">{{ $club['presidenta'] }}</td>
                    <td class="col-dir">{{ $club['direccion'] }}</td>
                    <td class="col-dir">{{ $club['sector'] ?? '' }}</td>
                    <td class="col-num" style="font-weight:bold;">{{ $club['beneficiarios'] }}</td>
                    <td class="col-num col-highlight-leche">{{ round($club['leche_litros']) }}</td>
                    <td class="col-num">{{ $club['leche_cajas'] ?? 0 }}</td>
                    <td class="col-num">{{ $club['leche_tarros'] ?? 0 }}</td>
                    <td class="col-num col-highlight-hojuelas">{{ round($club['hojuelas_kg']) }}</td>
                    <td class="col-num">{{ $club['hojuelas_sacos'] ?? 0 }}</td>
                    <td class="col-num">{{ $club['hojuelas_kilos'] ?? 0 }}</td>
                    <td class="header-spacer"></td>
                </tr>
            @endforeach
            @php
                $total_leche_cajas += intdiv((int) $total_leche_tarros_sueltos, 48);
                $total_leche_tarros_sueltos = (int) $total_leche_tarros_sueltos % 48;
                $total_hojuelas_sacos += intdiv((int) $total_hojuelas_kilos, 30);
                $total_hojuelas_kilos = (int) $total_hojuelas_kilos % 30;
            @endphp
            <tr class="total-row">
                <td colspan="6" rowspan="2" class="total-label">TOTAL:</td>
                <td class="col-num" rowspan="2">{{ $total_benef }}</td>
                <td class="col-num col-highlight-leche" rowspan="2">{{ round($total_leche_tarros) }}</td>
                <td class="col-num" rowspan="2">{{ $total_leche_cajas }}</td>
                <td class="col-num" rowspan="2">{{ $total_leche_tarros_sueltos }}</td>
                <td class="col-num col-highlight-hojuelas" rowspan="2">{{ round($total_hojuelas_kg) }}</td>
                <td class="col-num" rowspan="2">{{ $total_hojuelas_sacos }}</td>
                <td class="col-num" rowspan="2">{{ $total_hojuelas_kilos }}</td>
                <td class="header-spacer" rowspan="2"></td>
            </tr>
            <tr></tr>
            
        </tbody>
    </table>
    </div>
</body>
</html>
