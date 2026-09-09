<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta de Renuncia - Programa Vaso de Leche</title>
<style>
  @page { margin: 22px 26px; }
  * { box-sizing: border-box; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    color: #111;
    font-size: 12px;
    margin: 0;
  }
  table { border-collapse: collapse; }

  .header {
    width: 100%;
    border: 2px solid #000;
    border-radius: 8px;
    margin-bottom: 16px;
  }
  .header td { vertical-align: middle; padding: 8px 12px; }
  .header .logo-cell { width: 90px; text-align: center; }
  .header .logo-cell img { width: 70px; height: auto; }
  .header .titles { text-align: center; }
  .header .titles h1 {
    font-size: 14px;
    letter-spacing: 1px;
    margin: 0 0 2px;
    text-transform: uppercase;
  }
  .header .titles h2 {
    font-size: 11px;
    margin: 0;
    font-weight: normal;
    letter-spacing: 1px;
  }

  .main-title {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    letter-spacing: 3px;
    border: 2px solid #000;
    border-radius: 6px;
    padding: 6px;
    margin-bottom: 18px;
  }

  .fields { width: 100%; }
  .fields td { padding: 5px 0; vertical-align: middle; }
  .fields .lbl {
    font-weight: bold;
    font-size: 12px;
    white-space: nowrap;
    width: 200px;
    padding-right: 10px;
  }
  .box {
    border: 1px solid #000;
    height: 26px;
  }
  .box-cell { padding-left: 0; }

  .fecha-tbl td {
    padding: 0;
    text-align: center;
  }
  .fecha-tbl .digit {
    width: 22px;
    height: 26px;
    border: 1px solid #000;
  }
  .fecha-tbl .sep { width: 14px; font-weight: bold; }
  .fecha-tbl .cap {
    font-size: 8px;
    padding-top: 1px;
  }
  .fecha-lbl {
    font-weight: bold;
    font-size: 12px;
    padding: 0 8px 0 18px;
    white-space: nowrap;
  }

  .motivos-title {
    font-weight: bold;
    font-size: 12px;
    margin: 16px 0 6px;
  }
  .motivos { width: 100%; }
  .motivos td {
    border: 1px solid #000;
    padding: 7px 10px;
    height: 24px;
  }
  .motivos td.num { width: 34px; font-weight: bold; }

  .conformidad {
    text-align: center;
    font-weight: bold;
    font-size: 12px;
    margin: 18px 0 8px;
  }

  .signatures { width: 100%; margin-bottom: 16px; }
  .signatures th {
    border: 1px solid #000;
    background: #f2f2f2;
    padding: 6px;
    font-size: 11px;
    letter-spacing: 1px;
  }
  .signatures td {
    border: 1px solid #000;
    padding: 10px 12px;
    vertical-align: top;
    width: 50%;
    font-size: 11px;
  }
  .signatures .cm { margin: 0 0 6px; }
  .sign-space {
    height: 58px;
    border-bottom: 1px solid #000;
    margin: 6px 0;
  }
  .sign-role {
    text-align: center;
    font-weight: bold;
    font-size: 11px;
    letter-spacing: 1px;
    margin: 0 0 8px;
  }
  .dni-line { font-size: 11px; }
  .dni-line .fill {
    display: inline-block;
    border-bottom: 1px solid #000;
    width: 150px;
  }

  .observaciones {
    border: 1px solid #000;
    padding: 8px 12px 10px;
  }
  .observaciones .obs-title { font-weight: bold; font-size: 12px; }
  .observaciones .obs-title small { font-weight: normal; font-size: 10px; }
  .obs-lines { margin-top: 8px; }
  .obs-lines div {
    border-bottom: 1px solid #999;
    height: 20px;
  }
</style>
</head>
<body>

  <table class="header">
    <tr>
      <td class="logo-cell"><img src="{{ public_path('img/muni2.png') }}" alt="Municipalidad Distrital de La Esperanza"></td>
      <td class="titles">
        <h1>Municipalidad Distrital de La Esperanza</h1>
        <h2>Programa Vaso de Leche</h2>
      </td>
      <td class="logo-cell"></td>
    </tr>
  </table>

  <div class="main-title">ACTA DE RENUNCIA</div>

  <table class="fields">
    <tr>
      <td class="lbl">SIENDO LAS:</td>
      <td class="box-cell">
        <table style="width:100%">
          <tr>
            <td style="width:150px"><div class="box"></div></td>
            <td class="fecha-lbl" style="text-align:right">CON FECHA:</td>
            <td style="width:270px">
              <table class="fecha-tbl">
                <tr>
                  <td class="digit"></td><td class="digit"></td>
                  <td class="sep">/</td>
                  <td class="digit"></td><td class="digit"></td>
                  <td class="sep">/</td>
                  <td class="digit"></td><td class="digit"></td><td class="digit"></td><td class="digit"></td>
                </tr>
                <tr>
                  <td class="cap" colspan="2">día</td>
                  <td></td>
                  <td class="cap" colspan="2">mes</td>
                  <td></td>
                  <td class="cap" colspan="4">año</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class="lbl">LA SEÑORA:</td>
      <td class="box-cell"><div class="box"></div></td>
    </tr>
    <tr>
      <td class="lbl">DEL CLUB DE MADRES:</td>
      <td class="box-cell"><div class="box"></div></td>
    </tr>
    <tr>
      <td class="lbl">IDENTIFICADA CON DNI N°:</td>
      <td class="box-cell"><div class="box"></div></td>
    </tr>
    <tr>
      <td class="lbl">DOMICILIADA EN:</td>
      <td class="box-cell"><div class="box"></div></td>
    </tr>
    <tr>
      <td class="lbl">RENUNCIA A SER:</td>
      <td class="box-cell"><div class="box"></div></td>
    </tr>
  </table>

  <div class="motivos-title">POR LOS SIGUIENTES MOTIVOS:</div>
  <table class="motivos">
    <tr><td class="num">1-</td><td>&nbsp;</td></tr>
    <tr><td class="num">2-</td><td>&nbsp;</td></tr>
    <tr><td class="num">3-</td><td>&nbsp;</td></tr>
    <tr><td class="num">4-</td><td>&nbsp;</td></tr>
  </table>

  <div class="conformidad">FIRMAN EN SEÑAL DE CONFORMIDAD A LO ESCRITO EN LA PRESENTE ACTA.</div>

  <table class="signatures">
    <tr>
      <th>RENUNCIANTE</th>
      <th>PRESIDENTA (SELLO Y FIRMA)</th>
    </tr>
    <tr>
      <td>
        <p class="cm">C.M. "________________________"</p>
        <div class="sign-space"></div>
        <p class="sign-role">RENUNCIANTE</p>
        <div class="dni-line">DNI N°: <span class="fill"></span></div>
      </td>
      <td>
        <p class="cm">C.M. "________________________"</p>
        <div class="sign-space"></div>
        <p class="sign-role">PRESIDENTA</p>
        <div class="dni-line">DNI N°: <span class="fill"></span></div>
      </td>
    </tr>
  </table>

  <div class="observaciones">
    <div class="obs-title">OBSERVACIONES: <small>(Para ser llenado solo por PROVALE)</small></div>
    <div class="obs-lines">
      <div></div>
      <div></div>
      <div></div>
      <div></div>
    </div>
  </div>

</body>
</html>
