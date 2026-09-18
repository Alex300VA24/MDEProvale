@php
    $v = fn (string $key, $default = '') => data_get($data, $key, $default);
    $num = function ($value, int $decimals = 2) {
        if ($value === null || $value === '') return '';
        return number_format((float) $value, $decimals, '.', ',');
    };
    $num0 = fn ($value) => ($value === null || $value === '') ? '' : number_format((float) $value, 0, '.', ',');
    $pct = fn ($value) => ($value === null || $value === '') ? '' : number_format((float) $value, 2, '.', '');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Formato Ración A - {{ $v('mes_reportado') }} {{ $v('anio_reportado') }}</title>
    @include('reportes.racion._styles')
</head>
<body>
<section class="page">
    <header class="header">
        <div class="cg">CONTRALORIA GENERAL DE LA REPUBLICA</div>
        <div class="annex">ANEXO N°2 DE LA DIRECTIVA N° 015-2013-CG-CRL</div>
        <div class="title">INFORME DE LA RACION DISTRIBUIDA POR EL PROGRAMA DEL VASO DE LECHE</div>
        <div class="format">FORMATO<br>RACION A</div>
    </header>

    <table class="table meta">
        <colgroup><col style="width:19%"><col style="width:50%"><col style="width:13%"><col style="width:18%"></colgroup>
        <tr><td class="label">NOMBRE DE LA MUNICIPALIDAD:</td><td class="value">{{ $v('municipalidad') }}</td><td class="label">MES REPORTADO:</td><td class="value">{{ $v('mes_reportado') }}</td></tr>
        <tr><td class="label">NUM. EXPEDIENTE:</td><td class="value">{{ $v('numero_expediente') }}</td><td class="label">AÑO REPORTADO:</td><td class="value">{{ $v('anio_reportado') }}</td></tr>
        <tr>
            <td class="value" rowspan="3">{{ $v('tipo_municipalidad','MUNICIPALIDAD DISTRITAL') }}</td>
            <td class="value" rowspan="3"><table class="geo-table"><tr><td class="geo-heading">UBICACION<br>GEOGRAFICA</td><td class="geo-label">DEPARTAMENTO</td><td>{{ $v('departamento') }}</td></tr><tr><td></td><td class="geo-label">PROVINCIA</td><td>{{ $v('provincia') }}</td></tr></table></td>
            <td class="label">FECHA DE</td><td class="value">{{ $v('fecha_reporte') }}</td>
        </tr>
        <tr><td class="label"></td><td class="value"></td></tr>
        <tr><td class="label">COD. ENVIO:</td><td class="value">{{ $v('codigo_envio') }}</td></tr>
    </table>

    <div class="section-label">RACIONES O FORMULAS DISTRIBUIDAS</div>
    <div class="section-label" style="margin-top:0">RACIONES COMPUESTAS POR UN SOLO ALIMENTO</div>
    <table class="table ration-table">
        <colgroup><col style="width:3%"><col style="width:33%"><col style="width:10%"><col style="width:11%"><col style="width:11%"><col style="width:11%"><col style="width:10.5%"><col style="width:10.5%"></colgroup>
        <thead><tr><th rowspan="2">N°</th><th rowspan="2">LECHE Y/O ALIMENTOS EQUIVALENTES<br>7</th><th colspan="2">CANTIDAD POR RACION</th><th colspan="2">DIAS ATENDIDOS POR SEMANA</th><th colspan="2">TIPO DE RACION DISTRIBUIDA</th></tr><tr><th>GRAMOS (g)<br>8</th><th>CENTIMETROS<br>CUBICOS (cc)<br>9</th><th>1° PRIORIDAD<br>10</th><th>2° PRIORIDAD<br>11</th><th>CRUDA<br>12</th><th>PREPARADA<br>13</th></tr></thead>
        <tbody>
        @forelse($v('raciones_un_alimento',[]) as $r)
            <tr><td>{{ $loop->iteration }}</td><td>{{ data_get($r,'alimento','') }}</td><td>{{ $num(data_get($r,'gramos')) }}</td><td>{{ $num(data_get($r,'cc')) }}</td><td>{{ data_get($r,'dias_prioridad_1','') }}</td><td>{{ data_get($r,'dias_prioridad_2','') }}</td><td>@if(data_get($r,'tipo_racion') === 'CRUDA')<span class="x">x</span>@endif</td><td>@if(data_get($r,'tipo_racion') === 'PREPARADA')<span class="x">x</span>@endif</td></tr>
        @empty
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-label">RACIONES COMPUESTAS POR DOS O MAS ALIMENTOS</div>
    <table class="table ration-table">
        <colgroup><col style="width:3%"><col style="width:18%"><col style="width:5%"><col style="width:5%"><col style="width:15%"><col style="width:5%"><col style="width:5%"><col style="width:14%"><col style="width:4.5%"><col style="width:4.5%"><col style="width:5%"><col style="width:5%"><col style="width:5.5%"><col style="width:5.5%"></colgroup>
        <thead><tr><th rowspan="3">N°</th><th rowspan="3">LECHE/ALIMENTO<br>EQUIVALENTE/INSUMO<br>COMPLEMENTARIO<br>14</th><th colspan="2">CANTIDAD POR<br>RACION</th><th rowspan="3">LECHE/ALIMENTO<br>EQUIVALENTE/INSUMO<br>COMPLEMENTARIO<br>17</th><th colspan="2">CANTIDAD POR<br>RACION</th><th rowspan="3">LECHE/ALIMENTO<br>EQUIVALENTE/INSUMO<br>COMPLEMENTARIO<br>20</th><th colspan="2">CANTIDAD POR<br>RACION</th><th colspan="2">DIAS ATENDIDOS<br>POR SEMANA</th><th colspan="2">TIPO DE RACION<br>DISTRIBUIDA</th></tr><tr><th>g</th><th>cc</th><th>g</th><th>cc</th><th>g</th><th>cc</th><th>1°<br>PRIOR.<br>23</th><th>2°<br>PRIOR.<br>24</th><th>CRUDA<br>25</th><th>PREPARADA<br>26</th></tr><tr><th>15</th><th>16</th><th>18</th><th>19</th><th>21</th><th>22</th><th></th><th></th><th></th><th></th></tr></thead>
        <tbody>
        @forelse($v('raciones_compuestas',[]) as $r)
            <tr><td>{{ $loop->iteration }}</td><td class="food">{{ data_get($r,'alimento1','') }}</td><td>{{ $num(data_get($r,'alimento1_gramos')) }}</td><td>{{ $num(data_get($r,'alimento1_cc')) }}</td><td class="food">{{ data_get($r,'alimento2','') }}</td><td>{{ $num(data_get($r,'alimento2_gramos')) }}</td><td>{{ $num(data_get($r,'alimento2_cc')) }}</td><td class="food">{{ data_get($r,'alimento3','') }}</td><td>{{ $num(data_get($r,'alimento3_gramos')) }}</td><td>{{ $num(data_get($r,'alimento3_cc')) }}</td><td>{{ data_get($r,'dias_prioridad_1','') }}</td><td>{{ data_get($r,'dias_prioridad_2','') }}</td><td>@if(data_get($r,'tipo_racion') === 'CRUDA')<span class="x">x</span>@endif</td><td>@if(data_get($r,'tipo_racion') === 'PREPARADA')<span class="x">x</span>@endif</td></tr>
        @empty
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-label">INFORMACION SOBRE LA DISTRIBUCION DE ALIMENTOS, SEGUN HOJA DE DISTRIBUCION O ENTREGA</div>
    <table class="table distribution-table">
        <colgroup><col style="width:3%"><col style="width:35%"><col style="width:12%"><col style="width:12%"><col style="width:14%"><col style="width:12%"><col style="width:12%"></colgroup>
        <thead><tr><th>N°</th><th>LECHE/ALIMENTOS EQUIVALENTES/INSUMOS<br>COMPLEMENTARIOS<br>27</th><th>CANTIDAD EN<br>KILOGRAMOS (Kg)<br>28</th><th>CANTIDAD EN LITROS<br>(L)<br>29</th><th>FECHA DE DISTRIBUCION<br>30</th><th>FECHA DE ATENCION<br>DEL 31</th><th>AL 32</th></tr></thead>
        <tbody>
        @forelse($v('distribuciones',[]) as $d)
            <tr><td>{{ $loop->iteration }}</td><td>{{ data_get($d,'producto','') }}</td><td>{{ $num(data_get($d,'cantidad_kg')) }}</td><td>{{ $num(data_get($d,'cantidad_litros')) }}</td><td>{{ data_get($d,'fecha_distribucion','') }}</td><td>{{ data_get($d,'fecha_inicio_atencion','') }}</td><td>{{ data_get($d,'fecha_fin_atencion','') }}</td></tr>
        @empty
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-label">INFORMACION DEL CERTIFICADO DE CALIDAD FISICO QUIMICO O BROMATOLOGICO DE UN LABORATORIO</div>
    <table class="table quality-table">
        <colgroup><col style="width:3%"><col style="width:20%"><col style="width:19%"><col style="width:18%"><col style="width:11%"><col style="width:7%"><col style="width:9%"><col style="width:13%"></colgroup>
        <thead><tr><th>N°</th><th>LECHE/ALIMENTOS<br>EQUIVALENTES/INSUMOS COMPL.<br>33</th><th>NOMBRE DEL LABORATORIO<br>34</th><th>NUMERO DE CERTIFICADO FISICO QUIMICO O BROMATOLOGICO<br>35</th><th>FECHA DE EMISION DE CERTIFICADO<br>36</th><th>PROV. PRESENTO CERTIF. MICROBIO.<br>37</th><th>N° DE LOTE PRODUC.<br>38</th><th>FECHA DE VTO. DE LOTE PRODUC.<br>39</th></tr></thead>
        <tbody>
        @forelse($v('certificados',[]) as $c)
            @php($micro = data_get($c,'certificado_microbiologico'))
            <tr><td>{{ $loop->iteration }}</td><td>{{ data_get($c,'producto','') }}</td><td>{{ data_get($c,'laboratorio','') }}</td><td>{{ data_get($c,'numero_certificado','') }}</td><td>{{ data_get($c,'fecha_emision','') }}</td><td>{{ $micro === null ? '' : ($micro ? 'SI' : 'NO') }}</td><td>{{ data_get($c,'numero_lote','') }}</td><td>{{ data_get($c,'fecha_vencimiento','') }}</td></tr>
        @empty
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-label">INSUMOS QUE COMPONEN LA LECHE Y/O ALIMENTOS EQUIVALENTES DISTRIBUIDOS, SEGUN ESPECIFICACIONES TECNICAS O DECLARACION JURADA PRESENTADA POR EL FABRICANTE AL SOLICITAR EL REGISTRO SANITARIO</div>
    <table class="table composition-table">
        <colgroup><col style="width:3%"><col style="width:47%"><col style="width:33%"><col style="width:17%"></colgroup>
        <thead><tr><th>N°</th><th>NOMBRE DE LECHE/ALIMENTO EQUIVALENTE&nbsp; 40</th><th>INSUMO QUE COMPONE LA MEZCLA&nbsp; 41</th><th>PORCENTAJE (%) DE COMPOSICION&nbsp; 42</th></tr></thead>
        <tbody>
        @forelse($v('composicion',[]) as $c)
            <tr><td>{{ $loop->iteration }}</td><td>{{ data_get($c,'producto','') }}</td><td>{{ data_get($c,'insumo','') }}</td><td>{{ $pct(data_get($c,'porcentaje')) }}</td></tr>
        @empty
            <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-label">BENEFICIARIOS ZONA RURAL</div>
    <table class="table benef-table"><thead><tr><th>MENORES DE 1<br>AÑO 46</th><th>NIÑOS DE 1 A 6<br>AÑOS 47</th><th>MADRES<br>GESTANTES 48</th><th>MADRES<br>LACTANTES 49</th><th>7 A 13 AÑOS 50</th><th>PERSONAS CON<br>TBC 51</th><th>ANCIANOS 52</th><th>DISCAPACITADOS<br>53</th><th>TOTAL 54</th></tr></thead></table>
    <div class="footer-page">Página 1/2</div>
</section>

<section class="page">
    <header class="header"><div class="cg">CONTRALORIA GENERAL DE LA REPUBLICA</div><div class="annex">ANEXO N°2 DE LA DIRECTIVA N° 015-2013-CG-CRL</div><div class="title">INFORME DE LA RACION DISTRIBUIDA POR EL PROGRAMA DEL VASO DE LECHE</div><div class="format">FORMATO<br>RACION A</div></header>
    <table class="table meta">
        <colgroup><col style="width:19%"><col style="width:50%"><col style="width:13%"><col style="width:18%"></colgroup>
        <tr><td class="label">NOMBRE DE LA MUNICIPALIDAD:</td><td class="value">{{ $v('municipalidad') }}</td><td class="label">MES REPORTADO:</td><td class="value">{{ $v('mes_reportado') }}</td></tr>
        <tr><td class="label">NUM. EXPEDIENTE:</td><td class="value">{{ $v('numero_expediente') }}</td><td class="label">AÑO REPORTADO:</td><td class="value">{{ $v('anio_reportado') }}</td></tr>
        <tr><td class="value" rowspan="3">{{ $v('tipo_municipalidad','MUNICIPALIDAD DISTRITAL') }}</td><td class="value" rowspan="3"><table class="geo-table"><tr><td class="geo-heading">UBICACION<br>GEOGRAFICA</td><td class="geo-label">DEPARTAMENTO</td><td>{{ $v('departamento') }}</td></tr><tr><td></td><td class="geo-label">PROVINCIA</td><td>{{ $v('provincia') }}</td></tr></table></td><td class="label">FECHA DE</td><td class="value">{{ $v('fecha_reporte') }}</td></tr>
        <tr><td class="label"></td><td class="value"></td></tr><tr><td class="label">COD. ENVIO:</td><td class="value">{{ $v('codigo_envio') }}</td></tr>
    </table>

    <div class="section-label">BENEFICIARIOS ZONA RURAL</div>
    <table class="table benef-table"><thead><tr><th>MENORES DE 1<br>AÑO 46</th><th>NIÑOS DE 1 A 6<br>AÑOS 47</th><th>MADRES<br>GESTANTES 48</th><th>MADRES<br>LACTANTES 49</th><th>7 A 13 AÑOS 50</th><th>PERSONAS CON<br>TBC 51</th><th>ANCIANOS 52</th><th>DISCAPACITADOS<br>53</th><th>TOTAL 54</th></tr></thead><tbody><tr><td>{{ $num0($v('beneficiarios.rural.menores_1_anio',null)) }}</td><td>{{ $num0($v('beneficiarios.rural.ninos_1_a_6',null)) }}</td><td>{{ $num0($v('beneficiarios.rural.madres_gestantes',null)) }}</td><td>{{ $num0($v('beneficiarios.rural.madres_lactantes',null)) }}</td><td>{{ $num0($v('beneficiarios.rural.personas_7_a_13',null)) }}</td><td>{{ $num0($v('beneficiarios.rural.personas_tbc',null)) }}</td><td>{{ $num0($v('beneficiarios.rural.ancianos',null)) }}</td><td>{{ $num0($v('beneficiarios.rural.discapacitados',null)) }}</td><td>{{ $num0($v('beneficiarios.rural.total',null)) }}</td></tr></tbody></table>

    <div class="section-label">BENEFICIARIOS ZONA URBANA</div>
    <table class="table benef-table"><thead><tr><th>MENORES DE 1<br>AÑO 55</th><th>NIÑOS DE 1 A 6<br>AÑOS 56</th><th>MADRES<br>GESTANTES 57</th><th>MADRES<br>LACTANTES 58</th><th>7 A 13 AÑOS 59</th><th>PERSONAS CON<br>TBC 60</th><th>ANCIANOS 61</th><th>DISCAPACITADOS<br>62</th><th>TOTAL 63</th></tr></thead><tbody><tr><td>{{ $num0($v('beneficiarios.urbana.menores_1_anio',null)) }}</td><td>{{ $num0($v('beneficiarios.urbana.ninos_1_a_6',null)) }}</td><td>{{ $num0($v('beneficiarios.urbana.madres_gestantes',null)) }}</td><td>{{ $num0($v('beneficiarios.urbana.madres_lactantes',null)) }}</td><td>{{ $num0($v('beneficiarios.urbana.personas_7_a_13',null)) }}</td><td>{{ $num0($v('beneficiarios.urbana.personas_tbc',null)) }}</td><td>{{ $num0($v('beneficiarios.urbana.ancianos',null)) }}</td><td>{{ $num0($v('beneficiarios.urbana.discapacitados',null)) }}</td><td>{{ $num0($v('beneficiarios.urbana.total',null)) }}</td></tr></tbody></table>

    <table class="table" style="margin-top:1.5mm"><tr><td class="left" style="font-weight:700;width:83%">(64) CANTIDAD DE COMITES DEL PVL ATENDIDOS</td><td class="right">{{ $num0($v('cantidad_comites_atendidos',null)) }}</td></tr></table>
    <table class="table signature-grid" style="margin-top:1mm"><colgroup><col style="width:56%"><col style="width:44%"></colgroup><tr><td><span class="caption">(65) APELLIDOS Y NOMBRES DEL PRESIDENTE DEL COMITE DE ADMINISTRACION</span>{{ $v('presidente_comite_administracion') }}</td><td><div style="height:22mm;display:flex;align-items:flex-end;justify-content:center;font-size:4.8pt">FIRMA Y SELLO</div></td></tr></table>
    <table class="table signature-grid" style="margin-top:1mm"><colgroup><col style="width:34%"><col style="width:22%"><col style="width:44%"></colgroup><tr><td><span class="caption">(66) APELLIDOS Y NOMBRES DEL REPRESENTANTE DEL MINISTERIO DE SALUD</span>{{ $v('representante_ministerio_salud') }}</td><td><span class="caption">(67) PROFESION O GRADO TECNICO</span>{{ $v('profesion_representante_salud') }}</td><td><div style="height:22mm;display:flex;align-items:flex-end;justify-content:center;font-size:4.8pt">FIRMA Y SELLO</div></td></tr></table>
    <div class="footer-page">Página 2/2</div>
</section>
</body>
</html>
