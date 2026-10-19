@php
    $v = fn ($key, $default = '') => e(trim((string) ($data[$key] ?? $default)));
    // Observaciones: una línea subrayada por renglón escrito, mínimo 3 para poder anotar a mano.
    $obsLines = function ($key) use ($data) {
        $lines = preg_split('/\R/', trim((string) ($data[$key] ?? ''))) ?: [];
        $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));
        while (count($lines) < 3) { $lines[] = ''; }
        return $lines;
    };
    $mark = fn ($value, $option) => $value === $option ? 'X' : '&nbsp;';
@endphp
<style>
    @page { margin: 15mm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5pt; color: #111; }
    .fill { display: inline-block; min-width: 110px; color: #1a3a8a; font-style: italic; border-bottom: 1px solid #000; padding: 0 4px; }
    .row { width: 100%; border-collapse: collapse; margin: 0; }
    .row td { padding: 5px 0; text-transform: uppercase; font-size: 9.5pt; vertical-align: middle; }
    .sn { width: 46px; text-align: center; }
    .box { display: inline-block; width: 15px; height: 15px; border: 1.2px solid #000; text-align: center; line-height: 15px; font-weight: bold; font-size: 10pt; color: #1a3a8a; }
    .fill-line { color: #1a3a8a; font-style: italic; border-bottom: 1px solid #000; padding: 4px 4px 1px; min-height: 16px; text-transform: none; }
    .rule { border-top: 1.5px solid #000; margin: 10px 0; }
</style>

<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="vertical-align:middle;"><img src="{{ public_path('img/muni2.png') }}" style="width:54px; height:auto; vertical-align:middle; margin-right:8px;" alt="Logo de la Municipalidad Distrital de La Esperanza"><div style="display:inline-block; vertical-align:middle; text-align:left; line-height:1.25;"><div style="font-size:8pt; font-weight:bold;">MUNICIPALIDAD DISTRITAL</div><div style="font-size:8pt; font-weight:bold;">DE LA ESPERANZA</div><div style="font-size:7.5pt; font-weight:normal;">O.F. Vaso de Leche</div></div></td>
        <td style="width:130px; text-align:center; vertical-align:top; font-size:10.5pt;">
            <div class="fill">{!! $v('folio_month') ?: '&nbsp;' !!}</div>
            <div class="fill" style="margin-top:4px;">{!! $v('folio_number') ?: '&nbsp;' !!}</div>
        </td>
    </tr>
</table>

<div style="border:2px solid #000; width:60%; margin:10px auto 12px; text-align:center; font-weight:bold; padding:5px; letter-spacing:.5px;">PROGRAMA VASO DE LECHE</div>
<div style="text-align:center; font-weight:bold; font-size:15pt; margin-bottom:14px;">FICHA DE CONTROL INTERNO - INGRESO</div>

<div style="font-weight:bold; font-size:11pt; margin-bottom:10px;">PRODUCTO: <span style="text-decoration:underline;">{{ $productName }}</span></div>

<table class="row">
    <tr>
        <td style="width:150px;">FECHA DE INGRESO:</td>
        <td style="width:150px;"><span class="fill">{!! $v('date') ?: '&nbsp;' !!}</span></td>
        <td style="width:70px; text-align:right;">HORA:&nbsp;</td>
        <td><span class="fill">{!! $v('time') ?: '&nbsp;' !!}</span></td>
    </tr>
</table>

<table class="row">
    @foreach($items as $item)
        <tr>
            <td>{{ $item['label'] }}</td>
            <td class="sn">SI <span class="box">{!! $mark($item['value'], 'si') !!}</span></td>
            <td class="sn">NO <span class="box">{!! $mark($item['value'], 'no') !!}</span></td>
        </tr>
    @endforeach
</table>

<table class="row">
    <tr><td style="width:120px; vertical-align:top;">OBSERVACIONES:</td><td>@foreach($obsLines('observations') as $line)<div class="fill-line">{!! $line !== '' ? e($line) : '&nbsp;' !!}</div>@endforeach</td></tr>
</table>
<div class="rule"></div>

<table class="row">
    <tr>
        <td>FECHA DE VENCIMIENTO DEL PRODUCTO</td>
        <td style="width:250px; text-align:center;"><span class="fill">{!! $v('expiry') ?: '&nbsp;' !!}</span></td>
    </tr>
    <tr>
        <td>CANTIDAD DE PRODUCTO RECEPCIONADO</td>
        <td style="width:250px; text-align:center;"><span class="fill">{!! $v('quantity') ?: '&nbsp;' !!}</span> {{ $v('unit') }}</td>
    </tr>
    <tr>
        <td>NÚMERO DE LOTE(S) RECEPCIONADO(S)</td>
        <td style="width:250px; text-align:center;"><span class="fill">{!! $v('lots') ?: '&nbsp;' !!}</span></td>
    </tr>
</table>

<table class="row">
    <tr>
        <td>PESAJE ALEATORIO DEL PRODUCTO ADECUADO&nbsp; <span class="fill" style="text-transform:none;">{!! $v('weighing_detail') ?: '&nbsp;' !!}</span></td>
        <td class="sn">SI <span class="box">{!! $mark($weighing, 'si') !!}</span></td>
        <td class="sn">NO <span class="box">{!! $mark($weighing, 'no') !!}</span></td>
    </tr>
</table>
<table class="row">
    <tr><td style="width:120px; vertical-align:top;">OBSERVACIONES:</td><td>@foreach($obsLines('observations_end') as $line)<div class="fill-line">{!! $line !== '' ? e($line) : '&nbsp;' !!}</div>@endforeach</td></tr>
</table>
<div class="rule"></div>

<table class="row">
    @foreach($extras as $item)
        <tr>
            <td>{{ $item['label'] }}</td>
            <td class="sn">SI <span class="box">{!! $mark($item['value'], 'si') !!}</span></td>
            <td class="sn">NO <span class="box">{!! $mark($item['value'], 'no') !!}</span></td>
        </tr>
    @endforeach
</table>

<div style="margin-top:55px; width:240px; text-align:center; font-weight:bold; font-size:10pt;">
    <div style="border-top:1px solid #000; padding-top:5px;">PROGRAMA VASO DE LECHE</div>
</div>
