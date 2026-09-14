<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargo general PVL</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 8mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #17242b; font-size: 8px; margin: 0; }
        .head { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .head td { border: 0; vertical-align: middle; }
        .logo { width: 58px; }
        h1 { font-size: 14px; margin: 0 0 4px; text-align: center; color: #143f58; }
        .period { text-align: center; font-size: 9px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data th { background: #174a68; color: white; font-size: 7px; padding: 5px 3px; border: 1px solid #10364d; }
        .data td { border: 1px solid #9aabb2; padding: 4px 3px; vertical-align: middle; overflow-wrap: break-word; }
        .data tbody tr:nth-child(even) { background: #f2f7f6; }
        .num { text-align: center; font-variant-numeric: tabular-nums; }
        .total td { background: #dcece9; font-weight: bold; border-top: 2px solid #256b73; }
        .signatures { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .signatures td { width: 33.33%; text-align: center; padding: 0 25px; }
        .line { border-top: 1px solid #17242b; padding-top: 4px; font-weight: bold; }
        footer { position: fixed; bottom: -7mm; width: 100%; text-align: center; font-size: 7px; }
        .page:before { content: counter(page); }
    </style>
</head>
<body>
<footer>Página <span class="page"></span></footer>
<table class="head"><tr>
    <td style="width:150px"><img class="logo" src="{{ public_path('img/muni2.png') }}" alt="Municipalidad"><br><strong>MUNICIPALIDAD DISTRITAL<br>DE LA ESPERANZA</strong></td>
    <td><h1>CARGO GENERAL DE ENTREGA DE PRODUCTOS - PROGRAMA VASO DE LECHE</h1><div class="period">Período: {{ date('d/m/Y', strtotime($start_date)) }} - {{ date('d/m/Y', strtotime($end_date)) }}</div></td>
    <td style="width:150px;text-align:right">Emisión: {{ now()->format('d/m/Y H:i') }}</td>
</tr></table>
<table class="data">
    <colgroup><col style="width:3%"><col style="width:6%"><col style="width:16%"><col style="width:14%"><col style="width:17%"><col style="width:10%"><col style="width:8%"><col style="width:9%"><col style="width:9%"><col style="width:8%"></colgroup>
    <thead><tr><th>N°</th><th>CÓDIGO</th><th>CLUB DE MADRES</th><th>PRESIDENTA</th><th>DIRECCIÓN</th><th>SECTOR</th><th>BENEFICIARIOS</th><th>TOTAL LECHE (TARROS)</th><th>TOTAL HOJUELA (KG)</th><th>FIRMA</th></tr></thead>
    <tbody>
    @foreach($associations as $index => $club)
        <tr><td class="num">{{ $index + 1 }}</td><td class="num">{{ $club['codigo'] }}</td><td>{{ $club['nombre'] }}</td><td>{{ $club['presidenta'] }}</td><td>{{ $club['direccion'] }}</td><td>{{ $club['sector'] }}</td><td class="num">{{ $club['beneficiarios'] }}</td><td class="num">{{ $club['leche_total'] }}</td><td class="num">{{ $club['hojuelas_kg'] }}</td><td></td></tr>
    @endforeach
        <tr class="total"><td colspan="6" style="text-align:right">TOTALES GENERALES</td><td class="num">{{ $total_beneficiarios }}</td><td class="num">{{ $total_leche_tarros }}</td><td class="num">{{ $total_hojuelas_kg }}</td><td></td></tr>
    </tbody>
</table>
<table class="signatures"><tr><td><div class="line">SUBGERENCIA</div></td><td><div class="line">RESPONSABLE DE ALMACÉN</div></td><td><div class="line">CONTROL INTERNO</div></td></tr></table>
</body>
</html>
