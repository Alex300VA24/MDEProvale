<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tarjeta de Control Interno - {{ $productName }}</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #111; }
        .fill { color: #1a3a8a; font-style: italic; }
        table.kardex { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table.kardex th, table.kardex td { border: 1.5px solid #000; height: 22px; text-align: center; padding: 0 3px; }
        table.kardex th { font-size: 8.5pt; padding: 3px; }
        .neg { color: #c00; font-weight: bold; }
    </style>
</head>
<body>
@php
    $num = fn ($n) => $n === null ? '' : rtrim(rtrim(number_format($n, 2, '.', ','), '0'), '.');
    $saldo = 0; $totalIn = 0; $totalOut = 0;
    $computed = [];
    foreach ($rows as $row) {
        $blank = $row['in'] === null && $row['out'] === null;
        if (! $blank) {
            $saldo += ($row['in'] ?? 0) - ($row['out'] ?? 0);
            $totalIn += $row['in'] ?? 0;
            $totalOut += $row['out'] ?? 0;
        }
        // Con solo ingreso, el saldo queda vacío como en la tarjeta física.
        $computed[] = $row + ['saldo' => ($blank || $row['out'] === null) ? '' : ($saldo == 0 ? '—' : $num($saldo)), 'neg' => $saldo < 0];
    }
    $padding = max(0, $minRows - count($computed));
@endphp

<div style="position:absolute; top:0; right:0; width:60px; text-align:center;" class="fill">{{ $folio }}</div>
<div style="margin-bottom:8px;"><img src="{{ public_path('img/muni2.png') }}" style="width:54px; height:auto; vertical-align:middle; margin-right:8px;" alt="Logo de la Municipalidad Distrital de La Esperanza"><div style="display:inline-block; vertical-align:middle; text-align:left; line-height:1.25;"><div style="font-size:8pt; font-weight:bold;">MUNICIPALIDAD DISTRITAL</div><div style="font-size:8pt; font-weight:bold;">DE LA ESPERANZA</div><div style="font-size:7.5pt; font-weight:normal;">O.F. Vaso de Leche</div></div></div>
<div style="text-align:center; font-weight:bold; font-size:11pt; margin-bottom:12px;">PROGRAMA DEL VASO DE LECHE</div>
<div style="border:1.5px solid #000; width:60%; margin:0 auto 14px; text-align:center; font-weight:bold; padding:4px;">TARJETA DE CONTROL INTERNO</div>

<table style="width:100%; border-collapse:collapse; margin-bottom:14px; font-size:10pt; font-weight:bold;">
    <tr>
        <td style="border:1.5px solid #000; padding:6px 8px;">PRODUCTO: {{ $productName }}</td>
        <td style="width:30px;"></td>
        <td style="border:1.5px solid #000; padding:6px 8px; width:170px;">MES: <span class="fill">{{ $month }}</span></td>
    </tr>
</table>

<table class="kardex">
    <thead>
        <tr>
            <th rowspan="2" style="width:13%;">FECHA</th>
            <th rowspan="2" style="width:9%;">HORA</th>
            <th style="width:15%;">INGRESO</th>
            <th style="width:15%;">SALIDA</th>
            <th style="width:15%;">SALDO</th>
            <th rowspan="2">RESPONSABLE</th>
        </tr>
        <tr><th>CANTIDAD</th><th>CANTIDAD</th><th>CANTIDAD</th></tr>
    </thead>
    <tbody>
        @foreach($computed as $row)
            <tr class="fill">
                <td>{{ $row['date'] }}</td>
                <td>{{ $row['time'] }}</td>
                <td>{{ $num($row['in']) }}</td>
                <td>{{ $num($row['out']) }}</td>
                <td class="{{ $row['neg'] ? 'neg' : '' }}">{{ $row['saldo'] }}</td>
                <td style="text-align:left; padding-left:8px;">{{ $row['by'] }}</td>
            </tr>
        @endforeach
        @for($i = 0; $i < $padding; $i++)
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor
    </tbody>
</table>

<table style="width:100%; margin-top:10px; font-size:10pt;">
    <tr>
        <td style="text-align:right;">
            Total ingreso: <b>{{ $num($totalIn) ?: '0' }}</b> &nbsp;&nbsp;
            Total salida: <b>{{ $num($totalOut) ?: '0' }}</b> &nbsp;&nbsp;
            Saldo final: <b>{{ $num($saldo) ?: '0' }}</b>
        </td>
    </tr>
</table>
</body>
</html>
