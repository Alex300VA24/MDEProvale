<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reparto por vueltas PVL</title>
    <style>
        @page { size:A4 landscape; margin:8mm 6mm 10mm; }
        body { font-family:Arial,sans-serif; color:#17242b; font-size:7px; margin:0; }
        .route { page-break-after:always; }
        .route:last-child { page-break-after:auto; }
        h1 { font-size:13px; color:#143f58; text-align:center; margin:0 0 2px; }
        .meta { text-align:center; margin-bottom:7px; }
        .route-title { background:#174a68; color:#fff; padding:6px 8px; font-size:11px; font-weight:bold; margin-bottom:4px; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th { background:#256b73; color:#fff; border:1px solid #174a68; padding:4px 2px; font-size:6.5px; }
        td { border:1px solid #9aabb2; padding:3px 2px; overflow-wrap:break-word; }
        tbody tr:nth-child(even) { background:#f2f7f6; }
        .num { text-align:center; font-variant-numeric:tabular-nums; }
        .subtotal td { background:#dcece9; font-weight:bold; border-top:2px solid #256b73; }
        .summary { margin-top:10px; width:68%; margin-left:auto; }
        .summary th { background:#c9952f; border-color:#9a711f; }
        footer { position:fixed; bottom:-6mm; width:100%; text-align:center; font-size:7px; }
        .page:before { content:counter(page); }
    </style>
</head>
<body>
<footer>Página <span class="page"></span></footer>
@forelse($vueltas as $routeIndex => $route)
<section class="route">
    <h1>REPARTO LOGÍSTICO POR VUELTAS — PROGRAMA VASO DE LECHE</h1>
    <div class="meta">Período {{ date('d/m/Y', strtotime($start_date)) }} - {{ date('d/m/Y', strtotime($end_date)) }}</div>
    <div class="route-title">VUELTA N° {{ $route['vuelta'] }}</div>
    <table>
        <colgroup><col style="width:3%"><col style="width:5%"><col style="width:15%"><col style="width:13%"><col style="width:16%"><col style="width:9%"><col style="width:6%"><col style="width:6%"><col style="width:5%"><col style="width:5%"><col style="width:6%"><col style="width:5%"><col style="width:5%"></colgroup>
        <thead><tr><th>N°</th><th>COD</th><th>CLUB DE MADRES</th><th>PRESIDENTA</th><th>DIRECCIÓN</th><th>SECTOR</th><th>BENEF.</th><th>LECHE</th><th>CAJAS</th><th>TARROS</th><th>HOJUELA</th><th>SACOS</th><th>KILOS</th></tr></thead>
        <tbody>
        @foreach($route['clubs'] as $index => $club)
            <tr><td class="num">{{ $index + 1 }}</td><td class="num">{{ $club['codigo'] }}</td><td>{{ $club['nombre'] }}</td><td>{{ $club['presidenta'] }}</td><td>{{ $club['direccion'] }}</td><td>{{ $club['sector'] }}</td><td class="num">{{ $club['beneficiarios'] }}</td><td class="num">{{ $club['leche_total'] }}</td><td class="num">{{ $club['leche_cajas'] }}</td><td class="num">{{ $club['leche_tarros'] }}</td><td class="num">{{ $club['hojuelas_kg'] }}</td><td class="num">{{ $club['hojuelas_sacos'] }}</td><td class="num">{{ $club['hojuelas_kilos'] }}</td></tr>
        @endforeach
            <tr class="subtotal"><td colspan="6" style="text-align:right">SUBTOTAL VUELTA {{ $route['vuelta'] }}</td><td class="num">{{ $route['total_beneficiarios'] }}</td><td class="num">{{ $route['total_leche'] }}</td><td class="num">{{ $route['leche_cajas'] }}</td><td class="num">{{ $route['leche_tarros'] }}</td><td class="num">{{ $route['total_hojuelas'] }}</td><td class="num">{{ $route['hojuelas_sacos'] }}</td><td class="num">{{ $route['hojuelas_kilos'] }}</td></tr>
        </tbody>
    </table>
    @if($routeIndex === count($vueltas) - 1)
    <table class="summary"><thead><tr><th colspan="7">RESUMEN FINAL DE CARGA DEL DÍA</th></tr><tr><th>BENEF.</th><th>LECHE</th><th>CAJAS</th><th>TARROS SUELTOS</th><th>HOJUELA</th><th>SACOS</th><th>KG SUELTOS</th></tr></thead><tbody><tr><td class="num">{{ $total_beneficiarios }}</td><td class="num">{{ $total_leche_tarros }}</td><td class="num">{{ $total_leche_cajas }}</td><td class="num">{{ $total_leche_sueltos }}</td><td class="num">{{ $total_hojuelas_kg }}</td><td class="num">{{ $total_hojuelas_sacos }}</td><td class="num">{{ $total_hojuelas_sueltos }}</td></tr></tbody></table>
    @endif
</section>
@empty
<h1>REPARTO LOGÍSTICO POR VUELTAS</h1><p style="text-align:center">No hay comités con beneficiarios para este período.</p>
@endforelse
</body>
</html>
