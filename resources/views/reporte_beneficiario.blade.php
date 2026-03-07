<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Padrón de Beneficiarios - Club de Madres PVL</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: landscape;
            margin: 10mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.1;
            padding: 5px;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        
        .header-row {
            display: table-row;
        }
        
        .header-cell {
            display: table-cell;
            vertical-align: middle;
            padding: 3px;
        }
        
        .logo-cell {
            width: 70px;
            text-align: center;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            border: 1px solid #000;
            display: inline-block;
        }
        
        .title-cell {
            text-align: center;
            padding: 5px;
        }
        
        .municipalidad-info {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .programa-info {
            font-size: 7pt;
            margin-bottom: 3px;
        }
        
        .main-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .club-name {
            font-size: 11pt;
            font-weight: bold;
            margin: 3px 0;
        }
        
        .club-details {
            font-size: 7pt;
            margin-top: 2px;
        }
        
        .info-boxes {
            width: 200px;
            text-align: center;
        }
        
        .info-box {
            display: inline-block;
            border: 2px solid #000;
            padding: 3px 8px;
            margin: 0 2px;
            font-weight: bold;
            font-size: 7pt;
        }
        
        .info-box-label {
            font-size: 6pt;
            display: block;
        }
        
        .info-box-value {
            font-size: 11pt;
            display: block;
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
            padding: 3px;
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
        
        .section-title {
            background-color: #d0d0d0;
            font-weight: bold;
            text-align: center;
            font-size: 7pt;
            padding: 3px;
        }
        
        .row-number {
            width: 25px;
            text-align: center;
            font-weight: bold;
            background-color: #f5f5f5;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-left {
            text-align: left;
            padding-left: 3px;
        }
        
        .text-right {
            text-align: right;
            padding-right: 3px;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        
        .summary-table td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 7pt;
            text-align: center;
        }
        
        .summary-title {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 7pt;
            text-align: center;
            padding: 3px;
        }
        
        .priority-section {
            margin-top: 5px;
        }
        
        .priority-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .priority-table td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 6pt;
        }
        
        .observations-box {
            border: 2px solid #000;
            padding: 5px;
            margin-top: 5px;
            min-height: 40px;
            font-size: 7pt;
        }
        
        .observations-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <table style="width: 100%; margin-bottom: 5px;">
        <tr>
            <td style="width: 70px; text-align: center; vertical-align: middle;">
                <div style="width: 60px; height: 60px; border: 1px solid #000; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 7pt;">
                    LOGO
                </div>
                <div style="font-size: 6pt; margin-top: 2px; font-weight: bold;">
                    PROGRAMA VASO DE LECHE
                </div>
            </td>
            
            <td style="text-align: center; padding: 5px; vertical-align: middle;">
                <div style="font-size: 8pt; font-weight: bold;">MUNICIPALIDAD</div>
                <div style="font-size: 8pt; font-weight: bold;">DISTRITAL DE LA ESPERANZA</div>
                <div style="font-size: 7pt; margin: 2px 0;">PROGRAMA VASO DE LECHE</div>
                <div style="font-size: 10pt; font-weight: bold; margin: 5px 0;">
                    PADRÓN DE BENEFICIARIOS DEL CLUB DE MADRES DEL PVL (PERÍODO {{ $periodo ?? '2025-I' }})
                </div>
                <div style="font-size: 11pt; font-weight: bold; margin: 3px 0;">
                    {{ $club_nombre ?? 'SANTA RITA DE CASSIA' }}
                </div>
                <div style="font-size: 7pt;">
                    <strong>DIRECCIÓN:</strong> {{ $direccion ?? 'Mz.7 Lt.12' }}
                    <span style="margin: 0 10px;"><strong>CC.PP.:</strong> {{ $ccpp ?? 'Nuevo Horizonte' }}</span>
                </div>
                <div style="font-size: 7pt;">
                    <strong>PRESIDENTA:</strong> {{ $presidenta ?? 'MARIA NELLY RODRIGUEZ LOYOLA' }}
                </div>
            </td>
            
            <td style="width: 150px; text-align: center; vertical-align: middle;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: 2px solid #000; padding: 3px;">
                            <div style="font-size: 6pt; font-weight: bold;">ZONA</div>
                            <div style="font-size: 12pt; font-weight: bold;">{{ $zona ?? '01' }}</div>
                        </td>
                        <td style="border: 2px solid #000; padding: 3px;">
                            <div style="font-size: 6pt; font-weight: bold;">COMITÉ</div>
                            <div style="font-size: 12pt; font-weight: bold;">{{ $comite ?? '005' }}</div>
                        </td>
                        <td style="border: 2px solid #000; padding: 3px;">
                            <div style="font-size: 6pt; font-weight: bold;">Nº MES</div>
                            <div style="font-size: 12pt; font-weight: bold;">{{ $num_mes ?? '48' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TABLA PRINCIPAL -->
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="3" style="width: 25px;">Nº</th>
                <th rowspan="3" style="width: 35px;">FECHA<br/>INGRESO</th>
                <th colspan="3" class="section-title">Datos de la Socia</th>
                <th colspan="8" class="section-title">Datos del Beneficiario</th>
                <th rowspan="3" style="width: 40px;">BAJA</th>
                <th rowspan="3" style="width: 35px;">EDAD<br/>AÑOS</th>
                <th rowspan="3" style="width: 35px;">EDAD<br/>MESES</th>
                <th rowspan="3" style="width: 45px;">A - M - D</th>
                <th rowspan="3" style="width: 50px;">AÑO DE INGR.</th>
                <th rowspan="3" style="width: 60px;">FEC. NACIM.</th>
                <th rowspan="3" style="width: 60px;">FEC. TERMINO</th>
                <th rowspan="3" style="width: 30px;">OBSERV.</th>
            </tr>
            <tr>
                <th rowspan="2" style="width: 150px;">APELLIDOS Y NOMBRES</th>
                <th rowspan="2" style="width: 80px;">DIRECCIÓN</th>
                <th rowspan="2" style="width: 50px;">D.N.I.</th>
                <th rowspan="2" style="width: 150px;">APELLIDOS Y NOMBRES</th>
                <th rowspan="2" style="width: 50px;">D.N.I.</th>
                <th rowspan="2" style="width: 40px;">BAJA</th>
                <th rowspan="2" style="width: 35px;">EDAD<br/>AÑOS</th>
                <th rowspan="2" style="width: 35px;">EDAD<br/>MESES</th>
                <th rowspan="2" style="width: 40px;">A - M - D</th>
                <th rowspan="2" style="width: 50px;">AÑO INGR.</th>
                <th rowspan="2" style="width: 60px;">FEC. NACIM.</th>
            </tr>
        </thead>
        <tbody>
            @php
                $beneficiarios = $beneficiarios ?? [];
                $row_count = 1;
            @endphp
            
            @foreach($beneficiarios as $beneficiario)
                <tr>
                    <td class="row-number">{{ str_pad($row_count, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="text-center">{{ $beneficiario['fecha_ingreso'] ?? '' }}</td>
                    
                    <!-- Datos de la Socia -->
                    <td class="text-left">{{ $beneficiario['socia_nombre'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['socia_direccion'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['socia_dni'] ?? '' }}</td>
                    
                    <!-- Datos del Beneficiario -->
                    <td class="text-left">{{ $beneficiario['beneficiario_nombre'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['beneficiario_dni'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['beneficiario_baja'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['beneficiario_edad_anos'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['beneficiario_edad_meses'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['beneficiario_amd'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['beneficiario_ano_ingreso'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['beneficiario_fecha_nacimiento'] ?? '' }}</td>
                    
                    <!-- Columnas adicionales -->
                    <td class="text-center">{{ $beneficiario['socia_baja'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['socia_edad_anos'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['socia_edad_meses'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['socia_amd'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['socia_ano_ingreso'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['socia_fecha_nacimiento'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['socia_fecha_termino'] ?? '' }}</td>
                    <td class="text-center">{{ $beneficiario['observaciones'] ?? '' }}</td>
                </tr>
                @php $row_count++; @endphp
            @endforeach
        </tbody>
    </table>

    <!-- RESUMEN DE BENEFICIARIOS POR PRIORIDAD -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- Tabla de resumen izquierda -->
            <td style="width: 65%; vertical-align: top; padding-right: 5px;">
                <div style="font-weight: bold; text-align: center; background-color: #e0e0e0; border: 1px solid #000; padding: 3px; font-size: 7pt; margin-bottom: 2px;">
                    RESUMEN DE BENEFICIARIOS POR PRIORIDAD
                </div>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td colspan="9" style="border: 1px solid #000; text-align: center; font-weight: bold; font-size: 7pt; background-color: #f0f0f0; padding: 2px;">
                            1RA. PRIORIDAD
                        </td>
                        <td colspan="5" rowspan="2" style="border: 1px solid #000; text-align: center; font-weight: bold; font-size: 7pt; background-color: #f0f0f0; padding: 2px; vertical-align: middle;">
                            2DA. PRIORIDAD
                        </td>
                        <td rowspan="3" style="border: 1px solid #000; text-align: center; font-weight: bold; font-size: 7pt; background-color: #f0f0f0; padding: 2px; vertical-align: middle;">
                            GAP DE<br/>ATENCIÓN
                        </td>
                        <td rowspan="3" style="border: 1px solid #000; text-align: center; font-weight: bold; font-size: 7pt; background-color: #f0f0f0; padding: 2px; vertical-align: middle;">
                            TOTAL<br/>GENERAL
                        </td>
                    </tr>
                    <tr>
                        <td colspan="9" style="border: 1px solid #000; text-align: center; font-weight: bold; font-size: 7pt; background-color: #f5f5f5; padding: 2px;">
                            NIÑOS
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>0 AÑOS</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>1 AÑO</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>2 AÑOS</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>3 AÑOS</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>4 AÑOS</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>5 AÑOS</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>6 AÑOS</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>TOTAL</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>MADRES</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>NIÑOS</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>ANCIANOS</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>TUBERCULOSOS</strong></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8;"><strong>DISCAPACITADOS</strong></td>
                    </tr>
                    
                    @php
                        $resumen = $resumen ?? [
                            'total' => ['0_anos' => 4, '1_ano' => 6, '2_anos' => 6, '3_anos' => 6, '4_anos' => 4, '5_anos' => 0, '6_anos' => 6, 'total' => 37, 'madres_gestantes' => 1, 'madres_lactantes' => 10, 'madres_otros' => 0, 'ancianos' => 0, 'tuberculosos' => 0, 'discapacitados' => 0, 'gap' => '', 'total_general' => 48],
                            'masculino' => ['0_anos' => 3, '1_ano' => 2, '2_anos' => 4, '3_anos' => 3, '4_anos' => 3, '5_anos' => 4, '6_anos' => 2, 'total' => 21, 'madres_gestantes' => '', 'madres_lactantes' => '', 'madres_otros' => '', 'ancianos' => '', 'tuberculosos' => '', 'discapacitados' => '', 'gap' => '', 'total_general' => 21],
                            'femenino' => ['0_anos' => 1, '1_ano' => 4, '2_anos' => 2, '3_anos' => 3, '4_anos' => 1, '5_anos' => 2, '6_anos' => 3, 'total' => 16, 'madres_gestantes' => 1, 'madres_lactantes' => 10, 'madres_otros' => '', 'ancianos' => '', 'tuberculosos' => '', 'discapacitados' => '', 'gap' => '', 'total_general' => 27],
                        ];
                    @endphp
                    
                    <tr>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold;">{{ $resumen['total']['0_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold;">{{ $resumen['total']['1_ano'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold;">{{ $resumen['total']['2_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold;">{{ $resumen['total']['3_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold;">{{ $resumen['total']['4_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold;">{{ $resumen['total']['5_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold;">{{ $resumen['total']['6_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold; background-color: #f0f0f0;">{{ $resumen['total']['total'] ?? '' }}</td>
                        <td rowspan="3" style="border: 1px solid #000; padding: 2px; vertical-align: top;">
                            <div style="font-size: 6pt; text-align: left; padding: 2px;">
                                <div style="margin-bottom: 2px;"><strong>GESTANTES:</strong> {{ $resumen['total']['madres_gestantes'] ?? '' }}</div>
                                <div><strong>LACTANTES:</strong> {{ $resumen['total']['madres_lactantes'] ?? '' }}</div>
                            </div>
                        </td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['total']['madres_otros'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['total']['ancianos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['total']['tuberculosos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['total']['discapacitados'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['total']['gap'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 8pt; padding: 2px; font-weight: bold; background-color: #f0f0f0;">{{ $resumen['total']['total_general'] ?? '' }}</td>
                    </tr>
                    
                    <tr>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['0_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['1_ano'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['2_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['3_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['4_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['5_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['6_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold; background-color: #f0f0f0;">{{ $resumen['masculino']['total'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['madres_otros'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['ancianos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['tuberculosos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['discapacitados'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['masculino']['gap'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 8pt; padding: 2px; font-weight: bold; background-color: #f0f0f0;">{{ $resumen['masculino']['total_general'] ?? '' }}</td>
                    </tr>
                    
                    <tr>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['0_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['1_ano'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['2_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['3_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['4_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['5_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['6_anos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px; font-weight: bold; background-color: #f0f0f0;">{{ $resumen['femenino']['total'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['madres_otros'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['ancianos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['tuberculosos'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['discapacitados'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;">{{ $resumen['femenino']['gap'] ?? '' }}</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 8pt; padding: 2px; font-weight: bold; background-color: #f0f0f0;">{{ $resumen['femenino']['total_general'] ?? '' }}</td>
                    </tr>
                    
                    <tr>
                        <td colspan="1" style="border: 1px solid #000; text-align: right; font-size: 7pt; padding: 2px; font-weight: bold; background-color: #f8f8f8;">TOTAL:</td>
                        <td colspan="7" style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                    </tr>
                    
                    <tr>
                        <td colspan="1" style="border: 1px solid #000; text-align: right; font-size: 7pt; padding: 2px; font-weight: bold; background-color: #f8f8f8;">MASCULINO:</td>
                        <td colspan="7" style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                    </tr>
                    
                    <tr>
                        <td colspan="1" style="border: 1px solid #000; text-align: right; font-size: 7pt; padding: 2px; font-weight: bold; background-color: #f8f8f8;">FEMENINO:</td>
                        <td colspan="7" style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 7pt; padding: 2px;"></td>
                    </tr>
                </table>
            </td>
            
            <!-- Tabla de observaciones derecha -->
            <td style="width: 35%; vertical-align: top; padding-left: 5px;">
                <div style="font-weight: bold; text-align: center; background-color: #e0e0e0; border: 1px solid #000; padding: 3px; font-size: 7pt; margin-bottom: 2px;">
                    RESUMEN DE OBSERVACIONES
                </div>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8; font-weight: bold; width: 30px;">CÓDIGO</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8; font-weight: bold;">DESCRIPCIÓN</td>
                        <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px; background-color: #f8f8f8; font-weight: bold; width: 40px;">CANTIDAD</td>
                    </tr>
                    
                    @php
                        $observaciones_resumen = $observaciones_resumen ?? [
                            ['codigo' => '1', 'descripcion' => 'GESTANTE con 3ra. GESTAC. (BAJA)', 'cantidad' => ''],
                            ['codigo' => '2', 'descripcion' => 'BEBE CARD. (3 DE MES ABAJO)', 'cantidad' => ''],
                            ['codigo' => '3', 'descripcion' => 'NIÑOS 7 AÑO. (7 DE 13 AÑO(A)S)', 'cantidad' => ''],
                            ['codigo' => '4', 'descripcion' => 'N. M. DE 6 AÑOS ALTA S. JULI.', 'cantidad' => ''],
                            ['codigo' => '5', 'descripcion' => '', 'cantidad' => ''],
                            ['codigo' => '6', 'descripcion' => 'M. GEST. TUBERAL. (DES.PAU. HE)', 'cantidad' => '6'],
                            ['codigo' => '7', 'descripcion' => 'M. F. ESTAD TUBERAL.', 'cantidad' => ''],
                            ['codigo' => '8', 'descripcion' => 'GESTANTE C(I)VIL P(G) S(Q)CAL(RAL', 'cantidad' => ''],
                            ['codigo' => '9', 'descripcion' => 'NIÑO 7 DE 12MES CLUB P(A)C(.)P', 'cantidad' => ''],
                        ];
                    @endphp
                    
                    @foreach($observaciones_resumen as $obs)
                        <tr>
                            <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px;">{{ $obs['codigo'] }}</td>
                            <td style="border: 1px solid #000; text-align: left; font-size: 6pt; padding: 2px;">{{ $obs['descripcion'] }}</td>
                            <td style="border: 1px solid #000; text-align: center; font-size: 6pt; padding: 2px;">{{ $obs['cantidad'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

</body>
</html>