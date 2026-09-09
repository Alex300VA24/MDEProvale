<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programación de entrega con firma - Programa Vaso de Leche</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page {
            size: landscape;
            margin: 1.5mm 3mm;
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
            padding: 10px;
        }

        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tr { page-break-inside: avoid; }

        /* MAIN TABLE */
        .main-table {
            width: 98%;
            border-collapse: collapse;
            border: 2px solid #000;
            border-bottom: none;
            margin-left: 1%;
            margin-bottom: 5px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 8px;
        }
        .header-brand {
            width: 150px;
            text-align: left;
            vertical-align: middle;
            padding: 0;
        }
        .header-title {
            text-align: center;
            vertical-align: middle;
            line-height: 1.2;
            padding: 0;
        }
        .header-verification-container {
            width: 130px;
            vertical-align: top;
            padding: 0;
        }
        .header-verification-data {
            text-align: right;
            vertical-align: top;
            font-size: 6.5pt;
            line-height: 1.4;
        }
        .header-verification-data .sector-value {
            min-height: 11px;
            margin-top: 2px;
            border-bottom: 1px solid #000;
            overflow-wrap: break-word;
        }

        .main-table th {
            background-color: #d8d8d8;
            border: 1px solid #000;
            padding: 3px 1px;
            font-size: 5.7pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            line-height: 1.05;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 2px 1px;
            font-size: 5.6pt;
            vertical-align: middle;
            text-align: center;
        }
        .main-table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }

        .col-n { background-color: #f0f0f0; font-weight: bold; }
        .col-club { text-align: left; padding-left: 3px; }
        .col-pres { text-align: left; padding-left: 3px; }
        .col-dir { text-align: left; padding-left: 3px; }
        .col-recibe { text-align: left; padding-left: 3px; }
        .col-club,
        .col-pres,
        .col-dir,
        .col-recibe {
            overflow-wrap: break-word;
            word-wrap: break-word;
            line-height: 1.1;
        }
        .col-highlight-leche { background-color: #dff3e4; font-weight: bold; }
        .total-row { background-color: #e8e8e8; font-weight: bold; }
        .total-label { text-align: right; padding-right: 8px; vertical-align: middle; }

        .group-heading { background-color: #c8c8c8; }
        .numeric { white-space: nowrap; font-size: 5.3pt; }
        .code { font-size: 5.1pt; overflow-wrap: break-word; }
        .dni { font-size: 5pt; white-space: nowrap; }
        .date { font-size: 5pt; white-space: nowrap; }
        .manual-entry { background: #fff; }
        .signature { height: 12mm; background: #fff; }
    </style>
</head>
<body>
    <footer>
        <strong>PAG <span class="pagenum"></span></strong>
    </footer>

    <div class="page-container">
    @php
        $meses_es = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $nombreMes = strtoupper($meses_es[$currentMonth] ?? '');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
    @endphp
    <table class="main-table">
        <colgroup>
            <col style="width: 20px;">
            <col style="width: 25px;">
            <col style="width: 146px;">
            <col style="width: 136px;">
            <col style="width: 116px;">
            <col style="width: 71px;">
            <col style="width: 23px;">
            <col style="width: 23px;">
            <col style="width: 23px;">
            <col style="width: 23px;">
            <col style="width: 26px;">
            <col style="width: 24px;">
            <col style="width: 25px;">
            <col style="width: 48px;">
            <col style="width: 103px;">
            <col style="width: 55px;">
            <col style="width: 118px;">
        </colgroup>
        <thead>
            <tr>
                <td colspan="17" style="border: none; padding: 0 0 4px 0;">
                    <table class="header-table">
                        <tr>
                            <td class="header-brand">
                                <img src="{{ public_path('img/muni2.png') }}"
                                    style="width: 50px; height: auto; vertical-align: middle; margin-right: 5px;"
                                    alt="Logo">
                                <div style="display: inline-block; vertical-align: middle; text-align: left; width: 80px;">
                                    <div style="font-size: 6pt; font-weight: bold;">MUNICIPALIDAD DISTRITAL</div>
                                    <div style="font-size: 6pt; font-weight: bold;">DE LA ESPERANZA</div>
                                    <div style="font-size: 6pt;">O.F. Vaso de Leche</div>
                                </div>
                            </td>
                            <td class="header-title">
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
                            <td class="header-verification-container">
                                <div class="header-verification-data">
                                    <div style="font-weight: bold;">AÑO: {{ $currentYear }}</div>
                                    <div style="font-weight: bold; margin-top: 4px;">SECTOR:</div>
                                    <div class="sector-value">{{ $sector ?? 'TODOS' }}</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <th rowspan="2" style="width: 20px;">N°</th>
                <th rowspan="2" style="width: 25px;">COD</th>
                <th rowspan="2" style="width: 146px;">CLUB DE MADRES</th>
                <th rowspan="2" style="width: 136px;">PRESIDENTA</th>
                <th rowspan="2" style="width: 116px;">DIRECCIÓN</th>
                <th rowspan="2" style="width: 71px;">SECTOR</th>
                <th colspan="4" class="group-heading">RACIONES</th>
                <th colspan="3" class="group-heading col-highlight-leche">LECHE</th>
                <th rowspan="2" style="width: 48px;">FECHA<br>ENT.</th>
                <th rowspan="2" style="width: 103px;">RECIBE</th>
                <th rowspan="2" style="width: 55px;">DNI</th>
                <th rowspan="2" style="width: 118px;">FIRMA</th>
            </tr>
            <tr>
                <th style="width: 23px;">1RA<br>PRIOR.</th>
                <th style="width: 23px;">2DA<br>PRIOR.</th>
                <th style="width: 23px;">TOTAL</th>
                <th style="width: 23px;">BENEF.</th>
                <th class="col-highlight-leche" style="width: 26px;">BOLSAS</th>
                <th class="col-highlight-leche" style="width: 24px;">KILOS</th>
                <th class="col-highlight-leche" style="width: 25px;">RACIÓN</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalFirstPriority = 0;
                $totalSecondPriority = 0;
                $totalRations = 0;
                $totalBeneficiaries = 0;
                $totalBags = 0;
                $totalKilos = 0;
            @endphp
            @forelse($clubs as $index => $club)
                @php
                    $rations = ($club['primera_prioridad'] ?? 0) + ($club['segunda_prioridad'] ?? 0);
                    $beneficiaries = $club['total_beneficiarios'] ?? $rations;
                    $totalFirstPriority += $club['primera_prioridad'] ?? 0;
                    $totalSecondPriority += $club['segunda_prioridad'] ?? 0;
                    $totalRations += $rations;
                    $totalBeneficiaries += $beneficiaries;
                    $totalBags += $club['bolsas'] ?? 0;
                    $totalKilos += $club['kilos'] ?? 0;
                @endphp
                <tr>
                    <td class="col-n">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="code">{{ $club['codigo'] ?? '' }}</td>
                    <td class="col-club">{{ $club['nombre'] ?? '' }}</td>
                    <td class="col-pres">{{ $club['presidenta'] ?? '' }}</td>
                    <td class="col-dir">{{ $club['direccion'] ?? '' }}</td>
                    <td class="col-dir">{{ $club['sector'] ?? '' }}</td>
                    <td class="numeric">{{ $club['primera_prioridad'] ?? 0 }}</td>
                    <td class="numeric">{{ $club['segunda_prioridad'] ?? 0 }}</td>
                    <td class="numeric">{{ $rations }}</td>
                    <td class="numeric" style="font-weight:bold;">{{ $beneficiaries }}</td>
                    <td class="numeric col-highlight-leche">{{ $club['bolsas'] ?? 0 }}</td>
                    <td class="numeric col-highlight-leche">{{ $club['kilos'] ?? 0 }}</td>
                    <td class="numeric col-highlight-leche">{{ $club['racion'] ?? '' }}</td>
                    <td class="date manual-entry">{{ $club['fecha_entrega'] ?? '' }}</td>
                    <td class="col-recibe manual-entry"></td>
                    <td class="dni manual-entry">{{ $club['dni'] ?? '' }}</td>
                    <td class="signature"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" style="padding: 8px;">No hay comités para el período seleccionado.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="6" class="total-label">TOTAL:</td>
                <td>{{ $totalFirstPriority }}</td>
                <td>{{ $totalSecondPriority }}</td>
                <td>{{ $totalRations }}</td>
                <td>{{ $totalBeneficiaries }}</td>
                <td class="col-highlight-leche">{{ $totalBags }}</td>
                <td class="col-highlight-leche">{{ $totalKilos }}</td>
                <td class="col-highlight-leche"></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    </div>

</body>
</html>
