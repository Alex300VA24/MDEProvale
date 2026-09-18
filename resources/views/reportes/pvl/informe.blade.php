<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe sustentatorio PVL {{ $data['periodo'] }}</title>
    <style>
        @page { margin: 18mm 18mm 23mm 25mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #17253a;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.42;
        }
        .header {
            position: static;
            height: 19mm;
            border-bottom: 1.4pt solid #0b5f45;
            margin-bottom: 8mm;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { border: 0; vertical-align: middle; padding: 0; }
        .seal {
            width: 13mm;
            height: 13mm;
            border: 1.4pt solid #0b5f45;
            border-radius: 50%;
            color: #0b5f45;
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            line-height: 12mm;
        }
        .municipality { padding-left: 3mm !important; }
        .municipality strong { color: #0b5f45; font-size: 10pt; letter-spacing: .2pt; }
        .municipality span { display: block; color: #536578; font-size: 7.5pt; margin-top: 1mm; }
        .system-label { color: #0b5f45; font-size: 8pt; font-weight: bold; text-align: right; }
        .footer {
            position: fixed;
            bottom: -16mm;
            left: 0;
            right: 0;
            height: 11mm;
            border-top: .6pt solid #b7c5cf;
            color: #637486;
            font-size: 7pt;
            padding-top: 2mm;
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td { border: 0; padding: 0; }
        .footer .right { text-align: right; }
        .page-number:after { content: counter(page); }
        h1 {
            margin: 0 0 7mm;
            text-align: center;
            font-size: 12pt;
            text-decoration: underline;
            letter-spacing: .2pt;
        }
        h2 {
            margin: 6mm 0 2.5mm;
            padding-bottom: 1.3mm;
            border-bottom: .7pt solid #9db4c4;
            color: #173f61;
            font-size: 10pt;
            text-transform: uppercase;
            page-break-after: avoid;
        }
        p { margin: 0 0 3.2mm; text-align: justify; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 6mm; }
        .meta td { border: 0; padding: 1.2mm 0; vertical-align: top; }
        .meta .label { width: 28mm; font-weight: bold; }
        .meta .colon { width: 5mm; text-align: center; }
        .rule { border-top: 1pt solid #6f8190; margin: 2mm 0 6mm; }
        .status {
            border: 1pt solid {{ $data['envio_acreditado'] ? '#17785a' : '#b87908' }};
            background: {{ $data['envio_acreditado'] ? '#e9f7f1' : '#fff7e5' }};
            color: {{ $data['envio_acreditado'] ? '#105f47' : '#865600' }};
            font-weight: bold;
            padding: 2.5mm 3mm;
            margin: 3mm 0 5mm;
            text-align: center;
        }
        table.data { width: 100%; border-collapse: collapse; margin: 2.5mm 0 4mm; page-break-inside: auto; }
        table.data tr { page-break-inside: avoid; page-break-after: auto; }
        table.data th {
            background: #173f61;
            border: .6pt solid #173f61;
            color: #fff;
            font-size: 8pt;
            padding: 2mm 1.8mm;
            text-align: left;
        }
        table.data td { border: .6pt solid #aebdc8; padding: 2mm 1.8mm; vertical-align: top; }
        table.data .number { text-align: right; white-space: nowrap; }
        .summary td:nth-child(odd) { background: #edf3f7; color: #324b61; font-weight: bold; width: 23%; }
        .summary td:nth-child(even) { width: 27%; }
        .evidence-status { font-size: 7.3pt; font-weight: bold; }
        .supported { color: #105f47; }
        .pending { color: #915f00; }
        .references { color: #52687a; font-size: 7.6pt; margin-top: 1mm; }
        ol, ul { margin: 2mm 0 4mm 5mm; padding-left: 5mm; }
        li { margin-bottom: 1.8mm; text-align: justify; }
        .source-index { width: 8mm; text-align: center; }
        .source-origin { width: 17mm; }
        .source-field { width: 45mm; overflow-wrap: break-word; }
        .signature { margin-top: 15mm; width: 72mm; margin-left: auto; text-align: center; page-break-inside: avoid; }
        .signature-line { border-top: .8pt solid #27394a; padding-top: 2mm; }
        .signature strong, .signature span { display: block; }
        .small-note { color: #627486; font-size: 7.5pt; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 15mm;"><div class="seal">MDE</div></td>
                <td class="municipality">
                    <strong>{{ $data['municipalidad'] }}</strong>
                    <span>Programa del Vaso de Leche</span>
                </td>
                <td class="system-label">PROVALE<br>INFORME TRAZABLE</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td>Generado con datos validados y fuentes registradas en PROVALE. Requiere revisión y firma del responsable.</td>
                <td class="right">Página <span class="page-number"></span></td>
            </tr>
        </table>
    </div>

    <h1>INFORME N.° {{ $data['numero_informe'] ?: '________________________' }}</h1>

    <table class="meta">
        <tr><td class="label">A</td><td class="colon">:</td><td><strong>{{ $data['destinatario_nombre'] ?: '________________________________________' }}</strong><br>{{ $data['destinatario_cargo'] ?: 'Cargo pendiente de consignar' }}</td></tr>
        <tr><td class="label">DE</td><td class="colon">:</td><td><strong>{{ $data['remitente_nombre'] ?: '________________________________________' }}</strong><br>{{ $data['remitente_cargo'] }}</td></tr>
        <tr><td class="label">ASUNTO</td><td class="colon">:</td><td><strong>{{ mb_strtoupper($data['asunto']) }}</strong></td></tr>
        <tr><td class="label">PERIODO</td><td class="colon">:</td><td>{{ $data['periodo_texto'] }} · {{ $data['trimestre'] }}</td></tr>
        <tr><td class="label">FECHA</td><td class="colon">:</td><td>{{ $data['lugar'] }}, {{ $data['fecha'] }}</td></tr>
    </table>
    <div class="rule"></div>

    <p>Por medio del presente, se informa el resultado de la revisión de la información utilizada para preparar los reportes oficiales del Programa del Vaso de Leche correspondientes al periodo <strong>{{ $data['periodo_texto'] }}</strong>. El sustento integra registros de la base de datos, documentos indexados, cálculos determinísticos y trazabilidad por campo.</p>

    <div class="status">{{ $data['estado_acreditacion'] }}</div>

    <h2>1. Resumen verificable del periodo</h2>
    <table class="data summary">
        <tr>
            <td>Compras / ingresos</td><td class="number">{{ number_format((int) $data['resumen']['compras_registradas']) }}</td>
            <td>Total de gastos</td><td class="number">{{ $data['resumen']['total_gastos'] === null ? 'Sin dato confirmado' : 'S/ '.number_format((float) $data['resumen']['total_gastos'], 2) }}</td>
        </tr>
        <tr>
            <td>Total de recursos</td><td class="number">{{ $data['resumen']['total_recursos'] === null ? 'Sin dato confirmado' : 'S/ '.number_format((float) $data['resumen']['total_recursos'], 2) }}</td>
            <td>Saldo final</td><td class="number">{{ $data['resumen']['saldo_final'] === null ? 'Sin dato confirmado' : 'S/ '.number_format((float) $data['resumen']['saldo_final'], 2) }}</td>
        </tr>
        <tr>
            <td>Distribuciones</td><td class="number">{{ number_format((int) $data['resumen']['distribuciones']) }}</td>
            <td>Comités atendidos</td><td class="number">{{ number_format((int) ($data['resumen']['comites_atendidos'] ?? 0)) }}</td>
        </tr>
        <tr>
            <td>Beneficiarios</td><td class="number">{{ number_format((int) $data['resumen']['beneficiarios']) }}</td>
            <td>Certificados</td><td class="number">{{ number_format((int) $data['resumen']['certificados']) }}</td>
        </tr>
        <tr>
            <td>Fuentes trazables</td><td class="number">{{ number_format((int) $data['resumen']['fuentes']) }}</td>
            <td>Documentos indexados</td><td class="number">{{ number_format((int) $data['resumen']['documentos']) }}</td>
        </tr>
    </table>

    <h2>2. Matriz de acreditación</h2>
    <table class="data">
        <thead><tr><th style="width: 28%;">Materia verificada</th><th style="width: 18%;">Estado</th><th>Resultado y respaldo</th></tr></thead>
        <tbody>
        @foreach($data['evidencias'] as $evidencia)
            <tr>
                <td><strong>{{ $evidencia['control'] }}</strong></td>
                <td class="evidence-status {{ $evidencia['estado'] === 'ACREDITADO' ? 'supported' : 'pending' }}">{{ $evidencia['estado'] }}</td>
                <td>
                    {{ $evidencia['resultado'] }}
                    <div class="references"><strong>Referencias:</strong> {{ implode('; ', $evidencia['referencias']) }}</div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>3. Advertencias administrativas</h2>
    @if(count($data['advertencias']))
        <ul>
            @foreach($data['advertencias'] as $advertencia)
                <li><strong>{{ $advertencia['field'] ?? 'Dato revisado' }}:</strong> {{ $advertencia['message'] ?? 'Advertencia registrada.' }}</li>
            @endforeach
        </ul>
    @else
        <p>No se registraron advertencias pendientes en la validación del periodo.</p>
    @endif

    <h2>4. Fuentes utilizadas</h2>
    <table class="data">
        <thead><tr><th class="source-index">N.°</th><th class="source-origin">Origen</th><th class="source-field">Campo o conjunto</th><th>Referencia verificable</th></tr></thead>
        <tbody>
        @forelse($data['fuentes'] as $index => $fuente)
            <tr><td class="source-index">{{ $index + 1 }}</td><td>{{ $fuente['origen'] }}</td><td>{{ $fuente['campo'] }}</td><td>{{ $fuente['referencia'] }}</td></tr>
        @empty
            <tr><td colspan="4">No se registraron fuentes trazables. El informe no debe utilizarse como acreditación.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>5. Conclusiones</h2>
    <ol>
        @foreach($data['conclusiones'] as $conclusion)
            <li>{{ $conclusion }}</li>
        @endforeach
    </ol>

    <h2>6. Anexos</h2>
    <ol>
        @foreach($data['anexos'] as $anexo)
            <li>{{ $anexo }}</li>
        @endforeach
        @foreach($data['documentos'] as $documento)
            <li>{{ $documento['archivo'] }} — {{ str_replace('_', ' ', mb_strtoupper($documento['tipo_documento'])) }}.</li>
        @endforeach
    </ol>

    <p>Es todo cuanto informo para conocimiento, revisión y fines correspondientes.</p>

    <div class="signature">
        <div class="signature-line">
            <strong>{{ $data['remitente_nombre'] ?: 'Firma del responsable' }}</strong>
            <span>{{ $data['remitente_cargo'] }}</span>
        </div>
    </div>

    <p class="small-note">Nota de control: este documento no declara una firma digital ni acredita por sí solo la remisión a Contraloría. La acreditación de envío depende de la constancia y del código registrados en las fuentes del periodo.</p>
</body>
</html>
