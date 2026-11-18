<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Beneficiarios - Programa Vaso de Leche</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 8pt; color: #000; padding: 14px 34px; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td { vertical-align: middle; }

        /* Bloques */
        .box { border: 1.5px solid #000; margin-bottom: 3px; page-break-inside: avoid; }
        .box-title { background: #dcdcdc; border-bottom: 1px solid #000; padding: 3px 6px; font-weight: bold; font-size: 7.5pt; }

        /* Filas etiqueta / valor */
        .rows > tbody > tr > td { padding: 1.5px 6px; border-bottom: 1px solid #bdbdbd; }
        .rows > tbody > tr:last-child > td { border-bottom: none; }
        .lbl { width: 31%; font-weight: bold; font-size: 7.5pt; padding-left: 8px !important; height: 19px; }
        .val { width: 69%; padding-right: 8px !important; }

        /* Tablas internas sin bordes */
        .in td { padding: 0 2px; border: none; font-size: 7.5pt; }
        .in .t { text-align: right; padding-right: 4px; white-space: nowrap; }

        /* Campos */
        .line { border-bottom: 1px solid #000 !important; text-align: center; font-weight: bold; height: 13px; }
        .cap { text-align: center; font-size: 6pt; color: #444; padding-top: 1px; }
        .cell { border: 1px solid #000 !important; text-align: center; font-weight: bold; height: 15px; }

        /* Casillas */
        .cb { display: inline-block; width: 12px; height: 12px; border: 1px solid #000; text-align: center; font-size: 8pt; font-weight: bold; line-height: 11px; vertical-align: baseline; margin-left: 4px; }
        .opt { white-space: nowrap; }
        .opt .cb { margin-left: 4px; }

        /* Casillas DNI */
        .dni td.d { border: 1px solid #000; text-align: center; font-weight: bold; height: 15px; width: 12.5%; }
    </style>
</head>
<body>
@php
    $partner     = $partner ?? null;
    $socia       = $partner?->people ?? null;
    $association = $partner?->association ?? null;
    $bens        = $partner?->beneficiaries ?? collect();
    $logoPath    = $logoPath ?? public_path('img/muni2.png');

    $st = strtoupper($partner?->marital_status ?? '');
    $ed = strtoupper($partner?->education_level ?? '');

    $cb = fn ($on) => '<span class="cb">' . ($on ? 'X' : '&nbsp;') . '</span>';
    $fmt = fn ($d) => $d ? date('d/m/Y', strtotime($d)) : '';
    $dniArr = fn ($dni) => str_split(str_pad((string) $dni, 8, ' ', STR_PAD_RIGHT));
@endphp

    {{-- ENCABEZADO --}}
    <table style="border: 1.5px solid #000; margin-bottom: 5px;">
        <tr>
            <td style="width: 62%; padding: 4px 8px;">
                <table class="in"><tr>
                    <td style="width: 16%; text-align: left;">
                        @if(file_exists($logoPath))
                            <img src="{{ $logoPath }}" style="width: 64px; height: 64px;">
                        @else
                            <b>LOGO</b>
                        @endif
                    </td>
                    <td style="width: 84%; text-align: left; padding-left: 10px;">
                        <div style="font-weight: bold; font-size: 11pt; white-space: nowrap;">MUNICIPALIDAD DISTRITAL DE LA ESPERANZA</div>
                        <div style="font-size: 11pt; margin-top: 6px; white-space: nowrap;">PROGRAMA VASO DE LECHE</div>
                    </td>
                </tr></table>
            </td>
            <td style="width: 17%; padding: 5px; border-left: 1px solid #000; text-align: center;">
                <div style="font-weight: bold; font-size: 7.5pt; margin-bottom: 4px;">CÓDIGO</div>
                <div class="cell" style="height: 26px; line-height: 24px; font-size: 10pt;">{{ $partner?->id ? sprintf('%06d', $partner->id) : '' }}</div>
            </td>
            <td style="width: 21%; padding: 5px; border-left: 1px solid #000; text-align: center;">
                <div style="font-weight: bold; font-size: 7.5pt; margin-bottom: 4px;">PERIODO</div>
                <table class="in" style="table-layout: fixed;">
                    <tr>
                        <td class="cap" style="width: 55%;">AÑO</td>
                        <td class="cap" style="width: 45%;">SEMESTRE</td>
                    </tr>
                    <tr>
                        <td class="cell" style="height: 20px;">{{ $partner?->date_begin ? date('Y', strtotime($partner->date_begin)) : ($partner ? date('Y') : '') }}</td>
                        <td class="cell" style="height: 20px;">{{ $partner?->date_begin ? (date('n', strtotime($partner->date_begin)) <= 6 ? 'I' : 'II') : '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="border-top: 1px solid #000; text-align: center; padding: 5px 8px 5px 28px;">
                <div style="font-weight: bold; font-size: 21pt; letter-spacing: 3px;">FICHA DE BENEFICIARIOS</div>
                <div style="font-size: 7pt; margin-top: 2px;">Ficha de inscripción y empadronamiento individual de la madre/socia y beneficiarios del programa vaso de leche</div>
            </td>
        </tr>
    </table>

    {{-- CLUB --}}
    <table style="border: 1.5px solid #000; margin-bottom: 5px;">
        <tr>
            <td style="width: 31%; padding: 4px 6px; font-weight: bold; font-size: 7.5pt;">NOMBRE DEL CLUB:</td>
            <td style="width: 69%; padding: 4px 6px;">
                <table class="in"><tr><td class="line">{{ $association?->name ?? '' }}</td></tr></table>
            </td>
        </tr>
    </table>

    {{-- 1. MADRE / SOCIA --}}
    <div class="box">
        <div class="box-title">1- DATOS DE LA MADRE/SOCIA: (ADJUNTAR COPIA DNI)</div>
        <table class="rows">
            <tr>
                <td class="lbl">1.1 APELLIDOS Y NOMBRES:</td>
                <td class="val">
                    <table class="in">
                        <tr>
                            <td class="line" style="width: 33%;">{{ $socia?->father_lastname ?? '' }}</td>
                            <td class="line" style="width: 33%;">{{ $socia?->mother_lastname ?? '' }}</td>
                            <td class="line" style="width: 34%;">{{ $socia?->names ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="cap">APELLIDO PATERNO</td>
                            <td class="cap">APELLIDO MATERNO</td>
                            <td class="cap">NOMBRES</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="lbl">1.2 DIRECCIÓN / SECTOR:</td>
                <td class="val">
                    <table class="in">
                        <tr>
                            <td class="line" style="width: 62%;">{{ $socia?->address ?? '' }}</td>
                            <td style="width: 3%;"></td>
                            <td class="line" style="width: 35%;">{{ $socia?->placeSector ? ($socia->placeSector->place?->title . ' - ' . $socia->placeSector->sector?->title) : '' }}</td>
                        </tr>
                        <tr>
                            <td class="cap">DIRECCIÓN</td>
                            <td></td>
                            <td class="cap">SECTOR</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="lbl">1.3 Nº DNI / 1.3.1 FECHA DE NAC.:</td>
                <td class="val">
                    <table class="in">
                        <tr>
                            <td style="width: 52%;">
                                <table class="dni dni-t"><tr>
                                    @foreach($dniArr($socia?->dni ?? '') as $c)
                                        <td class="d">{{ trim($c) }}</td>
                                    @endforeach
                                </tr></table>
                            </td>
                            <td style="width: 4%;"></td>
                            <td class="cell" style="width: 44%;">{{ $fmt($socia?->birthdate) }}</td>
                        </tr>
                        <tr>
                            <td class="cap">Nº DNI</td>
                            <td></td>
                            <td class="cap">FECHA DE NACIMIENTO</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="lbl">1.4 ESTADO CIVIL:</td>
                <td class="val">
                    <table class="in"><tr>
                        <td class="opt">SOLTERA/O {!! $cb(str_contains($st, 'SOLT')) !!}</td>
                        <td class="opt">CASADA/O {!! $cb(str_contains($st, 'CAS')) !!}</td>
                        <td class="opt">CONVIVIENTE {!! $cb(str_contains($st, 'CONV')) !!}</td>
                        <td class="opt">SEPARADA/O {!! $cb(str_contains($st, 'SEP')) !!}</td>
                        <td class="opt">VIUDA/O {!! $cb(str_contains($st, 'VIU')) !!}</td>
                    </tr></table>
                </td>
            </tr>
            <tr>
                <td class="lbl">1.5 GRADO DE INSTRUCCIÓN:</td>
                <td class="val">
                    <table class="in"><tr>
                        <td class="opt">NINGUNO {!! $cb(str_contains($ed, 'NING')) !!}</td>
                        <td class="opt">INICIAL {!! $cb(str_contains($ed, 'INIC')) !!}</td>
                        <td class="opt">PRIMARIA {!! $cb(str_contains($ed, 'PRIM')) !!}</td>
                        <td class="opt">SECUNDARIA {!! $cb(str_contains($ed, 'SEC')) !!}</td>
                        <td class="opt">SUPERIOR {!! $cb(str_contains($ed, 'SUP')) !!}</td>
                    </tr></table>
                </td>
            </tr>
            <tr>
                <td class="lbl">1.6 OCUPACIÓN / 1.7 Nº DE HIJOS:</td>
                <td class="val">
                    <table class="in">
                        <tr>
                            <td class="line" style="width: 62%;">{{ $partner?->occupation ?? '' }}</td>
                            <td style="width: 3%;"></td>
                            <td class="line" style="width: 35%;">{{ $partner?->children_count ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="cap">OCUPACIÓN</td>
                            <td></td>
                            <td class="cap">Nº DE HIJOS</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="lbl">1.8 ACTUALMENTE SE ENCUENTRA:</td>
                <td class="val">
                    <table class="in"><tr>
                        <td class="opt" style="width: 66%;">GESTANDO (ADJUNTAR CARNET DE GESTACIÓN) {!! $cb($partner?->is_pregnant) !!}</td>
                        <td class="opt" style="width: 34%;">LACTANDO {!! $cb($partner?->is_lactating) !!}</td>
                    </tr></table>
                </td>
            </tr>
            <tr>
                <td class="lbl">1.9 DATOS DEL CÓNYUGE / FAMILIA:</td>
                <td class="val">
                    <table class="in">
                        <tr>
                            <td class="line" style="width: 38%;">{{ $partner?->spouse_occupation ?? '' }}</td>
                            <td style="width: 2%;"></td>
                            <td class="line" style="width: 34%;">{{ $partner?->spouse_education_level ?? '' }}</td>
                            <td style="width: 2%;"></td>
                            <td class="line" style="width: 24%;">{{ $partner?->family_income ? 'S/ ' . number_format($partner->family_income, 2) : '' }}</td>
                        </tr>
                        <tr>
                            <td class="cap">OCUPACIÓN DEL CÓNYUGE</td>
                            <td></td>
                            <td class="cap">GRADO DE INSTRUCCIÓN</td>
                            <td></td>
                            <td class="cap">INGRESO FAMILIAR</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- 2, 3, 4. BENEFICIARIOS --}}
    @foreach([0, 1, 2] as $i)
        @php
            $b   = $bens->get($i);
            $h   = $b?->histories?->first();
            $p   = $b?->person;
            $r   = strtoupper($b?->relationship?->title ?? '');
            $sec = $i + 2;
        @endphp
        <div class="box">
            <div class="box-title">{{ $sec }}- DATOS DEL BENEFICIARIO {{ $i + 1 }}: (ADJUNTAR COPIA DNI / PARTIDA NACIMIENTO)</div>
            <table class="rows">
                <tr>
                    <td class="lbl">{{ $sec }}.1 PARENTESCO CON LA SOCIA:</td>
                    <td class="val">
                        <table class="in"><tr>
                            <td class="opt">HIJO {!! $cb(str_contains($r, 'HIJ')) !!}</td>
                            <td class="opt">NIETO {!! $cb(str_contains($r, 'NIET')) !!}</td>
                            <td class="opt">SOBRINO {!! $cb(str_contains($r, 'SOBR')) !!}</td>
                            <td class="opt">HERMANO {!! $cb(str_contains($r, 'HERM')) !!}</td>
                            <td class="opt">PRIMO {!! $cb(str_contains($r, 'PRIM')) !!}</td>
                        </tr></table>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">{{ $sec }}.2 APELLIDOS Y NOMBRES:</td>
                    <td class="val">
                        <table class="in">
                            <tr>
                                <td class="line" style="width: 33%;">{{ $p?->father_lastname ?? '' }}</td>
                                <td class="line" style="width: 33%;">{{ $p?->mother_lastname ?? '' }}</td>
                                <td class="line" style="width: 34%;">{{ $p?->names ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="cap">APELLIDO PATERNO</td>
                                <td class="cap">APELLIDO MATERNO</td>
                                <td class="cap">NOMBRES</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">{{ $sec }}.3 FECHA DE NACIMIENTO:</td>
                    <td class="val">
                        <table class="in"><tr>
                            <td class="cell" style="width: 30%;">{{ $fmt($p?->birthdate) }}</td>
                            <td style="width: 70%;"></td>
                        </tr></table>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">{{ $sec }}.4 DOCUMENTO PRESENTADO:</td>
                    <td class="val">
                        <table class="in"><tr>
                            <td class="opt" style="width: 14%;">DNI {!! $cb(!empty($p?->dni)) !!}</td>
                            <td class="t" style="width: 5%;">Nº</td>
                            <td style="width: 42%;">
                                <table class="dni"><tr>
                                    @foreach($dniArr($p?->dni ?? '') as $c)
                                        <td class="d">{{ trim($c) }}</td>
                                    @endforeach
                                </tr></table>
                            </td>
                            <td class="opt" style="width: 39%; text-align: right;">PARTIDA NACIMIENTO {!! $cb(empty($p?->dni) && $b) !!}</td>
                        </tr></table>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">{{ $sec }}.5 DESNUTRIDO/DISCAPACITADO (CONSTANCIA):</td>
                    <td class="val">
                        <table class="in"><tr>
                            <td class="opt" style="width: 58%;">DESNUTRIDO DE 7 A 13 AÑOS {!! $cb($h?->is_malnourished) !!}</td>
                            <td class="opt" style="width: 42%;">DISCAPACITADO {!! $cb($h?->is_disabled) !!}</td>
                        </tr></table>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">{{ $sec }}.6 MEDIDAS ANTROPOMÉTRICAS:</td>
                    <td class="val">
                        <table class="in">
                            <tr>
                                <td class="line" style="width: 30%;">{{ $h?->weight > 0 ? $h->weight . ' kg' : '' }}</td>
                                <td style="width: 5%;"></td>
                                <td class="line" style="width: 30%;">{{ $h?->height > 0 ? $h->height . ' cm' : '' }}</td>
                                <td style="width: 5%;"></td>
                                <td class="line" style="width: 30%;">{{ $p?->gender ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="cap">PESO</td>
                                <td></td>
                                <td class="cap">TALLA</td>
                                <td></td>
                                <td class="cap">SEXO (M/F)</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach

    {{-- FIRMAS --}}
    <table style="border: 1.5px solid #000;">
        <tr>
            <td style="width: 38%; height: 76px; padding: 3px 6px; vertical-align: top; border-right: 1px solid #000;">
                <div style="font-size: 6.5pt;">FECHA: {{ $fmt($partner?->date_begin) ?: ($partner ? date('d/m/Y') : '') }}</div>
                <div style="border-bottom: 1px solid #000; width: 80%; margin: 40px auto 2px auto;"></div>
                <div style="font-weight: bold; font-size: 6.5pt; text-align: center;">FIRMA SOCIA</div>
            </td>
            <td style="width: 20%; height: 76px; padding: 3px 6px; text-align: center; vertical-align: bottom; border-right: 1px solid #000;">
                <div style="border: 1px solid #000; width: 55px; height: 52px; margin: 0 auto 3px auto;"></div>
                <div style="font-weight: bold; font-size: 6.5pt;">HUELLA</div>
            </td>
            <td style="width: 42%; height: 76px; padding: 3px 6px; vertical-align: top;">
                <div style="font-size: 6.5pt;">FECHA: {{ $partner ? date('d/m/Y') : '' }}</div>
                <div style="border-bottom: 1px solid #000; width: 80%; margin: 40px auto 2px auto;"></div>
                <div style="font-weight: bold; font-size: 6.5pt; text-align: center;">NOMBRE, FIRMA Y SELLO DE LA PRESIDENTA</div>
            </td>
        </tr>
    </table>
</body>
</html>
