<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Reporte PROVALE')</title>
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
            height: 100%;
            padding: 10px;
        }
        
        tbody {
            display: table-row-group;
        }
        
        thead {
            display: table-header-group;
        }
        
        tr {
            page-break-inside: avoid;
        }
        
        .header-table {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .header-cell {
            display: table-cell;
            vertical-align: middle;
        }
        
        .logo-cell {
            width: 70px;
            text-align: center;
        }
        
        .title-cell {
            text-align: center;
            padding: 5px;
        }
        
        .title-cell h2 {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .title-cell h3 {
            font-size: 9pt;
            font-weight: bold;
        }
        
        .info-cell {
            width: 120px;
            text-align: right;
            font-size: 7pt;
            padding-right: 10px;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            border-right: none;
            border-bottom: none;
            margin-bottom: 5px;
        }
        
        .main-table th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 3px 2px;
            font-size: 6pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        
        .main-table td {
            border: 1px solid #000;
            padding: 2px;
            font-size: 6pt;
            vertical-align: middle;
        }
        
        .section-header {
            background-color: #b0b0b0;
            font-weight: bold;
            text-align: center;
            padding: 8px 4px;
            font-size: 9pt;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .subsection-header {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: center;
            padding: 3px;
            font-size: 7pt;
        }
        
        .row-number {
            width: 28px;
            text-align: center;
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-left {
            text-align: left;
        }
        
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .summary-section {
            margin-top: 5px;
            border: 1px solid #000;
            padding: 5px;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .summary-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 7pt;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .totales-table {
            page-break-inside: avoid;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 5pt;
            font-weight: bold;
        }
        
        .badge-success {
            background: #E8F5E9;
            color: #4A7C59;
        }
        
        .badge-warning {
            background: #FEF3E2;
            color: #D97706;
        }
        
        .badge-danger {
            background: #FCE8E4;
            color: #E76F51;
        }
        
        .small-text {
            font-size: 5pt;
        }
    </style>
</head>
<body>
    <footer>
        <strong>PÁG <span class="pagenum"></span></strong>
    </footer>
    <div class="page-container">
        <table class="header-table">
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
                    <div style="font-size: 11pt; font-weight: bold; margin: 0;">
                        @yield('titulo', 'REPORTE PROVALE')
                    </div>
                    <div style="font-size: 9pt; font-weight: bold; margin: 0;">
                        @yield('subtitulo', '')
                    </div>
                </td>
                <td style="width: 120px; text-align: right; vertical-align: top; font-size: 7pt; padding: 0;">
                    <div>FECHA: {{ date('d/m/Y') }}</div>
                    <div>HORA: {{ date('H:i:s') }}</div>
                </td>
            </tr>
        </table>
        
        @yield('content')
    </div>
</body>
</html>
