<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programación de Entrega - Vaso de Leche</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 landscape; margin: 8mm 10mm; }
        body { font-family: Arial, sans-serif; font-size: 7pt; line-height: 1.15; }

        /* HEADER */
        .header-table { width: 100%; border-collapse: collapse; border: 2px solid #000; margin-bottom: 5px; }
        .header-table td { vertical-align: middle; padding: 4px 6px; }
        .header-logo { width: 70px; text-align: center; border-right: 1px solid #000; }
        .header-logo img { width: 60px; height: 60px; }
        .header-title { text-align: center; }
        .header-title .inst { font-size: 8pt; font-weight: bold; }
        .header-title .prog { font-size: 7pt; margin: 1px 0; }
        .header-title .doc-title { font-size: 10pt; font-weight: bold; margin: 4px 0 2px; }
        .header-title .periodo { font-size: 8pt; font-weight: bold; }
        .header-title .sub { font-size: 7pt; margin-top: 2px; }
        .header-right { width: 120px; text-align: center; border-left: 1px solid #000; font-size: 7pt; }

        /* MAIN TABLE */
        .main-table { width: 100%; border-collapse: collapse; border: 2px solid #000; }
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
        .col-club { text-align: left; padding-left: 3px; width: 160px; }
        .col-pres { text-align: left; padding-left: 3px; width: 140px; }
        .col-dir { text-align: left; padding-left: 3px; width: 110px; }
        .col-num { width: 28px; }
        .col-date { width: 50px; font-size: 5.5pt; }
        .total-row { background-color: #e8e8e8; font-weight: bold; }

        /* SIGNATURES */
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .sig-table td { width: 33.33%; text-align: center; padding: 4px; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #000; margin-top: 35px; padding-top: 4px; font-size: 7pt; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td class="header-logo">
                <img src="{{ public_path('img/muni2.png') }}" alt="Logo">
            </td>
            <td class="header-title">
                <div class="inst">MUNICIPALIDAD DISTRITAL DE LA ESPERANZA</div>
                <div class="prog">Gerencia de Desarrollo Social - Programa Vaso de Leche</div>
                <div class="doc-title">PROGRAMACIÓN DE ENTREGA DE LOS PRODUCTOS DEL PROGRAMA VASO DE LECHE</div>
                @php
                    $meses_es = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                    $mesActual = isset($mes) ? (int)$mes : (int)date('n');
                    $anioActual = $anio ?? date('Y');
                    $diasMes = cal_days_in_month(CAL_GREGORIAN, $mesActual, $anioActual);
                    $nombreMes = strtoupper($meses_es[$mesActual] ?? date('F'));
                @endphp
                <div class="periodo">PERÍODO DEL 01 AL {{ sprintf('%02d', $diasMes) }} DE {{ $nombreMes }} DEL {{ $anioActual }}</div>
                <div class="sub">LECHE EN POLVO, ATENCIÓN PRIORITARIA Y RACIONES DE COMPLEMENTACIÓN (FORTALECER CEREAL)</div>
            </td>
            <td class="header-right">
                <div style="font-weight:bold; margin-bottom:3px;">SECTOR:</div>
                <div style="border-bottom:1px solid #000; min-height:14px; padding:1px 4px;">{{ $sector ?? '' }}</div>
                <div style="font-weight:bold; margin-top:5px;">AÑO: {{ $anioActual }}</div>
            </td>
        </tr>
    </table>

    {{-- TABLA PRINCIPAL --}}
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" class="col-n">N°</th>
                <th rowspan="2" class="col-cod">COD</th>
                <th rowspan="2" class="col-club">CLUB DE MADRES</th>
                <th rowspan="2" class="col-pres">PRESIDENTA</th>
                <th rowspan="2" class="col-dir">DIRECCIÓN</th>
                <th rowspan="2" class="col-dir">SECTOR</th>
                <th colspan="4" style="background-color:#c8c8c8;">RACIONES</th>
                <th colspan="3" style="background-color:#c8c8c8;">LECHE</th>
                <th rowspan="2" class="col-num"><div class="rotate-text">FECHA ENT.</div></th>
                <th rowspan="2" style="width:70px;"><div class="rotate-text">RECIBE</div></th>
                <th rowspan="2" class="col-num"><div class="rotate-text">DNI</div></th>
                <th rowspan="2" class="col-num"><div class="rotate-text">FIRMA</div></th>
            </tr>
            <tr>
                <th class="col-num">1RA<br/>PRIOR</th>
                <th class="col-num">2DA<br/>PRIOR</th>
                <th class="col-num">TOTAL</th>
                <th class="col-num">BENEF</th>
                <th class="col-num">BOLSAS</th>
                <th class="col-num">KILOS</th>
                <th class="col-num">RACIÓN</th>
            </tr>
        </thead>
        <tbody>
            @php
                $clubes = $clubes ?? [];
                $t1ra = 0; $t2da = 0; $tbenef = 0; $tbolsas = 0; $tkilos = 0;
            @endphp
            @foreach($clubes as $i => $club)
                @php
                    $total_rac = ($club['primera_prioridad'] ?? 0) + ($club['segunda_prioridad'] ?? 0);
                    $t1ra    += $club['primera_prioridad'] ?? 0;
                    $t2da    += $club['segunda_prioridad'] ?? 0;
                    $tbenef  += $total_rac;
                    $tbolsas += $club['bolsas'] ?? 0;
                    $tkilos  += $club['kilos'] ?? 0;
                @endphp
                <tr>
                    <td class="col-n">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="col-cod">{{ $club['codigo'] ?? '' }}</td>
                    <td class="col-club">{{ $club['nombre'] ?? '' }}</td>
                    <td class="col-pres">{{ $club['presidenta'] ?? '' }}</td>
                    <td class="col-dir">{{ $club['direccion'] ?? '' }}</td>
                    <td class="col-num">{{ $club['primera_prioridad'] ?? '' }}</td>
                    <td class="col-num">{{ $club['segunda_prioridad'] ?? '' }}</td>
                    <td class="col-num">{{ $total_rac }}</td>
                    <td class="col-num">{{ $total_rac }}</td>
                    <td class="col-num">{{ $club['bolsas'] ?? '' }}</td>
                    <td class="col-num">{{ $club['kilos'] ?? '' }}</td>
                    <td class="col-num">{{ $club['racion'] ?? '' }}</td>
                    <td class="col-date">{{ $club['fecha_entrega'] ?? '' }}</td>
                    <td style="width:70px; text-align:left; padding-left:2px; font-size:5.5pt;">{{ $club['recibe'] ?? '' }}</td>
                    <td class="col-num" style="font-size:5.5pt;">{{ $club['dni'] ?? '' }}</td>
                    <td class="col-num"></td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" style="text-align:right; padding-right:8px;">TOTAL:</td>
                <td class="col-num">{{ $t1ra }}</td>
                <td class="col-num">{{ $t2da }}</td>
                <td class="col-num">{{ $tbenef }}</td>
                <td class="col-num">{{ $tbenef }}</td>
                <td class="col-num">{{ $tbolsas }}</td>
                <td class="col-num">{{ $tkilos }}</td>
                <td></td><td></td><td></td><td></td><td></td>
            </tr>
        </tbody>
    </table>
   
</body>
</html>
