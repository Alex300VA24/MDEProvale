<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programación de Entrega - Vaso de Leche</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 landscape; margin: 1.5mm 3mm; }
        body { margin: 0; padding: 3mm; font-family: DejaVu Sans, Arial, sans-serif; font-size: 7pt; line-height: 1.15; color: #000; }
        .page-container { width: 100%; padding: 10px; }
        footer { position: fixed; bottom: -4px; left: 0; right: 0; height: 20px; text-align: center; font-size: 8pt; }
        .pagenum:before { content: counter(page); }
                .header-table { width: 98%; margin-left: 1%; border-collapse: collapse; border-spacing: 0; margin-bottom: 8px; }
        .main-table { width: 98%; margin-left: 1%; border-collapse: collapse; border: 2px solid #000; table-layout: fixed; }
        .main-table thead { display: table-header-group; }
        .main-table tr { page-break-inside: avoid; }
        .main-table th,
        .main-table td { border: 1px solid #000; padding: 3px 2px; vertical-align: middle; }
        .main-table th { background: #d8d8d8; font-size: 5.8pt; text-align: center; }
        .main-table td { font-size: 5.8pt; text-align: center; overflow-wrap: break-word; }
        .main-table .text { text-align: left; }
        .main-table .number { text-align: right; font-variant-numeric: tabular-nums; }
        .total-row { background: #e8e8e8; font-weight: bold; }
        .col-n { width: 3%; }
        .col-code { width: 5%; }
        .col-club { width: 17%; }
        .col-president { width: 16%; }
        .col-address { width: 16%; }
        .col-sector { width: 10%; }
        .col-beneficiaries { width: 7%; }
        .col-milk { width: 6%; }
        .col-milk-rounded { width: 7%; }
        .col-oat { width: 6%; }
        .col-oat-rounded { width: 7%; }
    </style>
</head>
<body>
    <footer><strong>PÁG <span class="pagenum"></span></strong></footer>
    <div class="page-container">
    @php
        $months = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $selectedMonth = isset($mes) ? (int) $mes : (int) date('n');
        $selectedYear = isset($anio) ? (int) $anio : (int) date('Y');
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
        $monthName = $months[$selectedMonth] ?? '';
        $clubs = $clubes ?? [];
        $issued = $issuedAt ?? now();
        $formatAmount = static function ($value) {
            return rtrim(rtrim(number_format((float) ($value ?? 0), 2, '.', ','), '0'), '.');
        };
        $totals = [
            'beneficiaries' => 0,
            'milk' => 0,
            'milk_rounded' => 0,
            'oat' => 0,
            'oat_rounded' => 0,
        ];
    @endphp

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
                    PROGRAMACIÓN DE ENTREGA DE LOS PRODUCTOS DEL PROGRAMA VASO DE LECHE (PERIODO DEL 01 AL {{ sprintf('%02d', $lastDay) }} {{ $monthName }} {{ $selectedYear }})
                </div>
                <div style="font-size: 8pt; font-weight: bold; margin-top: 2px; text-transform: uppercase;">
                    LECHE EVAPORADA ENTERA Y HOJUELA DE QUINUA AVENA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES
                    @if(!empty($sector))
                        · SECTOR: {{ $sector }}
                    @endif
                </div>
            </td>
            <td style="width: 130px; text-align: right; vertical-align: top; font-size: 6.5pt; line-height: 1.4; padding: 0;">
                <div style="font-weight: bold;">FECHA: {{ $issued->format('d/m/Y') }}</div>
                <div style="font-weight: bold; margin-top: 4px;">HORA: {{ $issued->format('H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th class="col-n">N°</th>
                <th class="col-code">COD</th>
                <th class="col-club">CLUB DE MADRES</th>
                <th class="col-president">PRESIDENTA</th>
                <th class="col-address">DIRECCION</th>
                <th class="col-sector">SECTOR</th>
                <th class="col-beneficiaries">BENEF<br>(TOTAL)</th>
                <th class="col-milk">LECHE</th>
                <th class="col-milk-rounded">LECHE AL<br>REDONDEO</th>
                <th class="col-oat">HOJUELA<br>Kg</th>
                <th class="col-oat-rounded">HOJUELA AL<br>REDONDEO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clubs as $index => $club)
                @php
                    $totals['beneficiaries'] += (int) ($club['total_beneficiarios'] ?? 0);
                    $totals['milk'] += (float) ($club['leche'] ?? 0);
                    $totals['milk_rounded'] += (float) ($club['leche_redondeo'] ?? 0);
                    $totals['oat'] += (float) ($club['hojuela'] ?? 0);
                    $totals['oat_rounded'] += (float) ($club['hojuela_redondeo'] ?? 0);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $club['codigo'] ?? '' }}</td>
                    <td class="text">{{ $club['nombre'] ?? '' }}</td>
                    <td class="text">{{ $club['presidenta'] ?? '' }}</td>
                    <td class="text">{{ $club['direccion'] ?? '' }}</td>
                    <td class="text">{{ $club['sector'] ?? '' }}</td>
                    <td>{{ $club['total_beneficiarios'] ?? 0 }}</td>
                    <td class="number">{{ $formatAmount($club['leche'] ?? 0) }}</td>
                    <td class="number">{{ $formatAmount($club['leche_redondeo'] ?? 0) }}</td>
                    <td class="number">{{ $formatAmount($club['hojuela'] ?? 0) }}</td>
                    <td class="number">{{ $formatAmount($club['hojuela_redondeo'] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" style="text-align: right; padding-right: 6px;">TOTAL</td>
                <td>{{ $totals['beneficiaries'] }}</td>
                <td class="number">{{ $formatAmount($totals['milk']) }}</td>
                <td class="number">{{ $formatAmount($totals['milk_rounded']) }}</td>
                <td class="number">{{ $formatAmount($totals['oat']) }}</td>
                <td class="number">{{ $formatAmount($totals['oat_rounded']) }}</td>
            </tr>
        </tfoot>
    </table>
    </div>
</body>
</html>
