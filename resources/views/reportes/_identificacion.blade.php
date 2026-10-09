<table class="table meta">
    <colgroup>
        <col style="width:15%">
        <col style="width:35%">
        <col style="width:11%">
        <col style="width:9%">
        <col style="width:13%">
        <col style="width:17%">
    </colgroup>
    <tr>
        <td class="label">NOMBRE DE LA MUNICIPALIDAD:</td>
        <td class="value municipality-value">{{ $v('municipalidad') }}</td>
        <td class="label">NUM. EXPEDIENTE:</td>
        <td class="value identifier-value">{{ $v('numero_expediente') }}</td>
        <td class="label">MES REPORTADO:</td>
        <td class="value period-value">{{ $v('mes_reportado') }}</td>
    </tr>
    <tr>
        <td class="value municipality-type" rowspan="3">{{ $v('tipo_municipalidad', 'MUNICIPALIDAD DISTRITAL') }}</td>
        <td class="value geo-cell" colspan="3" rowspan="3">
            <table class="geo-table">
                <colgroup><col style="width:27%"><col style="width:31%"><col style="width:42%"></colgroup>
                <tr>
                    <td class="geo-heading" rowspan="2">UBICACION<br>GEOGRAFICA</td>
                    <td class="geo-label">DEPARTAMENTO</td>
                    <td class="geo-value">{{ $v('departamento') }}</td>
                </tr>
                <tr>
                    <td class="geo-label">PROVINCIA</td>
                    <td class="geo-value">{{ $v('provincia') }}</td>
                </tr>
            </table>
        </td>
        <td class="label">AÑO REPORTADO:</td>
        <td class="value period-value">{{ $v('anio_reportado') }}</td>
    </tr>
    <tr>
        <td class="label">FECHA DE REPORTE:</td>
        <td class="value identifier-value">{{ $v('fecha_reporte') }}</td>
    </tr>
    <tr>
        <td class="label">COD. ENVIO:</td>
        <td class="value identifier-value">{{ $v('codigo_envio') }}</td>
    </tr>
</table>
