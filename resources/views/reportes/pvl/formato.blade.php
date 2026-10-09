@php
    $v = fn (string $key, $default = '') => data_get($data, $key, $default);
    $num = function ($value, int $decimals = 2) {
        if ($value === null || $value === '') return '';
        return number_format((float) $value, $decimals, '.', ',');
    };
    $money = fn ($value) => $num($value, 2);
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Formato PVL - {{ $v('mes_reportado') }} {{ $v('anio_reportado') }}</title>
    @include('reportes.pvl._styles')
</head>
<body>
<section class="page">
    @if($v('fecha_hora_impresion'))
        <div class="print-time">{{ $v('fecha_hora_impresion') }}</div>
    @endif
    <header class="header">
        <div class="cg">CONTRALORIA GENERAL DE LA REPUBLICA</div>
        <div class="annex">ANEXO N°1 DE LA DIRECTIVA N° 015-2013-CG-CRL</div>
        <div class="title">INFORMACION MENSUAL DE GASTOS E INGRESOS DEL PROGRAMA DEL VASO DE LECHE</div>
        <div class="format">FORMATO PVL</div>
    </header>

    @include('reportes._identificacion')

    <div class="section-label">GASTOS EN LECHE Y/O ALIMENTOS EQUIVALENTES (AVENA, SOYA, KIWICHA, ENRIQUECIDO LACTEOS, ETC.)</div>
    <table class="table purchase-table">
        <colgroup><col style="width:2.2%"><col style="width:2.5%"><col style="width:16.5%"><col style="width:7.5%"><col style="width:6.2%"><col style="width:13%"><col style="width:7%"><col style="width:6.5%"><col style="width:3.7%"><col style="width:5%"><col style="width:7.6%"><col style="width:7.5%"><col style="width:7.5%"><col style="width:7.3%"></colgroup>
        <thead>
        <tr><th rowspan="2">N°</th><th rowspan="2">(P)<br>7</th><th rowspan="2">LECHE Y/O ALIMENTOS EQUIVALENTES<br>8</th><th rowspan="2">MARCA<br>9</th><th rowspan="2">ORIGEN<br>10</th><th rowspan="2">PROVEEDOR<br>11</th><th rowspan="2">RUC<br>12</th><th rowspan="2">TIPO COMP.<br>PAGO<br>13</th><th colspan="2">COMP. PAGO&nbsp; 14</th><th rowspan="2">FECHA DE EMISION<br>15</th><th rowspan="2">CANTIDAD<br>KILOS(Kgs)<br>16</th><th rowspan="2">CANTIDAD<br>LITROS(Lts)<br>17</th><th rowspan="2">IMPORTE S/.<br>18</th></tr>
        <tr><th>SERIE</th><th>NUMERO</th></tr>
        </thead>
        <tbody>
        @forelse($v('compras_alimentos', []) as $c)
            <tr>
                <td>{{ $loop->iteration }}</td><td>{{ data_get($c, 'clasificacion', '') }}</td><td class="food">{{ data_get($c, 'producto', '') }}</td>
                <td>{{ data_get($c, 'marca', '') }}</td><td>{{ data_get($c, 'origen', '') }}</td><td>{{ data_get($c, 'proveedor', '') }}</td>
                <td>{{ data_get($c, 'ruc', '') }}</td><td>{{ data_get($c, 'tipo_comprobante', '') }}</td><td>{{ data_get($c, 'serie', '') }}</td><td>{{ data_get($c, 'numero_comprobante', '') }}</td>
                <td>{{ data_get($c, 'fecha_emision', '') }}</td><td class="right">{{ $num(data_get($c, 'cantidad_kg')) }}</td><td class="right">{{ $num(data_get($c, 'cantidad_litros')) }}</td><td class="right">{{ $money(data_get($c, 'importe')) }}</td>
            </tr>
        @empty
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endforelse
        <tr><td colspan="13" class="right" style="font-weight:700">(19) TOTAL COMPRAS S/.</td><td class="right" style="font-weight:700">{{ $money($v('total_compras_alimentos', null)) }}</td></tr>
        </tbody>
    </table>

    <div class="section-label">GASTOS EN INSUMOS COMPLEMENTARIOS (AZUCAR, COCOA, CANELA, PAN, ETC.)</div>
    <table class="table purchase-table">
        <colgroup><col style="width:2.2%"><col style="width:2.5%"><col style="width:16.5%"><col style="width:7.5%"><col style="width:6.2%"><col style="width:13%"><col style="width:7%"><col style="width:6.5%"><col style="width:3.7%"><col style="width:5%"><col style="width:7.6%"><col style="width:7.5%"><col style="width:7.5%"><col style="width:7.3%"></colgroup>
        <thead><tr><th rowspan="2">N°</th><th rowspan="2">(P)<br>20</th><th rowspan="2">INSUMOS COMPLEMENTARIOS<br>21</th><th rowspan="2">MARCA<br>22</th><th rowspan="2">ORIGEN<br>23</th><th rowspan="2">PROVEEDOR<br>24</th><th rowspan="2">RUC<br>25</th><th rowspan="2">TIPO COMP.<br>PAGO<br>26</th><th colspan="2">COMP. PAGO&nbsp; 27</th><th rowspan="2">FECHA DE EMISION<br>28</th><th rowspan="2">CANTIDAD<br>KILOS(Kgs)<br>29</th><th rowspan="2">CANTIDAD<br>LITROS(Lts)<br>30</th><th rowspan="2">IMPORTE S/.<br>31</th></tr><tr><th>SERIE</th><th>NUMERO</th></tr></thead>
        <tbody>
        @forelse($v('compras_insumos', []) as $c)
            <tr><td>{{ $loop->iteration }}</td><td>{{ data_get($c,'clasificacion','') }}</td><td>{{ data_get($c,'producto','') }}</td><td>{{ data_get($c,'marca','') }}</td><td>{{ data_get($c,'origen','') }}</td><td>{{ data_get($c,'proveedor','') }}</td><td>{{ data_get($c,'ruc','') }}</td><td>{{ data_get($c,'tipo_comprobante','') }}</td><td>{{ data_get($c,'serie','') }}</td><td>{{ data_get($c,'numero_comprobante','') }}</td><td>{{ data_get($c,'fecha_emision','') }}</td><td class="right">{{ $num(data_get($c,'cantidad_kg')) }}</td><td class="right">{{ $num(data_get($c,'cantidad_litros')) }}</td><td class="right">{{ $money(data_get($c,'importe')) }}</td></tr>
        @empty
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endforelse
        <tr><td colspan="13" class="right" style="font-weight:700">(32) TOTAL COMPRAS S/.</td><td class="right" style="font-weight:700">{{ $money($v('total_compras_insumos', null)) }}</td></tr>
        </tbody>
    </table>

    <div class="section-label">VALORIZACION DE LECHE / ALIMENTOS EQUIVALENTES / INSUMOS COMPLEMENTARIOS DONADOS O ADQUISICIONES CON DONACIONES</div>
    <table class="table purchase-table">
        <colgroup><col style="width:2.2%"><col style="width:31.5%"><col style="width:12%"><col style="width:7%"><col style="width:27%"><col style="width:8%"><col style="width:6%"><col style="width:6.3%"></colgroup>
        <thead><tr><th>N°</th><th>LECHE Y/O ALIMENTO EQUIVALENTE / INSUMOS COMPLEMENTARIOS<br>33</th><th>MARCA<br>34</th><th>ORIGEN<br>35</th><th>DONANTE<br>36</th><th>CANTIDAD<br>KILOS(Kgs)<br>37</th><th>CANTIDAD<br>LITROS(Lts)<br>38</th><th>IMPORTE S/.<br>39</th></tr></thead>
        <tbody>
        @forelse($v('donaciones', []) as $d)
            <tr><td>{{ $loop->iteration }}</td><td>{{ data_get($d,'producto','') }}</td><td>{{ data_get($d,'marca','') }}</td><td>{{ data_get($d,'origen','') }}</td><td>{{ data_get($d,'donante','') }}</td><td class="right">{{ $num(data_get($d,'cantidad_kg')) }}</td><td class="right">{{ $num(data_get($d,'cantidad_litros')) }}</td><td class="right">{{ $money(data_get($d,'importe')) }}</td></tr>
        @empty
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endforelse
        <tr><td colspan="7" class="right" style="font-weight:700">(40) TOTAL COMPRAS S/.</td><td class="right" style="font-weight:700">{{ $money($v('total_donaciones', null)) }}</td></tr>
        </tbody>
    </table>

    <table class="table summary-row">
        <tr><td class="left" style="width:87%">(41) TOTAL GASTOS OPERATIVOS DEL PROGRAMA</td><td class="right">{{ $money($v('total_gastos_operativos', null)) }}</td></tr>
        <tr><td class="left">(42) TOTAL GASTOS</td><td class="right">{{ $money($v('total_gastos', null)) }}</td></tr>
    </table>

    <div class="section-label">FUENTES DE FINANCIAMIENTO</div>
    <table class="table finance-table">
        <tr><td class="label">(43) SALDO INICIAL DE RECURSOS TRANSFERIDOS DEL TESORO PUBLICO</td><td class="value">{{ $money($v('financiamiento.saldo_inicial_tesoro', null)) }}</td></tr>
        <tr><td class="label">(44) TRANSFERENCIA DEL TESORO PUBLICO DEL MES REPORTADO</td><td class="value">{{ $money($v('financiamiento.transferencia_tesoro', null)) }}</td></tr>
        <tr><td class="label">(45) RECURSOS DIRECTAMENTE RECAUDADOS</td><td class="value">{{ $money($v('financiamiento.recursos_directamente_recaudados', null)) }}</td></tr>
        <tr><td class="label">(46) FONDO DE COMPENSACION MUNICIPAL (FONCOMUN)</td><td class="value">{{ $money($v('financiamiento.foncomun', null)) }}</td></tr>
        <tr><td class="label">(47) TOTAL DONACIONES</td><td class="value">{{ $money($v('financiamiento.donaciones', null)) }}</td></tr>
        <tr><td class="label">(48) INTERESES</td><td class="value">{{ $money($v('financiamiento.intereses', null)) }}</td></tr>
        <tr><td class="label">(49) TOTAL RECURSOS</td><td class="value">{{ $money($v('financiamiento.total_recursos', null)) }}</td></tr>
        <tr><td class="label">(50) SALDO FINAL DE RECURSOS TRANSFERIDOS DEL TESORO PUBLICO</td><td class="value">{{ $money($v('financiamiento.saldo_final', null)) }}</td></tr>
    </table>
    <div class="footer-page">Página 1/2</div>
</section>

<div class="page-break"></div>

<section class="page">
    <header class="header">
        <div class="cg">CONTRALORIA GENERAL DE LA REPUBLICA</div>
        <div class="annex">ANEXO N°1 DE LA DIRECTIVA N° 015-2013-CG-CRL</div>
        <div class="title">INFORMACION MENSUAL DE GASTOS E INGRESOS DEL PROGRAMA DEL VASO DE LECHE</div>
        <div class="format">FORMATO PVL</div>
    </header>
    @include('reportes._identificacion')
    <table class="table signature-grid">
        <colgroup><col style="width:66%"><col style="width:34%"></colgroup>
        <tr><td><span class="caption">(51) APELLIDOS Y NOMBRES DEL PRESIDENTE DEL COMITE DE ADMINISTRACION</span><span class="name">{{ $v('presidente_comite_administracion') }}</span></td><td><span class="sigline">FIRMA Y SELLO</span></td></tr>
        <tr><td><span class="caption">(52) APELLIDOS Y NOMBRES DEL DIRECTOR DE ADMINISTRACION</span><span class="name">{{ $v('director_administracion') }}</span></td><td><span class="sigline">FIRMA Y SELLO</span></td></tr>
    </table>
    <div class="footer-page">Página 2/2</div>
</section>
</body>
</html>
