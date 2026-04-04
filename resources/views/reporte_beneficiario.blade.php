<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Padrón de Beneficiarios - Club de Madres</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: landscape; margin: 1.5mm 3mm 1.5mm 3mm; }
        body { margin: 0; padding: 1mm; font-family: Arial, sans-serif; }
        footer { position: fixed; bottom: -4px; left: 0px; right: 0px; height: 20px; text-align: center; font-size: 8pt; }
        .pagenum:before { content: counter(page); }
        .page-container { width: 100%; height: 100%; padding: 0; }
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tr { page-break-inside: avoid; }
        .header-table { display: table; width: 100%; margin-bottom: 8px; }
        .header-info { display: inline-block; text-align: left; }
        .header-info-line { font-size: 7pt; margin: 2px 0; white-space: nowrap; }
        .header-info-label { display: inline-block; width: 62px; text-align: right; font-weight: bold; }
        .header-info-value { display: inline-block; min-width: 160px; text-align: left; padding-left: 4px; }
        .club-title { font-family: Georgia, "Times New Roman", serif; font-size: 20pt; font-weight: bold; letter-spacing: 0.6px; margin: 3px 0 4px; }
        .header-datetime { font-size: 7pt; line-height: 1.5; text-align: left; }
        .legend-row { background-color: #f0f0f0; font-size: 5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; word-spacing: 2px; line-height: 1.5; }
        .legend-block { display: inline-block; margin-right: 18px; }
        .main-table { width: 100%; min-width: 100%; border-collapse: collapse; border: 2px solid #000; border-right: none; border-bottom: none; margin-bottom: 5px; }
        .main-table th { background-color: #e0e0e0; border: 1px solid #000; padding: 2px; font-size: 5.5pt; font-weight: bold; text-align: center; }
        .main-table td { border: 1px solid #000; padding: 1px; font-size: 5.5pt; vertical-align: middle; }
        .row-number { width: 20px; text-align: center; background-color: #f5f5f5; font-weight: bold; }
        .text-center { text-align: center; }
        .text-left { text-align: left; padding-left: 2px; }
        .boxes-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .boxes-table td { border: 1px solid #000; padding: 2px; text-align: center; font-size: 6pt; }
        .boxes-table .lbl { background-color: #e0e0e0; font-weight: bold; }
        .boxes-table .val { font-size: 9pt; font-weight: bold; }
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .sig-table td { width: 33.33%; text-align: center; padding: 4px; }
        .sig-line { border-top: 1px solid #000; margin-top: 30px; padding-top: 3px; font-size: 7pt; }
        .resumen-title { font-weight: bold; text-align: center; background-color: #d8d8d8; border: 1px solid #000; padding: 2px; font-size: 6pt; margin-bottom: 2px; }
        .summary-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .summary-table td { border: 1px solid #000; padding: 2px; text-align: center; font-size: 6pt; }
    </style>
</head>
<body>
    <footer><strong>PÁG <span class="pagenum"></span></strong></footer>
    <div class="page-container">
        <table class="main-table">
            <thead>
                <tr>
                    <td colspan="14" style="border: none; padding: 0;">
                        <table class="header-table" style="width: 100%; border-collapse: collapse; border-spacing: 0;">
                            <tr>
                                <td style="width: 140px; text-align: left; vertical-align: middle;">
                                    <img src="{{ public_path('img/muni2.png') }}" style="width: 50px; height: auto; vertical-align: middle; margin-right: 5px;" alt="Logo">
                                    <div style="display: inline-block; vertical-align: middle; text-align: left; width: 80px;">
                                        <div style="font-size: 6pt; font-weight: bold;">MUNICIPALIDAD DISTRITAL</div>
                                        <div style="font-size: 6pt; font-weight: bold;">DE LA ESPERANZA</div>
                                        <div style="font-size: 6pt;">O.F. Vaso de Leche</div>
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: middle; padding: 0; width: 60%;">
                                    <div style="font-size: 11pt; font-weight: bold;">PADRÓN DE BENEFICIARIOS DEL CLUB DE MADRES DEL PVL (PERÍODO {{ $periodo ?? date('Y') }})</div>
                                    <div class="club-title">{{ strtoupper($club_nombre ?? '') }}</div>
                                    <div class="header-info">
                                        <div class="header-info-line">
                                            <span class="header-info-label">DIRECCIÓN:</span><span class="header-info-value">{{ $direccion ?? '' }}</span>
                                            <span class="header-info-label">CC.PP.:</span><span class="header-info-value">{{ $ccpp ?? '' }}</span>
                                            <span class="header-info-label">PRESIDENTA:</span><span class="header-info-value">{{ strtoupper($presidenta ?? '') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td style="width: 120px; text-align: right; vertical-align: top; font-size: 7pt;">
                                    <table class="boxes-table">
                                        <tr><td class="lbl">ZONA</td><td class="lbl">COMITÉ</td><td class="lbl">BENEF.</td></tr>
                                        <tr><td class="val">{{ $zona ?? '' }}</td><td class="val">{{ $comite ?? '' }}</td><td class="val">{{ $total_beneficiarios ?? 0 }}</td></tr>
                                    </table>
    
                                </td>
                                <td style="width: 85px; vertical-align: top; padding-top: 5px;">
                                    <div class="header-datetime">FECHA: {{ $fecha ?? date('d/m/Y') }}</div>
                                    <div class="header-datetime">HORA: {{ $hora ?? date('H:i:s') }}</div>
                                </td>
                            </tr><!--  -->
                        </table>
                    </td>
                </tr>
                <tr>
                    <th style="width: 15px;" rowspan="3">Nº</th>
                    <th colspan="3" style="background-color: #d0d0d0;">DATOS DE LA SOCIA</th>
                    <th colspan="10" style="background-color: #d0d0d0;">DATOS DEL BENEFICIARIO</th>
                    <th rowspan="3" style="width: 2px; border-top: none; border-bottom: none; border-right: none; border-left: 1px solid #000; background-color: white;">&nbsp;</th>
                </tr>
                <tr>
                    <th style="text-align: left; padding-left: 2px; width: 125px;" rowspan="2" >APELLIDOS Y NOMBRES</th>
                    <th style="text-align: left; padding-left: 2px; width: 90px;" rowspan="2">DIRECCIÓN</th>
                    <th style="width: 35px;" rowspan="2">DNI</th>
                    <th style="text-align: left; padding-left: 2px; width: 125px;" rowspan="2">APELLIDOS Y NOMBRES</th>
                    <th style="width: 35px;" rowspan="2">DNI</th>
                    <th style="width: 15px;" rowspan="2">BAJA</th>
                    <th style="width: 15px;" rowspan="2">TIPO</th>
                    <th style="width: 30px;" rowspan="2">FECHA NACIM.</th>
                    <th style="width: 10px;" rowspan="2">SEXO</th>
                    <th colspan="3">EDAD</th>
                    <th style="width: 30px;" rowspan="2">PARENTESCO</th>
                </tr>

                <tr>
                    <th style="width: 12px;">A</th>
                    <th style="width: 12px;">M</th>
                    <th style="width: 12px;">D</th>
                </tr>

            </thead>
            <tbody>
                @php $row = 1; @endphp
                @foreach($beneficiarios ?? [] as $grupo)
                    @foreach($grupo['items'] ?? [] as $index => $b)
                    <tr>
                        @if($index === 0)
                            <td class="row-number" rowspan="{{ $grupo['rowspan'] ?? 1 }}">{{ str_pad($row++, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="text-left" rowspan="{{ $grupo['rowspan'] ?? 1 }}">{{ $grupo['socia_nombre'] ?? '' }}</td>
                            <td class="text-left" rowspan="{{ $grupo['rowspan'] ?? 1 }}">{{ $grupo['socia_direccion'] ?? '' }}</td>
                            <td class="text-center" rowspan="{{ $grupo['rowspan'] ?? 1 }}">{{ $grupo['socia_dni'] ?? '' }}</td>
                        @endif
                        <td class="text-left">{{ $b['beneficiario_nombre'] ?? '' }}</td>
                        <td class="text-center">{{ $b['beneficiario_dni'] ?? '' }}</td>
                        <td class="text-center">{{ $b['beneficiario_baja'] ?? '' }}</td>
                        <td class="text-center">{{ $b['beneficiario_tipo'] ?? '' }}</td>
                        <td class="text-center">{{ $b['beneficiario_fecha_nacimiento'] ?? '' }}</td>
                        <td class="text-center">{{ $b['beneficiario_sexo'] ?? '' }}</td>
                        <td class="text-center">{{ $b['beneficiario_edad_anos'] ?? '' }}</td>
                        <td class="text-center">{{ $b['beneficiario_edad_meses'] ?? '' }}</td>
                        <td class="text-center">{{ $b['beneficiario_edad_dias'] ?? '' }}</td>
                        <td class="text-center">{{ $b['beneficiario_parentesco'] ?? '' }}</td>
                        <td style="width: 2px; border-top: none; border-bottom: none; border-right: none; border-left: 1px solid #000; background-color: white;">&nbsp;</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        @php
            $resumen = $resumen ?? [];
            $resumenFilas = $resumen_filas ?? [];
            $observaciones = $observaciones ?? [
                ['codigo' => '-', 'descripcion' => 'EDAD >= 14 años (BAJA)', 'cantidad' => 0],
                ['codigo' => '-', 'descripcion' => 'ANCIANO < DE 60 AÑOS', 'cantidad' => 0],
                ['codigo' => '-', 'descripcion' => 'GES / LAC <= DE 12 AÑOS', 'cantidad' => 0],
                ['codigo' => '-', 'descripcion' => 'FEC. NAC EN BLANCO', 'cantidad' => 0],
                ['codigo' => '-', 'descripcion' => 'GES. MAS DE 9 MESES / SIN FECHA DE INGRESO (BAJA)', 'cantidad' => 0],
                ['codigo' => '-', 'descripcion' => 'LAC. MAS DE UN AÑO / SIN FECHA INGRESO (BAJA)', 'cantidad' => 0],
                ['codigo' => '-', 'descripcion' => 'BENEFICIARIO DUPLICADO (NOMBRE)', 'cantidad' => 0],
                ['codigo' => '-', 'descripcion' => 'NO TIENE DNI', 'cantidad' => 0],
                ['codigo' => '-', 'descripcion' => 'NRO DE DNI DUPLICADO', 'cantidad' => 0],
            ];
        @endphp

        <table style="width: 99.5%; border-collapse: collapse; margin-top: 12px;">
            <tr>
                <td valign="top" style="width: 78%; padding-right: 8px;">
                    <table class="summary-table" style="width: 100%;">
                        <tr>
                            <td colspan="17" style="background-color: #d8d8d8; font-weight: bold;">RESUMEN DE BENEFICIARIOS POR PRIORIDAD</td>
                        </tr>
                        <tr>
                            <td rowspan="3" style="background-color: #d8d8d8; font-weight: bold; vertical-align: middle;">DETALLE</td>
                            <td colspan="10" style="background-color: #d8d8d8; font-weight: bold;">1RA. PRIORIDAD</td>
                            <td colspan="4" style="background-color: #d8d8d8; font-weight: bold;">2DA. PRIORIDAD</td>
                            <td rowspan="3" style="background-color: #d8d8d8; font-weight: bold; vertical-align: middle;">DAR DE BAJA<br/></td>
                            <td rowspan="3" style="background-color: #d8d8d8; font-weight: bold; vertical-align: middle;">TOTAL BENEFIC.</td>
                        </tr>
                        <tr>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;" colspan="8">NIÑOS</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;" colspan="2">MADRES</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;" rowspan="2">NIÑOS<br/> 7-13 AÑOS</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;" rowspan="2">ANCIANOS</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;" rowspan="2">TEBECIANOS.</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;" rowspan="2">DISCAPACIT.</td>
                        </tr>
                        <tr>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">0 AÑOS</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">1 AÑO</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">2 AÑOS</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">3 AÑOS</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">4 AÑOS</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">5 AÑOS</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">6 AÑOS</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">TOTAL</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">GESTANTES</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt;">LACTANTES</td>
                        </tr>
                        @foreach($resumenFilas as $fila)
                        <tr>
                            <td style="background-color: #f0f0f0; font-weight: bold;">{{ $fila['label'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['0_anos'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['1_ano'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['2_anos'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['3_anos'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['4_anos'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['5_anos'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['6_anos'] ?? '' }}</td>
                            <td style="background-color: #f0f0f0; font-weight: bold;">{{ $fila['data']['total'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['madres_gestantes'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['madres_lactantes'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['ninos_7_13'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['ancianos'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['tuberculosos'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['discapacitados'] ?? '' }}</td>
                            <td style="font-weight: bold;">{{ $fila['data']['gap'] ?? '' }}</td>
                            <td style="background-color: #f0f0f0; font-weight: bold; font-size: 8pt;">{{ $fila['data']['total_general'] ?? '' }}</td>
                        </tr>
                        @endforeach
                    </table>
                </td>
                <td valign="top" style="width: 18%; padding-left: 5px;">
                    @if(!empty($observaciones))
                    <table class="summary-table" style="width: 100%;">
                        <tr>
                            <td colspan="3" style="background-color: #d8d8d8; font-weight: bold; text-align: center;">RESUMEN DE OBSERVACIONES</td>
                        </tr>
                        <tr>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt; width: 20px;">CÓDIGO</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt; text-align: left; padding-left: 3px;">DESCRIPCIÓN</td>
                            <td style="background-color: #e8e8e8; font-weight: bold; font-size: 5pt; width: 20px;">CANT.</td>
                        </tr>
                        @foreach($observaciones as $obs)
                        <tr>
                            <td style="font-weight: bold; text-align: center; font-size: 5pt;">{{ $obs['codigo'] }}</td>
                            <td style="text-align: left; padding-left: 3px; font-size: 5pt;">{{ $obs['descripcion'] }}</td>
                            <td style="font-weight: bold; text-align: center; font-size: 5pt;">{{ $obs['cantidad'] }}</td>
                        </tr>
                        @endforeach
                    </table>
                    @endif
                </td>
                <td style="width: 2px; border: none; padding: 0;">&nbsp;</td>
            </tr>
        </table>
    </div>
</body>
</html>
