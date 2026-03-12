<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            font-size: 9px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-active {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Fecha de generación: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    @if($tipo == 'estadistico')
        <div style="margin-bottom: 20px;">
            <h3 style="font-size: 12px; margin-bottom: 10px;">Resumen Estadístico</h3>
            <table style="width: 50%;">
                <tr>
                    <td><strong>Total de Resoluciones:</strong></td>
                    <td>{{ $resolutions->count() }}</td>
                </tr>
                <tr>
                    <td><strong>Resoluciones Vigentes:</strong></td>
                    <td>{{ $resolutions->where('date_end', '>=', date('Y-m-d'))->count() }}</td>
                </tr>
                <tr>
                    <td><strong>Resoluciones Vencidas:</strong></td>
                    <td>{{ $resolutions->where('date_end', '<', date('Y-m-d'))->count() }}</td>
                </tr>
            </table>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Documento</th>
                <th style="width: 12%;">F. Emisión</th>
                <th style="width: 12%;">F. Inicio</th>
                <th style="width: 12%;">F. Fin</th>
                <th style="width: 10%;">Estado</th>
                <th style="width: 24%;">Comités Asociados</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resolutions as $index => $resolution)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $resolution->document }}</td>
                    <td>{{ \Carbon\Carbon::parse($resolution->date_document)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($resolution->date_start)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($resolution->date_end)->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge {{ $resolution->state->abbreviation == 'A' ? 'badge-active' : 'badge-inactive' }}">
                            {{ $resolution->state->title ?? 'N/A' }}
                        </span>
                    </td>
                    <td>
                        @if($resolution->associations->count() > 0)
                            @foreach($resolution->associations as $assoc)
                                • {{ $assoc->name }}<br>
                            @endforeach
                        @else
                            <span style="color: #999;">Sin comités</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                        No hay resoluciones para mostrar
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Sistema PROVALE - Programa de Vaso de Leche</p>
        <p>Reporte generado automáticamente</p>
    </div>
</body>
</html>
