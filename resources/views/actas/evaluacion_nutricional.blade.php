<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta de Evaluación Nutricional - Programa Vaso de Leche</title>
<style>
  /* Mismo margen que el Acta de Reuniones: encabezado fijo en cada hoja. */
  @page {
    margin-top: 150px;
    margin-right: 24px;
    margin-bottom: 40px;
    margin-left: 24px;
  }
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; font-size: 10px; margin: 0; }
  table { border-collapse: collapse; }

  .page-header { position: fixed; top: -138px; left: 0; right: 0; height: 134px; padding: 6px 4px 0; }
  .head-table { width: 100%; }
  .head-table td { vertical-align: top; }
  .logo-cell { width: 66px; text-align: center; }
  .logo-cell img { width: 60px; height: auto; }
  .muni-text { width: 150px; font-size: 8.5px; line-height: 1.3; text-transform: uppercase; padding-left: 6px; }
  .title-block { text-align: center; }
  .title-block .title { font-size: 17px; font-weight: bold; line-height: 1.2; }
  .title-block .sub { font-size: 10px; margin-top: 3px; }
  .meta-cell { width: 110px; text-align: right; font-size: 8.5px; }
  .pagina-box { font-weight: bold; }
  .pagina-box .num:after { content: counter(page); }
  .generado { font-style: italic; color: #555; margin-top: 6px; }

  .datos { width: 100%; margin-top: 10px; }
  .datos td { font-size: 10.5px; padding: 2px 0; vertical-align: bottom; }
  .datos .lbl { white-space: nowrap; font-weight: bold; padding-right: 5px; }
  .datos .val { border-bottom: 1px dotted #000; padding-right: 10px; }
  .fecha-box { display: inline-block; border: 1px solid #000; width: 110px; height: 18px; vertical-align: middle; }

  table.roster { width: 100%; }
  table.roster th, table.roster td { border: 1px solid #000; padding: 3px 4px; text-align: center; vertical-align: middle; }
  table.roster thead th { background: #f2f2f2; font-size: 9px; text-transform: uppercase; }
  table.roster td.name { text-align: left; font-weight: 600; }
  table.roster td.blank { height: 24px; }
  .x { font-weight: bold; }

  .signatures { width: 100%; margin-top: 26px; page-break-inside: avoid; }
  .signatures td { width: 50%; text-align: center; padding: 0 40px; vertical-align: top; }
  .sign-line { border-bottom: 1px solid #000; height: 40px; margin-bottom: 4px; }
  .sign-role { font-weight: bold; font-size: 10px; }
</style>
</head>
<body>

  <div class="page-header">
    <table class="head-table">
      <tr>
        <td class="logo-cell">
          <img src="{{ public_path('img/muni2.png') }}" alt="Municipalidad Distrital de La Esperanza">
        </td>
        <td class="muni-text">
          Municipalidad<br>
          Distrital de La Esperanza<br>
          Programa Vaso de Leche
        </td>
        <td class="title-block">
          <div class="title">ACTA DE EVALUACI&Oacute;N NUTRICIONAL</div>
          <div class="sub">Control de peso y talla de los beneficiarios del Club de Madres</div>
        </td>
        <td class="meta-cell">
          <div class="pagina-box">P&Aacute;GINA <span class="num"></span></div>
          <div class="generado">{{ $generado }}</div>
        </td>
      </tr>
    </table>
    <table class="datos">
      <tr>
        <td class="lbl">Club de Madres:</td>
        <td class="val" style="width:42%">{{ $association->code }} &ndash; {{ mb_strtoupper($association->name, 'UTF-8') }}</td>
        <td class="lbl">Sector:</td>
        <td class="val">{{ $sector }}</td>
        <td class="lbl" style="text-align:right">Fecha:</td>
        <td style="width:120px"><span class="fecha-box"></span></td>
      </tr>
      <tr>
        <td class="lbl">Presidenta:</td>
        <td class="val">{{ mb_strtoupper($presidenta, 'UTF-8') }}</td>
        <td class="lbl">Beneficiarios:</td>
        <td class="val">{{ count($filas) }}</td>
        <td></td><td></td>
      </tr>
    </table>
  </div>

  <table class="roster">
    <thead>
      <tr>
        <th rowspan="2" style="width:24px">N&deg;</th>
        <th rowspan="2">Apellidos y nombres</th>
        <th rowspan="2" style="width:62px">DNI</th>
        <th rowspan="2" style="width:22px">Sexo</th>
        <th rowspan="2" style="width:58px">F. nacimiento</th>
        <th rowspan="2" style="width:46px">Edad</th>
        <th rowspan="2" style="width:56px">Parentesco</th>
        <th colspan="2">Ficha</th>
        <th colspan="2">Evaluaci&oacute;n actual</th>
        <th rowspan="2" style="width:54px">Estado nutricional</th>
        <th rowspan="2" style="width:80px">Observaciones</th>
      </tr>
      <tr>
        <th style="width:40px">Peso (kg)</th>
        <th style="width:40px">Talla (cm)</th>
        <th style="width:44px">Peso (kg)</th>
        <th style="width:44px">Talla (cm)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($filas as $f)
        <tr>
          <td>{{ $f['numero'] }}</td>
          <td class="name">
            {{ $f['name'] }}
            @if($f['is_malnourished']) <span class="x">(DESNUTRIDO)</span> @endif
            @if($f['is_disabled']) <span class="x">(DISCAPACITADO)</span> @endif
          </td>
          <td>{{ $f['dni'] }}</td>
          <td>{{ $f['gender'] }}</td>
          <td>{{ $f['birthdate'] }}</td>
          <td>{{ $f['age'] }}</td>
          <td>{{ mb_strtoupper($f['relationship'], 'UTF-8') }}</td>
          <td>{{ $f['weight'] ?? '' }}</td>
          <td>{{ $f['height'] ?? '' }}</td>
          <td class="blank"></td>
          <td class="blank"></td>
          <td class="blank"></td>
          <td class="blank"></td>
        </tr>
      @empty
        <tr><td colspan="13">Sin beneficiarios registrados en este comit&eacute;.</td></tr>
      @endforelse
    </tbody>
  </table>

  <table class="signatures">
    <tr>
      <td>
        <div class="sign-line"></div>
        <div class="sign-role">FIRMA DEL EVALUADOR &ndash; MDE.</div>
      </td>
      <td>
        <div class="sign-line"></div>
        <div class="sign-role">FIRMA DE LA PRESIDENTA DEL CLUB DE MADRES</div>
      </td>
    </tr>
  </table>

</body>
</html>
