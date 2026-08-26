<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: landscape;
            margin: 1.5mm 3mm 1.5mm 3mm;
        }

        body {
            margin: 0;
            padding: 3mm;
            font-family: Arial, sans-serif;
        }

        footer {
            position: fixed;
            bottom: -4px;
            left: 0px;
            right: 0px;
            height: 20px;
            text-align: center;
            font-size: 8pt;
            font-family: Arial, sans-serif;
        }

        .pagenum:before {
            content: counter(page);
        }

        .page-container {
            width: 100%;
            padding: 10px;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .header-table {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .section-block {
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            background-color: #d8d8d8;
            border: 1px solid #000;
            padding: 4px 6px;
            margin-bottom: 3px;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 5px;
        }

        .main-table th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .main-table td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 7pt;
            vertical-align: middle;
        }

        .row-number {
            width: 30px;
            text-align: center;
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .group-header {
            background-color: #b0b0b0;
            font-weight: bold;
            text-align: left;
            padding: 5px;
            font-size: 8pt;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .filtros-line {
            font-size: 7pt;
            text-align: left;
            margin-bottom: 4px;
            color: #333;
        }

        .totales-line {
            margin-top: 2px;
            text-align: right;
            font-size: 8pt;
            font-weight: bold;
        }

        .resumen-table {
            width: 55%;
        }

        .resumen-table td {
            font-size: 8pt;
        }

        .resumen-total-row td {
            font-weight: bold;
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <footer>
        <strong>PÁG <span class="pagenum"></span></strong>
    </footer>
    <div class="page-container">
        <table class="header-table" style="border-collapse: collapse;">
            <tr>
                <td style="width: 150px; text-align: left; vertical-align: middle; padding: 0;">
                    <img src="{{ public_path('img/muni2.png') }}"
                        style="width: 50px; height: auto; vertical-align: middle; margin-right: 5px;"
                        alt="Logo">
                    <div style="display: inline-block; vertical-align: middle; text-align: left; width: 80px;">
                        <div style="font-size: 6pt; font-weight: bold;">MUNICIPALIDAD DISTRITAL</div>
                        <div style="font-size: 6pt; font-weight: bold;">DE LA ESPERANZA</div>
                        <div style="font-size: 6pt;">O.F. Vaso de Leche</div>
                    </div>
                </td>
                <td style="text-align: center; vertical-align: middle; line-height: 1.2; padding: 0; width: 60%;">
                    <div style="font-size: 11pt; font-weight: bold;">{{ $titulo }}</div>
                </td>
                <td style="width: 120px; text-align: right; vertical-align: top; font-size: 7pt; padding: 0;">
                    <div>FECHA: {{ $fecha }}</div>
                    <div>HORA: {{ $hora }}</div>
                </td>
            </tr>
        </table>

        @foreach($secciones as $i => $seccion)
            <div class="section-block" @if($i > 0) style="page-break-before: always;" @endif>
                <div class="section-title">{{ $seccion['titulo'] }}</div>
                @if(!empty($seccion['filtros_aplicados']))
                    <div class="filtros-line">Filtros: {{ implode('  |  ', $seccion['filtros_aplicados']) }}</div>
                @endif
                <table class="main-table">
                    <thead>
                        <tr>
                            <th class="row-number">N°</th>
                            @foreach($seccion['columnas'] as $col)
                                <th>{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php $n = 1; $currentGroup = null; @endphp
                        @forelse($seccion['rows'] as $row)
                            @if($seccion['group_by'] && ($row[$seccion['group_by']] ?? '') !== $currentGroup)
                                @php $currentGroup = $row[$seccion['group_by']] ?? ''; @endphp
                                <tr>
                                    <td colspan="{{ count($seccion['columnas']) + 1 }}" class="group-header">
                                        {{ $seccion['group_label'] }}: {{ $currentGroup !== '' ? $currentGroup : 'Sin especificar' }}
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td class="row-number">{{ $n }}</td>
                                @foreach($seccion['columnas'] as $col)
                                    <td>{{ $row[$col['key']] ?? '' }}</td>
                                @endforeach
                            </tr>
                            @php $n++; @endphp
                        @empty
                            <tr>
                                <td colspan="{{ count($seccion['columnas']) + 1 }}" style="text-align:center; padding: 10px;">
                                    No se encontraron registros para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="totales-line">Total {{ $seccion['titulo'] }}: {{ count($seccion['rows']) }}</div>
            </div>
        @endforeach

        <div class="section-block" style="page-break-before: always;">
            <div class="section-title">RESUMEN GENERAL DEL REPORTE</div>
            <table class="main-table resumen-table">
                <thead>
                    <tr>
                        <th>Entidad</th>
                        <th>Total de Registros</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $r)
                        <tr>
                            <td>{{ $r['label'] }}</td>
                            <td style="text-align:center;">{{ $r['total'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="resumen-total-row">
                        <td>TOTAL GENERAL</td>
                        <td style="text-align:center;">{{ $total_general }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
