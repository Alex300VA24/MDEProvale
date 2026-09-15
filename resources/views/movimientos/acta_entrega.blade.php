<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de entrega PVL</title>
    <style>
        @page { size:A4 landscape; margin:9mm 7mm 11mm; }
        body { font-family:Arial,sans-serif; color:#17242b; font-size:8px; margin:0; }
        h1 { text-align:center; color:#143f58; font-size:14px; margin:0 0 3px; }
        h2 { text-align:center; font-size:10px; margin:0 0 8px; color:#256b73; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th { background:#174a68; color:white; padding:5px 3px; border:1px solid #10364d; font-size:7px; }
        td { border:1px solid #9aabb2; padding:4px 3px; height:9mm; overflow-wrap:break-word; }
        tbody tr:nth-child(even) { background:#f2f7f6; }
        .num { text-align:center; font-variant-numeric:tabular-nums; }
        .total td { background:#dcece9; font-weight:bold; }
        .warning { margin-top:8px; padding:6px 8px; border-left:3px solid #c9952f; background:#fff9e8; }
        footer { position:fixed; bottom:-7mm; width:100%; text-align:center; font-size:7px; }
        .page:before { content:counter(page); }
    </style>
</head>
<body>
@php
    $isComplete = $productMode === 'complete';
    $showMilk = $productMode !== 'oat';
    $showOat = $productMode !== 'milk';
    $productTitle = $isComplete
        ? 'LECHE EVAPORADA ENTERA Y HOJUELA DE QUINUA AVENA PRECOCIDA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES'
        : ($showMilk ? 'LECHE EVAPORADA ENTERA' : 'HOJUELA DE QUINUA AVENA PRECOCIDA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES');
    $columnCount = 7 + ($showMilk ? 1 : 0) + ($showOat ? 1 : 0);
@endphp
<footer>Página <span class="page"></span></footer>
@include('movimientos.partials.official_header', [
    'headerTitle' => 'ACTA DE ENTREGA DE PRODUCTOS - PROGRAMA VASO DE LECHE',
    'headerSubtitle' => $productTitle . ' · PERÍODO ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)),
])
<table>
    <thead><tr><th>N°</th><th>CÓD.</th><th>CLUB DE MADRES</th><th>PRESIDENTA</th><th>DIRECCIÓN</th><th>SECTOR</th><th>BENEF.</th>@if($showMilk)<th>LECHE (TARROS)</th>@endif @if($showOat)<th>HOJUELA (KG)</th>@endif<th>FIRMA DE RECEPCIÓN {{ $isComplete ? '' : ($showMilk ? '- LECHE' : '- HOJUELA') }}</th></tr></thead>
    <tbody>
    @foreach($associations as $index => $club)
        <tr><td class="num">{{ $index + 1 }}</td><td class="num">{{ $club['codigo'] }}</td><td>{{ $club['nombre'] }}</td><td>{{ $club['presidenta'] }}</td><td>{{ $club['direccion'] }}</td><td>{{ $club['sector'] }}</td><td class="num">{{ $club['beneficiarios'] }}</td>@if($showMilk)<td class="num">{{ $club['leche_total'] }}</td>@endif @if($showOat)<td class="num">{{ $club['hojuelas_kg'] }}</td>@endif<td></td></tr>
    @endforeach
        <tr class="total"><td colspan="6" style="text-align:right">TOTAL</td><td class="num">{{ $total_beneficiarios }}</td>@if($showMilk)<td class="num">{{ $total_leche_tarros }}</td>@endif @if($showOat)<td class="num">{{ $total_hojuelas_kg }}</td>@endif<td></td></tr>
    </tbody>
</table>
@if(!$isComplete)<div class="warning"><strong>Constancia de contingencia:</strong> esta acta acredita recepción exclusiva de {{ $showMilk ? 'leche evaporada entera' : 'hojuela' }}. No acredita recepción del producto pendiente.</div>@endif
</body>
</html>
