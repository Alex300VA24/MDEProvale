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
            padding: 4px 2px;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            line-height: 1.15;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 7pt;
            vertical-align: middle;
            text-align: center;
            height: 9mm;
        }
        .main-table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }

        .col-n { background-color: #f0f0f0; font-weight: bold; }
        .col-club { text-align: left; padding-left: 3px; }
        .col-pres { text-align: left; padding-left: 3px; }
        .col-dir { text-align: left; padding-left: 3px; }
        .col-club,
        .col-pres,
        .col-dir {
            overflow-wrap: break-word;
            word-wrap: break-word;
            line-height: 1.15;
        }
        .col-recibe { text-align: left; padding-left: 3px; overflow-wrap: break-word; }
        .manual-entry { background: #fff; }
        .total-row { background-color: #e8e8e8; font-weight: bold; }
        .total-label { text-align: right; padding-right: 8px; vertical-align: middle; }

        .numeric { white-space: nowrap; font-size: 7pt; }
        .code { font-size: 6.8pt; overflow-wrap: break-word; }
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
            <col style="width: 18px;">
            <col style="width: 22px;">
            <col style="width: 90px;">
            <col style="width: 70px;">
            <col style="width: 65px;">
            <col style="width: 71px;">
            <col style="width: 23px;">
            <col style="width: 23px;">
            <col style="width: 23px;">
            <col style="width: 23px;">
            <col style="width: 20px;">
            <col style="width: 20px;">
            <col style="width: 20px;">
            <col style="width: 38px;">
            <col style="width: 65px;">
            <col style="width: 35px;">
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
                                <div style="font-size: 11pt; font-weight: bold; margin: 0; text-transform: uppercase;">
                                    PROGRAMACIÓN DE ENTREGA DE LOS PRODUCTOS DEL PROGRAMA VASO DE LECHE (PERIODO DEL 01 AL {{ sprintf('%02d', $daysInMonth) }} {{ $nombreMes }} {{ $currentYear }})
                                </div>
                                <div style="font-size: 8pt; font-weight: bold; margin-top: 2px; text-transform: uppercase;">
                                    LECHE EVAPORADA ENTERA Y HOJUELA DE QUINUA AVENA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES
                                </div>
                            </td>
                            <td class="header-verification-container">
                                <div class="header-verification-data">
                                    <div style="font-weight: bold;">FECHA: {{ now()->format('d/m/Y') }}</div>
                                    <div style="font-weight: bold; margin-top: 4px;">HORA: {{ now()->format('H:i') }}</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <th style="width: 18px;">N.°</th>
                <th style="width: 22px;">CÓD.</th>
                <th style="width: 90px;">CLUB DE MADRES</th>
                <th style="width: 70px;">PRESIDENTA</th>
                <th style="width: 65px;">DIRECCIÓN</th>
                <th style="width: 71px;">SECTOR</th>
                <th style="width: 23px;">1RA<br>PRIOR.</th>
                <th style="width: 23px;">2DA<br>PRIOR.</th>
                <th style="width: 23px;">TOTAL<br>RAC.</th>
                <th style="width: 23px;">BENEF.</th>
                <th style="width: 20px;">BOLSAS</th>
                <th style="width: 20px;">KILOS</th>
                <th style="width: 20px;">RACIÓN</th>
                <th style="width: 38px;">FECHA<br>ENTREGA</th>
                <th style="width: 65px;">RECIBE</th>
                <th style="width: 35px;">DNI</th>
                <th style="width: 118px;">FIRMA</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPrimera = 0;
                $totalSegunda = 0;
                $totalRaciones = 0;
                $totalBeneficiaries = 0;
                $totalBolsas = 0;
                $totalKilos = 0;
            @endphp
            @forelse($clubs as $index => $club)
                @php
                    $rations = ($club['primera_prioridad'] ?? 0) + ($club['segunda_prioridad'] ?? 0);
                    $beneficiaries = $club['total_beneficiarios'] ?? $rations;
                    $totalPrimera += $club['primera_prioridad'] ?? 0;
                    $totalSegunda += $club['segunda_prioridad'] ?? 0;
                    $totalRaciones += $rations;
                    $totalBeneficiaries += $beneficiaries;
                    $totalBolsas += $club['bolsas'] ?? 0;
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
                    <td class="numeric">{{ $club['bolsas'] ?? 0 }}</td>
                    <td class="numeric">{{ $club['kilos'] ?? 0 }}</td>
                    <td class="numeric">{{ $club['racion'] ?? '' }}</td>
                    <td class="numeric">{{ $club['fecha_entrega'] ?? '' }}</td>
                    <td class="col-recibe manual-entry">{{ $club['recibe'] ?? '' }}</td>
                    <td class="numeric manual-entry">{{ $club['dni'] ?? '' }}</td>
                    <td class="signature"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" style="padding: 8px;">No hay comités para el período seleccionado.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="6" class="total-label">TOTAL:</td>
                <td>{{ $totalPrimera }}</td>
                <td>{{ $totalSegunda }}</td>
                <td>{{ $totalRaciones }}</td>
                <td>{{ $totalBeneficiaries }}</td>
                <td>{{ $totalBolsas }}</td>
                <td>{{ $totalKilos }}</td>
                <td colspan="5"></td>
            </tr>
        </tbody>
    </table>
    </div>

</body>
</html>
