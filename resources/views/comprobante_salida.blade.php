<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido-Comprobante de Salida</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: landscape; margin: 1.5mm 3mm 1.5mm 3mm; }
        body { margin: 0; padding: 3mm; font-family: Arial, sans-serif; font-size: 8pt; line-height: 1.2; }
        .page-container { width: 100%; padding: 6px 8px 4px; }
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tr { page-break-inside: avoid; }

        /* HEADER */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; padding: 6px; border: none; }
        .header-logo { width: 90px; text-align: center; }
        .header-logo img { width: 75px; height: auto; }
        .header-title { text-align: center; padding: 10px 14px; position: relative; left: -18px; }
        .header-title .inst { font-size: 9pt; font-weight: bold; }
        .header-title .prog { font-size: 8pt; margin: 2px 0; }
        .header-title .doc-title { font-size: 18pt; font-weight: bold; letter-spacing: 1px; margin: 3px 0; }
        .header-title .doc-sub { font-size: 10pt; font-weight: bold; margin: 2px 0; }
        .header-title .mes-ano { font-size: 8pt; margin-top: 3px; }
        .header-boxes { width: 170px; padding: 6px 4px; }
        .header-spacer { width: 12px; padding: 0 !important; border: none !important; background: transparent !important; }
        .boxes-table { width: 100%; border-collapse: collapse; }
        .boxes-table td { border: 1px solid #000; padding: 2px 3px; text-align: center; font-size: 7pt; }
        .boxes-table .lbl { background-color: #e0e0e0; font-weight: bold; font-size: 6.5pt; }
        .boxes-table .val { font-size: 9pt; font-weight: bold; height: 18px; }
        .boxes-table .num-orden { font-size: 14pt; font-weight: bold; padding: 6px; }

        /* INFO ROWS */
        .info-row { width: 50%; border: 2px solid #000; padding: 5px 8px; margin-bottom: 2px; font-size: 8pt; display: flex; align-items: center; }
        .info-row-2col { width: 51.9%; border-collapse: collapse; margin-bottom: 4px; }
        .info-row-2col td { border: 2px solid #000; padding: 5px 8px; font-size: 8pt; }
        .field-line { border-bottom: 1px solid #000; display: inline-block; min-width: 190px; padding: 0 5px; margin-left: 5px; }
        .lbl-bold { font-weight: bold; }
        .date-right { text-align: right; font-size: 9pt; margin-bottom: 8px; padding-right: 20px; }

        /* MAIN TABLE */
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .main-table th { background-color: #ffffff; border: 1px solid #000; padding: 5px 3px; font-size: 8.5pt; font-weight: bold; text-align: center; }
        .main-table td { border: 1px solid #000; padding: 4px; font-size: 8pt; text-align: center; vertical-align: middle; }
        .main-table thead tr:first-child th:not(.spacer-col) { border-top: 2px solid #000; }
        .main-table tr > *:first-child { border-left: 2px solid #000; }
        .main-table tbody tr:last-child td:not(.spacer-col) { border-bottom: 2px solid #000; }
        .main-table .sec-hdr { background-color: #ffffff; font-weight: bold; font-size: 9pt; text-align: center; padding: 6px; letter-spacing: 3px; }
        .main-table .subsec-hdr { background-color: #ffffff; font-weight: bold; font-size: 8pt; text-align: center; padding: 5px; letter-spacing: 2px; }
        .main-table .desc-col { text-align: left; padding-left: 8px; min-width: 200px; }
        .main-table .num-col { width: 35px; background-color: #f5f5f5; font-weight: bold; }
        .main-table .qty-col { width: 46px; }
        .main-table .uom-col { width: 65px; }
        .main-table .price-col { width: 72px; }
        .main-table .spacer-col {
            width: 10px;
            border-top: none !important;
            border-bottom: none !important;
            border-right: none !important;
            border-left: 1px solid #000 !important;
            background-color: #fff !important;
            padding: 0 !important;
        }
        .total-row td { background-color: #ffffff; font-weight: bold; font-size: 8.5pt; }

        /* FOOTER NOTE */
        .footer-note { border: 2px solid #000; padding: 5px; margin-bottom: 6px; font-size: 7.5pt; text-align: center; }

        /* SIGNATURES */
        .sig-table { width: 100%; border-collapse: separate; border-spacing: 16px 0; margin-top: 4px; padding-top: 20px}
        .sig-table td { border: none; padding: 0; vertical-align: top; width: 33.33%; }
        .sig-block { min-height: 96px; }
        .sig-block-head { display: table; width: 100%; margin-bottom: 6px; }
        .sig-logo { display: table-cell; width: 48px; vertical-align: top; text-align: left; }
        .sig-logo img { width: 38px; height: auto; }
        .sig-entity { display: table-cell; vertical-align: top; text-align: left; font-size: 7.5pt; line-height: 1.25; }
        .sig-entity strong { display: block; font-size: 8pt; }
        .sig-hidden { visibility: hidden; }
        .sig-space { height: 26px; }
        .sig-line { border-top: 1px solid #000; padding-top: 4px; font-size: 7.5pt; text-align: center; }
        .sig-role { text-align: center; font-size: 7pt; margin-top: 2px; font-weight: bold; }
        .sig-area { text-align: center; font-size: 7pt; margin-top: 2px; }
        .sig-dni { text-align: center; font-size: 7pt; margin-top: 3px; }
        .dni-write-line { display: inline-block; width: 110px; border-bottom: 1px solid #000; height: 10px; vertical-align: middle; margin-left: 4px; }
    </style>
</head>
<body>
    <div class="page-container">

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td style="width: 150px; text-align: left; vertical-align: middle; padding: 0;" class="header-logo">
                <img src="{{ public_path('img/muni2.png') }}" 
                style="width: 50px; height: auto; vertical-align: middle; margin-right: 5px;" 
                alt="Logo">
                    <div style="display: inline-block; vertical-align: middle; text-align: left; width: 80px;">
                    <div style="font-size: 6pt; font-weight: bold;">MUNICIPALIDAD DISTRITAL DE LA ESPERANZA</div>
                    
                    <div style="font-size: 6pt;">O.F. Vaso de Leche</div>
                    </div>
            </td>
            <td class="header-title">
                <div class="doc-title">PEDIDO-COMPROBANTE DE SALIDA</div>
                <div class="doc-sub">SUBGERENCIA DE PROGRAMAS SOCIALES</div>
                <div class="doc-sub">PROGRAMA VASO DE LECHE</div>
            </td>
            <td class="header-boxes">
                <table class="boxes-table">
                    <tr>
                        <td class="lbl">ZONA</td>
                        <td class="lbl">CÓDIGO</td>
                        <td class="lbl">BENEF</td>
                    </tr>
                    <tr>
                        <td class="val">{{ $zona ?? '' }}</td>
                        <td class="val">{{ $comite ?? '' }}</td>
                        <td class="val">{{ $num_mes ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td class="header-boxes" style="width: 120px;">
                <table class="boxes-table">
                    <tr>
                        <td class="lbl">NÚMERO</td>
                    </tr>
                    <tr>
                        <td class="num-orden">{{ $numero_orden ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td class="header-spacer">&nbsp;</td>
        </tr>
    </table>

    <div class="date-right">La Esperanza, {{ $fecha ?? '' }}</div>

    {{-- SOLICITANTE --}}
    <div class="info-row">
        <span class="lbl-bold">Se les entrega a la Sra. (Sr.):</span>
        <span class="field-line">{{ $solicitante_nombre ?? '' }}</span>
    </div>


    <table class="info-row-2col" style="margin-bottom: 12px;">
        <tr>
            <td style="width:60%;">
                <span class="lbl-bold">Con destino al club de Madres:</span>
                <span class="field-line" style="min-width:180px;">{{ $domicilio ?? '' }}</span>
                los siguientes artículos.
            </td>
        </tr>
    </table>

    {{-- TABLA PRINCIPAL --}}
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="3">ITEM</th>
                <th colspan="2" class="sec-hdr">SOLICITADO</th>
                <th colspan="2" class="sec-hdr">DESPACHADO</th>
                <th colspan="2" class="sec-hdr">VALORES</th>
                <th rowspan="3" class="spacer-col">&nbsp;</th>
            </tr>
            <tr>
                <th colspan="2" class="subsec-hdr">ARTÍCULOS</th>
                <th rowspan="2" class="qty-col">CANTIDAD<br/>DESPACHADO</th>
                <th rowspan="2" class="uom-col">UNIDAD<br/>DE MEDIDA</th>
                <th rowspan="2" class="price-col">UNITARIO<br/>S/.</th>
                <th rowspan="2" class="price-col">TOTAL<br/>S/.</th>
            </tr>
            <tr>
                <th class="qty-col">CANTIDAD</th>
                <th class="desc-col" style="text-align:center; padding-left:0;">DESCRIPCIÓN</th>
            </tr>
        </thead>
        <tbody>
            @php
                $articulos = $articulos ?? [];
                $rows_count = max(count($articulos), 14);
            @endphp
            @for ($i = 0; $i < $rows_count; $i++)
                @php $art = $articulos[$i] ?? null; @endphp
                <tr>
                    <td class="num-col">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="qty-col">{{ $art['cantidad_solicitado'] ?? '' }}</td>
                    <td class="desc-col">{{ $art['descripcion'] ?? '' }}</td>
                    <td class="qty-col">{{ $art['cantidad_despachado'] ?? '' }}</td>
                    <td class="uom-col">{{ $art['unidad'] ?? '' }}</td>
                    <td class="price-col">{{ $art['unitario'] ?? '' }}</td>
                    <td class="price-col">{{ $art['total'] ?? '' }}</td>
                    <td class="spacer-col"></td>
                </tr>
            @endfor
            <tr class="total-row">
                <td colspan="6" style="text-align:right; padding-right:10px;">TOTAL:</td>
                <td class="price-col" style="background-color: #e0e0e0;">{{ $total_general ?? '' }}</td>
                <td class="spacer-col"></td>
            </tr>
        </tbody>
    </table>

    {{-- FIRMAS --}}
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-block">
                    <div class="sig-block-head">
                        <div class="sig-logo">
                            <img src="{{ public_path('img/muni2.png') }}" alt="Logo">
                        </div>
                        <div class="sig-entity">
                            <strong>Municipalidad Distrital de La Esperanza</strong>
                        </div>
                    </div>
                    <div class="sig-space"></div>
                    <div class="sig-line"></div>
                    <div class="sig-role">{{ $encargado_almacen ?? '' }}</div>
                    <div class="sig-area">SUBGERENTE DE PROGRAMAS SOCIALES</div>
                    <div class="sig-area">PROGRAMA VASO DE LECHE</div>
                    <div class="sig-dni">DNI: {{ $dni_encargado ?? '' }}</div>
                </div>
            </td>
            <td>
                <div class="sig-block">
                    <div class="sig-block-head">
                        <div class="sig-logo">
                            <img src="{{ public_path('img/muni2.png') }}" alt="Logo">
                        </div>
                        <div class="sig-entity">
                            <strong>Municipalidad Distrital de La Esperanza</strong>
                        </div>
                    </div>
                    <div class="sig-space"></div>
                    <div class="sig-line"></div>
                    <div class="sig-role">{{ $control ?? '' }}</div>
                    <div class="sig-area">ENCARGADO DE PROVALE</div>
                    <div class="sig-dni">DNI: {{ $dni_control ?? '' }}</div>
                </div>
            </td>
            <td>
                <div class="sig-block">
                    <div class="sig-block-head">
                        <div class="sig-logo sig-hidden">
                            <img src="{{ public_path('img/muni2.png') }}" alt="Logo">
                        </div>
                        <div class="sig-entity sig-hidden">
                            <strong>Municipalidad Distrital de La Esperanza</strong>
                        </div>
                    </div>
                    <div class="sig-space"></div>
                    <div class="sig-line"></div>
                    <div class="sig-role">RECIBÍ CONFORME</div>
                    <div class="sig-dni">DNI:<span class="dni-write-line"></span></div>
                </div>
            </td>
        </tr>
    </table>
    </div>

</body>
</html>
