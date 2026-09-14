<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control y fiscalización PVL</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 7mm 12mm; }
        body { font-family: Arial, sans-serif; color:#17242b; font-size:7.5px; margin:0; }
        h1 { text-align:center; color:#143f58; font-size:14px; margin:0 0 3px; }
        .meta { text-align:center; margin-bottom:9px; font-size:9px; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th { background:#174a68; color:#fff; border:1px solid #10364d; padding:5px 2px; font-size:7px; }
        td { border:1px solid #9aabb2; padding:4px 3px; overflow-wrap:break-word; }
        tbody tr:nth-child(even) { background:#f2f7f6; }
        .num { text-align:center; font-variant-numeric:tabular-nums; }
        .daily { background:#fff5d6; font-weight:bold; }
        .note { margin-top:8px; border-left:3px solid #c9952f; padding:5px 8px; background:#fff9e8; }
        footer { position:fixed; bottom:-7mm; width:100%; text-align:center; font-size:7px; }
        .page:before { content:counter(page); }
    </style>
</head>
<body>
<footer>Página <span class="page"></span></footer>
<h1>REPORTE DE CONTROL Y FISCALIZACIÓN — RACIÓN POR DÍA</h1>
<div class="meta">Programa Vaso de Leche · Período {{ date('d/m/Y', strtotime($start_date)) }} - {{ date('d/m/Y', strtotime($end_date)) }} · {{ $days_in_month }} días de atención</div>
<table>
    <colgroup><col style="width:3%"><col style="width:6%"><col style="width:16%"><col style="width:14%"><col style="width:9%"><col style="width:7%"><col style="width:8%"><col style="width:8%"><col style="width:9%"><col style="width:9%"><col style="width:11%"></colgroup>
    <thead><tr><th>N°</th><th>CÓDIGO</th><th>CLUB DE MADRES</th><th>PRESIDENTA</th><th>SECTOR</th><th>BENEFICIARIOS</th><th>TOTAL LECHE</th><th>TOTAL HOJUELA</th><th>RACIÓN LECHE/DÍA</th><th>RACIÓN HOJ./DÍA</th><th>OBSERVACIONES</th></tr></thead>
    <tbody>
    @foreach($associations as $index => $club)
        <tr><td class="num">{{ $index + 1 }}</td><td class="num">{{ $club['codigo'] }}</td><td>{{ $club['nombre'] }}</td><td>{{ $club['presidenta'] }}</td><td>{{ $club['sector'] }}</td><td class="num">{{ $club['beneficiarios'] }}</td><td class="num">{{ $club['leche_total'] }} tarros</td><td class="num">{{ $club['hojuelas_kg'] }} kg</td><td class="num daily">{{ number_format($club['racion_diaria_leche'], 2) }}</td><td class="num daily">{{ number_format($club['racion_diaria_hojuelas'], 2) }}</td><td>{{ $club['observacion'] }}</td></tr>
    @endforeach
    </tbody>
</table>
<div class="note"><strong>Salvaguarda de auditoría:</strong> valores diarios calculados con precisión de 6 decimales y mostrados a 2 decimales. Fórmula: total del producto ÷ {{ $days_in_month }} días.</div>
</body>
</html>
