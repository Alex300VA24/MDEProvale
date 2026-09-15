<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Beneficiarios - Programa Vaso de Leche</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            padding: 12px 20px 20px 20px;
            margin: 0;
        }
        
        .header {
            display: table;
            width: 100%;
            border: 2px solid #000;
            margin-bottom: 10px;
        }
        
        .header-row {
            display: table-row;
        }
        
        .header-cell {
            display: table-cell;
            padding: 5px;
            vertical-align: middle;
            border-right: 1px solid #000;
        }
        
        .header-cell:last-child {
            border-right: none;
        }
        
        .logo {
            width: 80px;
            text-align: center;
        }
        
        .title {
            text-align: center;
            padding: 10px;
        }
        
        .title h2 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .title h3 {
            font-size: 10pt;
            margin-bottom: 3px;
        }
        
        .title h1 {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .codigo-box {
            width: 120px;
            border-left: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .periodo-box {
            width: 100px;
            border-left: 1px solid #000;
            padding: 3px;
            font-size: 8pt;
        }
        
        .periodo-label {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .periodo-fields {
            display: flex;
            justify-content: space-between;
        }
        
        .nombre-club-row {
            border: 1px solid #000;
            border-top: none;
            padding: 5px;
            margin-bottom: 3px;
        }
        
        .section {
            border: 2px solid #000;
            margin-bottom: 2px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background-color: #e0e0e0;
            padding: 2px 4px;
            font-weight: bold;
            font-size: 7pt;
            border-bottom: 1px solid #000;
        }
        
        .section-content {
            padding: 3px 2px;
        }
        
        .form-row {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }
        
        .form-label {
            display: inline-block;
            font-weight: bold;
            margin-right: 5px;
            font-size: 8pt;
        }
        
        .form-field {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 150px;
            padding: 0 3px;
        }
        
        .checkbox-group {
            display: inline-block;
            margin-right: 15px;
        }
        
        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 3px;
            vertical-align: middle;
        }
        
        .checkbox.checked::after {
            content: "";
            font-size: 10pt;
            font-weight: bold;
        }
        
        .dni-boxes {
            display: inline-block;
        }
        
        .dni-box {
            display: inline-block;
            width: 15px;
            height: 18px;
            border: 1px solid #000;
            margin: 0 1px;
            text-align: center;
            vertical-align: middle;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        td, th {
            border: 1px solid #000;
            padding: 3px;
            font-size: 8pt;
        }
        
        .beneficiario-section {
            border: 1px solid #000;
            margin-bottom: 5px;
            padding: 5px;
        }
        
        .footer-section {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        
        .footer-cell {
            display: table-cell;
            border: 1px solid #000;
            padding: 30px 10px 10px 10px;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .small-text {
            font-size: 7pt;
        }
        
        .inline-field {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 80px;
            padding: 0 3px;
        }
    </style>
</head>
<body>
@php
    $partner     = $partner ?? null;
    $socia       = $partner?->people ?? null;
    $association = $partner?->association ?? null;
    $bens        = $partner?->beneficiaries ?? collect();
    $b1 = $bens->get(0); $h1 = $b1?->histories?->first();
    $b2 = $bens->get(1); $h2 = $b2?->histories?->first();
    $b3 = $bens->get(2); $h3 = $b3?->histories?->first();
    $logoPath    = $logoPath ?? public_path('img/muni2.png');

    $dniSociaArr = str_split(str_pad($socia?->dni ?? '', 8, ' ', STR_PAD_RIGHT));
    $dniB1Arr    = str_split(str_pad($b1?->person?->dni ?? '', 8, ' ', STR_PAD_RIGHT));
    $dniB2Arr    = str_split(str_pad($b2?->person?->dni ?? '', 8, ' ', STR_PAD_RIGHT));
    $dniB3Arr    = str_split(str_pad($b3?->person?->dni ?? '', 8, ' ', STR_PAD_RIGHT));

    $st = strtoupper($partner?->marital_status ?? '');
    $ed = strtoupper($partner?->education_level ?? '');
    $r1 = strtoupper($b1?->relationship?->title ?? '');
    $r2 = strtoupper($b2?->relationship?->title ?? '');
    $r3 = strtoupper($b3?->relationship?->title ?? '');
@endphp
    <!-- HEADER -->
    <table style="width: 100%; border: 2px solid #000; margin-bottom: 5px;">
        <tr>
            <td style="width: 80px; text-align: center; padding: 5px;">
                <div style="width: 70px; height: 70px; border: 1px solid #000; margin: 0 auto;">
                    @if(isset($logoPath) && file_exists($logoPath))
                        <img src="{{ $logoPath }}" style="width: 100%; height: 100%; object-fit: contain;">
                    @else
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 10pt; font-weight: bold;">LOGO</div>
                    @endif
                </div>
            </td>
            <td style="text-align: center; padding: 10px;">
                <div style="font-weight: bold; font-size: 10pt;">MUNICIPALIDAD DISTRITAL DE LA ESPERANZA</div>
                <div style="font-size: 9pt; margin: 2px 0;">PROGRAMA VASO DE LECHE</div>
                <div style="font-weight: bold; font-size: 18pt; letter-spacing: 2px; margin-top: 3px;">FICHA DE BENEFICIARIOS</div>
                <div style="font-size: 7pt; margin-top: 2px;">Ficha de inscripción y empadronamiento individual de la madre/socia y beneficiarios del programa vaso de leche</div>
            </td>
            <td style="width: 100px; padding: 5px;">
                <div style="font-weight: bold; font-size: 8pt; text-align: center; margin-bottom: 5px;">CODIGO</div>
                <div style="height: 30px; border: 1px solid #000; text-align: center; line-height: 30px; font-weight: bold; font-size: 10pt;">{{ $partner?->id ? sprintf('%06d', $partner->id) : '' }}</div>
            </td>
            <td style="width: 90px; padding: 5px;">
                <div style="font-weight: bold; font-size: 8pt; text-align: center; margin-bottom: 3px;">PERIODO</div>
                <table style="border: none; width: 100%;">
                    <tr>
                        <td style="border: none; font-size: 7pt; padding: 1px; height: 25px; width: 75%; text-align: center;">AÑO</td>
                        <td style="border: none; font-size: 7pt; padding: 1px; height: 25px; width: 25%; text-align: center;">SEMESTRE</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 2px; text-align: center; font-weight: bold;">{{ $partner?->date_begin ? date('Y', strtotime($partner->date_begin)) : date('Y') }}</td>
                        <td style="padding: 6px 2px; text-align: center; font-weight: bold;">{{ $partner?->date_begin ? (date('n', strtotime($partner->date_begin)) <= 6 ? 'I' : 'II') : '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- NOMBRE DEL CLUB -->
    <div style="border: 1px solid #000; padding: 5px; margin-bottom: 3px; font-size: 8pt;">
        <span style="font-weight: bold;">NOMBRE DEL CLUB:</span>
        <span style="border-bottom: 1px solid #000; display: inline-block; width: calc(100% - 140px); margin-left: 5px; font-weight: bold;">{{ $association?->name ?? '' }}</span>
    </div>

    <!-- SECCIÓN 1: DATOS DE LA MADRE/SOCIA -->
    <div class="section">
        <div class="section-title">1- DATOS DE LA MADRE/SOCIA: (ADJUNTAR COPIA DNI)</div>
        <div class="section-content">
            <div style="margin-bottom: 5px;">
                <span class="form-label">1.1 APELLIDOS Y NOMBRES:</span>
                <table style="display: inline-table; width: calc(100% - 180px); margin-left: 5px; border: 1px solid #000;">
                    <tr>
                        <td style="width: 33%; text-align: center; font-size: 7pt; padding: 2px;">APELLIDO PATERNO</td>
                        <td style="width: 33%; text-align: center; font-size: 7pt; padding: 2px;">APELLIDO MATERNO</td>
                        <td style="width: 34%; text-align: center; font-size: 7pt; padding: 2px;">NOMBRES</td>
                    </tr>
                    <tr>
                        <td style="height: 18px; text-align: center; font-weight: bold;">{{ $socia?->father_lastname ?? '' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $socia?->mother_lastname ?? '' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $socia?->names ?? '' }}</td>
                    </tr>
                </table>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">1.2 DIRECCION:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 400px; margin-left: 5px;">{{ $socia?->address ?? '' }}</span>
                <span class="form-label" style="margin-left: 10px;">SECTOR:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 100px; margin-left: 5px;">{{ $socia?->placeSector ? ($socia->placeSector->place?->title . ' - ' . $socia->placeSector->sector?->title) : '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">1.3 Nº DNI:</span>
                <span class="dni-boxes">
                    @foreach($dniSociaArr as $char)
                        <span class="dni-box">{{ trim($char) }}</span>
                    @endforeach
                </span>
                <span class="form-label" style="margin-left: 15px;">1.3.1 FECHA DE NACIMIENTO:</span>
                <span style="border: 1px solid #000; display: inline-block; width: 100px; height: 18px; margin-left: 5px; text-align: center; line-height: 17px; font-weight: bold;">{{ $socia?->birthdate ? date('d/m/Y', strtotime($socia->birthdate)) : '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">1.4 ESTADO CIVIL:</span>
                <span class="checkbox-group">
                    <span>SOLTERA/O:</span>
                    <span class="checkbox">{{ str_contains($st, 'SOLT') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>CASADA/O:</span>
                    <span class="checkbox">{{ str_contains($st, 'CAS') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>CONVIVIENTE:</span>
                    <span class="checkbox">{{ str_contains($st, 'CONV') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>SEPARADA/O:</span>
                    <span class="checkbox">{{ str_contains($st, 'SEP') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>VIUDA/O:</span>
                    <span class="checkbox">{{ str_contains($st, 'VIU') ? 'X' : '' }}</span>
                </span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">1.5 GRADO DE INSTRUCCION:</span>
                <span class="checkbox-group">
                    <span>NINGUNO:</span>
                    <span class="checkbox">{{ str_contains($ed, 'NING') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>INICIAL:</span>
                    <span class="checkbox">{{ str_contains($ed, 'INIC') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>PRIMARIA:</span>
                    <span class="checkbox">{{ str_contains($ed, 'PRIM') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>SECUNDARIA:</span>
                    <span class="checkbox">{{ str_contains($ed, 'SEC') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>SUPERIOR:</span>
                    <span class="checkbox">{{ str_contains($ed, 'SUP') ? 'X' : '' }}</span>
                </span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">1.6 OCUPACION:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 200px; margin-left: 5px;">{{ $partner?->occupation ?? '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">1.7 Nº DE HIJOS:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 200px; margin-left: 5px;">{{ $partner?->children_count ?? '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">1.8 ACTUALMENTE SE ENCUENTRA:</span>
                <span style="margin-left: 10px;">GESTANDO (ADJUNTAR CARNET DE GESTACION)</span>
                <span class="checkbox" style="margin-left: 5px;">{{ $partner?->is_pregnant ? 'X' : '' }}</span>
                <span style="margin-left: 15px;">LACTANDO:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ $partner?->is_lactating ? 'X' : '' }}</span>
            </div>

            <div>
                <span class="form-label">1.9 OCUPACION DEL CONYUGE:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 200px; margin-left: 5px;">{{ $partner?->spouse_occupation ?? '' }}</span>
                <span class="form-label" style="margin-left: 10px;">GRADO DE INSTRUCCION:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 150px; margin-left: 5px;">{{ $partner?->spouse_education_level ?? '' }}</span>
                <span class="form-label" style="margin-left: 10px;">INGRESO FAMILIAR:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 100px; margin-left: 5px;">{{ $partner?->family_income ? 'S/ ' . number_format($partner->family_income, 2) : '' }}</span>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: BENEFICIARIO 1 -->
    <div class="section">
        <div class="section-title">2- DATOS DEL BENEFICIARIO 1: (ADJUNTAR COPIA DNI / PARTIDA NACIMIENTO)</div>
        <div class="section-content">
            <div style="margin-bottom: 5px;">
                <span class="form-label">2.1 PARENTESCO CON LA SOCIA:</span>
                <span class="checkbox-group">
                    <span>HIJO:</span>
                    <span class="checkbox">{{ str_contains($r1, 'HIJ') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>NIETO:</span>
                    <span class="checkbox">{{ str_contains($r1, 'NIET') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>SOBRINO:</span>
                    <span class="checkbox">{{ str_contains($r1, 'SOBR') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>HERMANO:</span>
                    <span class="checkbox">{{ str_contains($r1, 'HERM') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>PRIMO:</span>
                    <span class="checkbox">{{ str_contains($r1, 'PRIM') ? 'X' : '' }}</span>
                </span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">2.2 APELLIDOS Y NOMBRES:</span>
                <table style="display: inline-table; width: calc(100% - 180px); margin-left: 5px; border: 1px solid #000;">
                    <tr>
                        <td style="width: 33%; text-align: center; font-size: 7pt; padding: 2px;">APELLIDO PATERNO</td>
                        <td style="width: 33%; text-align: center; font-size: 7pt; padding: 2px;">APELLIDO MATERNO</td>
                        <td style="width: 34%; text-align: center; font-size: 7pt; padding: 2px;">NOMBRES</td>
                    </tr>
                    <tr>
                        <td style="height: 18px; text-align: center; font-weight: bold;">{{ $b1?->person?->father_lastname ?? '' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $b1?->person?->mother_lastname ?? '' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $b1?->person?->names ?? '' }}</td>
                    </tr>
                </table>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">2.3 FECHA DE NACIMIENTO:</span>
                <span style="border: 1px solid #000; display: inline-block; width: 100px; height: 18px; margin-left: 5px; text-align: center; line-height: 17px; font-weight: bold;">{{ $b1?->person?->birthdate ? date('d/m/Y', strtotime($b1->person->birthdate)) : '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">2.4 DOCUMENTO PRESENTADO:</span>
                <span style="margin-left: 10px;">DNI:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ !empty($b1?->person?->dni) ? 'X' : '' }}</span>
                <span style="margin-left: 5px;">Nº</span>
                <span class="dni-boxes" style="margin-left: 5px;">
                    @foreach($dniB1Arr as $char)
                        <span class="dni-box">{{ trim($char) }}</span>
                    @endforeach
                </span>
                <span style="margin-left: 15px;">PARTIDA NACIMIENTO:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ (empty($b1?->person?->dni) && $b1) ? 'X' : '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">2.5 DESNUTRIDO/DISCAPACITADO (CONSTANCIA):</span>
                <span style="margin-left: 10px;">DESNUTRIDO DE 7 A 13 AÑOS:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ $h1?->is_malnourished ? 'X' : '' }}</span>
                <span style="margin-left: 15px;">DISCAPACITADO:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ $h1?->is_disabled ? 'X' : '' }}</span>
            </div>

            <div>
                <span class="form-label">2.6 MEDIDAS ANTROPOMETRICAS:</span>
                <span style="margin-left: 10px;">PESO:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 80px; margin-left: 5px; text-align: center; font-weight: bold;">{{ $h1?->weight > 0 ? $h1->weight . ' kg' : '' }}</span>
                <span style="margin-left: 15px;">TALLA:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 80px; margin-left: 5px; text-align: center; font-weight: bold;">{{ $h1?->height > 0 ? $h1->height . ' cm' : '' }}</span>
                <span style="margin-left: 15px;">SEXO (M/F):</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 80px; margin-left: 5px; text-align: center; font-weight: bold;">{{ $b1?->person?->gender ?? '' }}</span>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 3: BENEFICIARIO 2 -->
    <div class="section">
        <div class="section-title">3- DATOS DEL BENEFICIARIO 2: (ADJUNTAR COPIA DNI / PARTIDA NACIMIENTO)</div>
        <div class="section-content">
            <div style="margin-bottom: 5px;">
                <span class="form-label">3.1 PARENTESCO CON LA SOCIA:</span>
                <span class="checkbox-group">
                    <span>HIJO:</span>
                    <span class="checkbox">{{ str_contains($r2, 'HIJ') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>NIETO:</span>
                    <span class="checkbox">{{ str_contains($r2, 'NIET') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>SOBRINO:</span>
                    <span class="checkbox">{{ str_contains($r2, 'SOBR') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>HERMANO:</span>
                    <span class="checkbox">{{ str_contains($r2, 'HERM') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>PRIMO:</span>
                    <span class="checkbox">{{ str_contains($r2, 'PRIM') ? 'X' : '' }}</span>
                </span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">3.2 APELLIDOS Y NOMBRES:</span>
                <table style="display: inline-table; width: calc(100% - 180px); margin-left: 5px; border: 1px solid #000;">
                    <tr>
                        <td style="width: 33%; text-align: center; font-size: 7pt; padding: 2px;">APELLIDO PATERNO</td>
                        <td style="width: 33%; text-align: center; font-size: 7pt; padding: 2px;">APELLIDO MATERNO</td>
                        <td style="width: 34%; text-align: center; font-size: 7pt; padding: 2px;">NOMBRES</td>
                    </tr>
                    <tr>
                        <td style="height: 18px; text-align: center; font-weight: bold;">{{ $b2?->person?->father_lastname ?? '' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $b2?->person?->mother_lastname ?? '' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $b2?->person?->names ?? '' }}</td>
                    </tr>
                </table>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">3.3 FECHA DE NACIMIENTO:</span>
                <span style="border: 1px solid #000; display: inline-block; width: 100px; height: 18px; margin-left: 5px; text-align: center; line-height: 17px; font-weight: bold;">{{ $b2?->person?->birthdate ? date('d/m/Y', strtotime($b2->person->birthdate)) : '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">3.4 DOCUMENTO PRESENTADO:</span>
                <span style="margin-left: 10px;">DNI:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ !empty($b2?->person?->dni) ? 'X' : '' }}</span>
                <span style="margin-left: 5px;">Nº</span>
                <span class="dni-boxes" style="margin-left: 5px;">
                    @foreach($dniB2Arr as $char)
                        <span class="dni-box">{{ trim($char) }}</span>
                    @endforeach
                </span>
                <span style="margin-left: 15px;">PARTIDA NACIMIENTO:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ (empty($b2?->person?->dni) && $b2) ? 'X' : '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">3.5 DESNUTRIDO/DISCAPACITADO (CONSTANCIA):</span>
                <span style="margin-left: 10px;">DESNUTRIDO DE 7 A 13 AÑOS:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ $h2?->is_malnourished ? 'X' : '' }}</span>
                <span style="margin-left: 15px;">DISCAPACITADO:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ $h2?->is_disabled ? 'X' : '' }}</span>
            </div>

            <div>
                <span class="form-label">3.6 MEDIDAS ANTROPOMETRICAS:</span>
                <span style="margin-left: 10px;">PESO:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 80px; margin-left: 5px; text-align: center; font-weight: bold;">{{ $h2?->weight > 0 ? $h2->weight . ' kg' : '' }}</span>
                <span style="margin-left: 15px;">TALLA:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 80px; margin-left: 5px; text-align: center; font-weight: bold;">{{ $h2?->height > 0 ? $h2->height . ' cm' : '' }}</span>
                <span style="margin-left: 15px;">SEXO (M/F):</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 80px; margin-left: 5px; text-align: center; font-weight: bold;">{{ $b2?->person?->gender ?? '' }}</span>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 4: BENEFICIARIO 3 -->
    <div class="section">
        <div class="section-title">4- DATOS DEL BENEFICIARIO 3: (ADJUNTAR COPIA DNI / PARTIDA NACIMIENTO)</div>
        <div class="section-content">
            <div style="margin-bottom: 5px;">
                <span class="form-label">4.1 PARENTESCO CON LA SOCIA:</span>
                <span class="checkbox-group">
                    <span>HIJO:</span>
                    <span class="checkbox">{{ str_contains($r3, 'HIJ') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>NIETO:</span>
                    <span class="checkbox">{{ str_contains($r3, 'NIET') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>SOBRINO:</span>
                    <span class="checkbox">{{ str_contains($r3, 'SOBR') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>HERMANO:</span>
                    <span class="checkbox">{{ str_contains($r3, 'HERM') ? 'X' : '' }}</span>
                </span>
                <span class="checkbox-group">
                    <span>PRIMO:</span>
                    <span class="checkbox">{{ str_contains($r3, 'PRIM') ? 'X' : '' }}</span>
                </span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">4.2 APELLIDOS Y NOMBRES:</span>
                <table style="display: inline-table; width: calc(100% - 180px); margin-left: 5px; border: 1px solid #000;">
                    <tr>
                        <td style="width: 33%; text-align: center; font-size: 7pt; padding: 2px;">APELLIDO PATERNO</td>
                        <td style="width: 33%; text-align: center; font-size: 7pt; padding: 2px;">APELLIDO MATERNO</td>
                        <td style="width: 34%; text-align: center; font-size: 7pt; padding: 2px;">NOMBRES</td>
                    </tr>
                    <tr>
                        <td style="height: 18px; text-align: center; font-weight: bold;">{{ $b3?->person?->father_lastname ?? '' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $b3?->person?->mother_lastname ?? '' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $b3?->person?->names ?? '' }}</td>
                    </tr>
                </table>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">4.3 FECHA DE NACIMIENTO:</span>
                <span style="border: 1px solid #000; display: inline-block; width: 100px; height: 18px; margin-left: 5px; text-align: center; line-height: 17px; font-weight: bold;">{{ $b3?->person?->birthdate ? date('d/m/Y', strtotime($b3->person->birthdate)) : '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">4.4 DOCUMENTO PRESENTADO:</span>
                <span style="margin-left: 10px;">DNI:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ !empty($b3?->person?->dni) ? 'X' : '' }}</span>
                <span style="margin-left: 5px;">Nº</span>
                <span class="dni-boxes" style="margin-left: 5px;">
                    @foreach($dniB3Arr as $char)
                        <span class="dni-box">{{ trim($char) }}</span>
                    @endforeach
                </span>
                <span style="margin-left: 15px;">PARTIDA NACIMIENTO:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ (empty($b3?->person?->dni) && $b3) ? 'X' : '' }}</span>
            </div>

            <div style="margin-bottom: 5px;">
                <span class="form-label">4.5 DESNUTRIDO/DISCAPACITADO (CONSTANCIA):</span>
                <span style="margin-left: 10px;">DESNUTRIDO DE 7 A 13 AÑOS:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ $h3?->is_malnourished ? 'X' : '' }}</span>
                <span style="margin-left: 15px;">DISCAPACITADO:</span>
                <span class="checkbox" style="margin-left: 5px;">{{ $h3?->is_disabled ? 'X' : '' }}</span>
            </div>

            <div>
                <span class="form-label">4.6 MEDIDAS ANTROPOMETRICAS:</span>
                <span style="margin-left: 10px;">PESO:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 80px; margin-left: 5px; text-align: center; font-weight: bold;">{{ $h3?->weight > 0 ? $h3->weight . ' kg' : '' }}</span>
                <span style="margin-left: 15px;">TALLA:</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 80px; margin-left: 5px; text-align: center; font-weight: bold;">{{ $h3?->height > 0 ? $h3->height . ' cm' : '' }}</span>
                <span style="margin-left: 15px;">SEXO (M/F):</span>
                <span style="border-bottom: 1px solid #000; display: inline-block; width: 80px; margin-left: 5px; text-align: center; font-weight: bold;">{{ $b3?->person?->gender ?? '' }}</span>
            </div>
        </div>
    </div>

    <!-- FOOTER - FIRMAS -->
    <table style="width: 100%; border: 2px solid #000; margin-top: 5px;">
        <tr>
            <td style="width: 33%; height: 70px; padding: 2px 3px; font-size: 7pt; position: relative; vertical-align: bottom;">
                <div style="font-size: 6pt; position: absolute; top: 2px; left: 3px;">FECHA: {{ $partner?->date_begin ? date('d/m/Y', strtotime($partner->date_begin)) : date('d/m/Y') }}</div>
                <div style="border-bottom: 1px solid #000; width: 80%; margin: 35px auto 2px auto;"></div>
                <div style="font-weight: bold; font-size: 6pt; text-align: center;">FIRMA SOCIA</div>
            </td>
            <td style="width: 22%; height: 70px; padding: 2px 3px; font-size: 7pt; text-align: center; vertical-align: bottom;">
                <div style="border-bottom: 1px solid #000; width: 80%; margin: 0 auto 2px auto; height: 40px;"></div>
                <div style="font-weight: bold; font-size: 6pt;">HUELLA</div>
            </td>
            <td style="width: 45%; height: 70px; padding: 2px 3px; font-size: 7pt; text-align: center; vertical-align: bottom; position: relative;">
                <div style="font-size: 6pt; position: absolute; top: 2px; left: 3px;">FECHA: {{ date('d/m/Y') }}</div>
                <div style="border-bottom: 1px solid #000; width: 80%; margin: 35px auto 2px auto;"></div>
                <div style="font-weight: bold; font-size: 6pt;">NOMBRE, FIRMA Y SELLO DE LA PRESIDENTA</div>
            </td>
        </tr>
    </table>

</body>
</html>