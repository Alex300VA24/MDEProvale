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
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #2C2420;
            line-height: 1.4;
        }
        .header {
            background: linear-gradient(135deg, #4A7C59 0%, #3d6647 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }
        .header-logo {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .header .subtitle {
            font-size: 12px;
            margin-top: 5px;
        }
        .info-box {
            background: #FDF8F3;
            border: 2px solid #F5E6D3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .info-box h2 {
            color: #4A7C59;
            font-size: 16px;
            margin-bottom: 10px;
            border-bottom: 2px solid #4A7C59;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #8B7355;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table thead {
            background: #4A7C59;
            color: white;
        }
        table th {
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        table td {
            padding: 8px 10px;
            border-bottom: 1px solid #F5E6D3;
            font-size: 11px;
        }
        table tbody tr:nth-child(even) {
            background: #FDF8F3;
        }
        table tbody tr:hover {
            background: #F5E6D3;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #8B7355;
            padding: 10px;
            border-top: 1px solid #F5E6D3;
        }
        .page-number:after {
            content: counter(page);
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
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
        .summary-box {
            background: #E8F5E9;
            border-left: 4px solid #4A7C59;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-box h3 {
            color: #4A7C59;
            margin-bottom: 10px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #4A7C59;
        }
        .summary-label {
            font-size: 10px;
            color: #8B7355;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('img/muni2.png') }}" alt="Logo" class="header-logo">
        <h1>PROVALE - MDE</h1>
        <p>Programa del Vaso de Leche</p>
        <p class="subtitle">Municipalidad Distrital de La Esperanza</p>
    </div>

    @yield('content')

    <div class="footer">
        <p>Generado el {{ date('d/m/Y H:i:s') }} | Página <span class="page-number"></span></p>
        <p>Sistema PROVALE - Municipalidad Distrital de La Esperanza</p>
    </div>
</body>
</html>
