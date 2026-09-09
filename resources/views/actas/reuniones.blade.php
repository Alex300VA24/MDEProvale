<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta de Reuniones - Club de Madres</title>
<style>
  /* Margen superior reserva el espacio del encabezado fijo que se repite en
     todas las hojas. */
  @page {
    margin-top: 168px;
    margin-right: 24px;
    margin-bottom: 40px;
    margin-left: 24px;
  }
  * { box-sizing: border-box; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    color: #1a1a1a;
    font-size: 11px;
    margin: 0;
  }
  table { border-collapse: collapse; }

  /* Encabezado fijo: DomPDF lo pinta en cada pagina. El `top` negativo lo
     empuja hacia el margen superior de la pagina para que no invada la tabla. */
  .page-header {
    position: fixed;
    top: -152px;
    left: 0;
    right: 0;
    height: 152px;
    padding: 8px 20px 0;
  }
  .head-table { width: 100%; }
  .head-table td { vertical-align: top; }
  .logo-cell { width: 66px; text-align: center; }
  .logo-cell img { width: 60px; height: auto; }
  .muni-text {
    width: 112px;
    font-size: 8.5px;
    line-height: 1.3;
    text-transform: uppercase;
    padding-left: 6px;
  }
  .title-block { text-align: center; }
  .title-block .title {
    font-size: 21px;
    font-weight: bold;
    line-height: 1.2;
  }

  /* Bloque ASUNTO / FECHA: va debajo del logo, alineado a la izquierda, con
     espacio para escritura manual. */
  .sub-head { width: 100%; margin-top: 22px; }
  .sub-head td { vertical-align: middle; font-size: 12px; font-weight: bold; }
  .sub-head .asunto-cell { width: 68%; }
  .sub-head .fecha-cell { width: 32%; text-align: right; }
  .asunto .fill-line {
    display: inline-block;
    border-bottom: 1px solid #000;
    vertical-align: bottom;
    height: 14px;
    width: 300px;
  }
  /* Recuadro para escribir la fecha a mano (no una linea). */
  .fecha-field .fecha-box {
    display: inline-block;
    border: 1px solid #000;
    width: 140px;
    height: 26px;
    vertical-align: middle;
    margin-left: 4px;
  }
  .meta-cell { width: 78px; text-align: right; font-size: 8.5px; }
  .pagina-box { font-weight: bold; }
  .pagina-box .num {
    margin-left: 3px;
  }
  .pagina-box .num:after { content: counter(page); }
  .generado { font-style: italic; color: #555; margin-top: 6px; }

  /* Roster */
  table.roster { width: 100%; }
  table.roster th, table.roster td {
    border: 1px solid #000;
    padding: 4px 5px;
    text-align: center;
    vertical-align: middle;
  }
  table.roster thead th {
    background: #f2f2f2;
    font-size: 10px;
    text-transform: uppercase;
  }
  table.roster td.club-name { text-align: left; font-weight: 600; }
  table.roster td.presidenta-name { text-align: left; }
  table.roster td.firma-cell { height: 34px; }
  .col-zona { width: 40px; }
  .col-comite { width: 50px; }
  .col-num { width: 30px; }
  .col-dni { width: 90px; }
  .col-firma { width: 120px; }
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
          Of. Vaso de Leche
        </td>
        <td class="title-block">
          <div class="title">Club de Madres del Distrito de La Esperanza</div>
        </td>
        <td class="meta-cell">
          <div class="pagina-box">P&Aacute;GINA <span class="num"></span></div>
          <div class="generado">{{ $generado }}</div>
        </td>
      </tr>
    </table>
    <table class="sub-head">
      <tr>
        <td class="asunto-cell">
          <span class="asunto">ASUNTO: <span class="fill-line"></span></span>
        </td>
        <td class="fecha-cell">
          <span class="fecha-field">FECHA: <span class="fecha-box"></span></span>
        </td>
      </tr>
    </table>
  </div>

  <table class="roster">
    <thead>
      <tr>
        <th colspan="2">C&oacute;digo</th>
        <th rowspan="2" class="col-num">N&deg;</th>
        <th rowspan="2">Nombre del Club de Madres</th>
        <th colspan="2">Presidenta</th>
        <th rowspan="2" class="col-firma">Firma</th>
      </tr>
      <tr>
        <th class="col-zona">Zona</th>
        <th class="col-comite">Comit&eacute;</th>
        <th>Nombres y Apellidos</th>
        <th class="col-dni">N&deg; DNI</th>
      </tr>
    </thead>
    <tbody>
      @forelse($filas as $fila)
        <tr>
          <td class="col-zona">{{ $fila['zona'] }}</td>
          <td class="col-comite">{{ $fila['comite'] }}</td>
          <td class="col-num">{{ $fila['numero'] }}</td>
          <td class="club-name">{{ $fila['club'] }}</td>
          <td class="presidenta-name">{{ $fila['presidenta'] }}</td>
          <td class="col-dni">{{ $fila['dni'] }}</td>
          <td class="col-firma firma-cell"></td>
        </tr>
      @empty
        <tr><td colspan="7">Sin comit&eacute;s registrados.</td></tr>
      @endforelse
    </tbody>
  </table>

</body>
</html>
