<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta de Supervisión - Programa Vaso de Leche</title>
<style>
  @page { margin: 18px 24px; }
  * { box-sizing: border-box; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    color: #111;
    font-size: 10px;
    line-height: 1.25;
    margin: 0;
  }
  table { border-collapse: collapse; width: 100%; }

  /* ---- Header ---- */
  .header td { vertical-align: middle; }
  .header .logo-cell { width: 70px; text-align: center; }
  .header .logo-cell img { width: 58px; height: auto; }
  .header .muni-name { font-style: italic; font-size: 12px; padding-left: 8px; }
  .header .pvl-box {
    border: 2px solid #000;
    border-radius: 5px;
    padding: 5px 10px;
    font-weight: bold;
    font-size: 11px;
    letter-spacing: 1px;
    text-align: center;
    margin-top: 4px;
    margin-left: 8px;
    display: inline-block;
  }
  .cod-box { border: 1px solid #000; width: 165px; }
  .cod-box td {
    padding: 5px 7px;
    font-weight: bold;
    font-size: 10px;
  }
  .cod-box tr:first-child td { border-bottom: 1px solid #000; }

  .main-title {
    text-align: center;
    font-weight: bold;
    font-size: 12px;
    margin: 8px 0 10px;
  }

  .section-title {
    font-weight: bold;
    font-size: 10px;
    margin: 10px 0 4px;
  }

  /* ---- Inline field rows ---- */
  .row { width: 100%; margin-bottom: 3px; }
  .row td { padding: 2px 0; vertical-align: bottom; }
  .lbl { white-space: nowrap; padding-right: 5px; }
  .fill {
    border-bottom: 1px dotted #000;
    height: 12px;
  }
  .fill-b { border-bottom: 1px dotted #000; font-weight: bold; height: 12px; }

  /* ---- SI / NO rows ---- */
  .yn { width: 100%; margin-bottom: 2px; }
  .yn td { padding: 2px 0; vertical-align: middle; }
  .yn .q { padding-right: 10px; }
  .yn .opts { width: 108px; white-space: nowrap; text-align: right; }
  .cbx {
    width: 11px;
    height: 11px;
    border: 1px solid #000;
    display: inline-block;
    vertical-align: middle;
    margin-right: 3px;
  }
  .opts .sp { margin-left: 16px; }

  .dots { border-bottom: 1px dotted #000; height: 13px; margin-bottom: 2px; }

  .concl .underline { text-decoration: underline; }

  /* ---- Signatures ---- */
  .signatures { margin-top: 22px; }
  .signatures td { width: 50%; text-align: center; padding: 0 20px; vertical-align: top; }
  .sign-line { border-bottom: 1px solid #000; height: 34px; margin-bottom: 4px; }
  .sign-role { font-weight: bold; font-size: 10px; margin-bottom: 6px; }
  .sign-detail { text-align: left; font-size: 9px; }
  .sign-detail .u {
    display: inline-block;
    border-bottom: 1px solid #000;
    width: 62%;
  }
</style>
</head>
<body>

  <table class="header">
    <tr>
      <td class="logo-cell"><img src="{{ public_path('img/muni2.png') }}" alt="Municipalidad Distrital de La Esperanza"></td>
      <td>
        <span class="muni-name">Municipalidad Distrital de La Esperanza</span><br>
        <span class="pvl-box">PROGRAMA VASO DE LECHE</span>
      </td>
      <td style="width:165px">
        <table class="cod-box">
          <tr><td>COD:</td></tr>
          <tr><td>N° Beneficiarios:</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <div class="main-title">ACTA DE SUPERVISIÓN A LOS CLUBES DE MADRES Y COMITÉS VASO DE LECHE</div>

  <table class="row">
    <tr>
      <td class="lbl" style="width:70px">Fecha:</td>
      <td class="fill" style="width:32%"></td>
      <td class="lbl" style="width:85px; padding-left:12px">Hora entrada:</td>
      <td class="fill"></td>
      <td class="lbl" style="width:75px; padding-left:12px">Hora salida:</td>
      <td class="fill"></td>
    </tr>
  </table>

  <div class="section-title">DATOS DEL CLUB DE MADRES Y/O COMITÉ VASO DE LECHE</div>
  <table class="row">
    <tr>
      <td class="lbl" style="width:150px">Nombre y/o denominación:</td>
      <td class="fill-b"></td>
    </tr>
  </table>
  <table class="row">
    <tr>
      <td class="lbl" style="width:60px">Dirección:</td>
      <td class="fill-b" style="width:60%"></td>
      <td class="lbl" style="width:50px; padding-left:12px">Sector:</td>
      <td class="fill-b"></td>
    </tr>
  </table>

  <div class="section-title">PREPARACIÓN DEL PRODUCTO:</div>
  <table class="yn">
    <tr>
      <td class="q">Cantidad de leche que utiliza diariamente</td>
      <td style="width:150px"><span class="fill" style="display:inline-block; width:95px"></span> tarros</td>
    </tr>
    <tr>
      <td class="q">Cantidad de avena que utiliza diariamente</td>
      <td style="width:150px"><span class="fill" style="display:inline-block; width:95px"></span> bolsas</td>
    </tr>
    <tr>
      <td class="q">Preparó la mezcla (leche + avena) de acuerdo a la cantidad de beneficiarios</td>
      <td class="opts"><span class="cbx"></span>SI<span class="sp"><span class="cbx"></span>NO</span></td>
    </tr>
    <tr>
      <td class="q">¿Prepara los alimentos en un lugar adecuado?</td>
      <td class="opts"><span class="cbx"></span>SI<span class="sp"><span class="cbx"></span>NO</span></td>
    </tr>
    <tr>
      <td class="q">Cuenta con registro de entrega – recepción de raciones</td>
      <td class="opts"><span class="cbx"></span>SI<span class="sp"><span class="cbx"></span>NO</span></td>
    </tr>
  </table>

  <div class="section-title">DE ENCONTRAR EN EL MOMENTO DE LA SUPERVISIÓN ALGUNA SOCIA Y/O BENEFICIARIO:</div>
  <table class="row">
    <tr>
      <td class="lbl" style="width:120px">Nombres y apellidos:</td>
      <td class="fill" style="width:62%"></td>
      <td class="lbl" style="width:34px; padding-left:12px">DNI:</td>
      <td class="fill"></td>
    </tr>
  </table>
  <table class="yn">
    <tr>
      <td class="q">Se brinda un trato adecuado a los beneficiarios</td>
      <td class="opts"><span class="cbx"></span>SI<span class="sp"><span class="cbx"></span>NO</span></td>
    </tr>
  </table>
  <div style="font-weight:bold; margin:3px 0 2px">Si la respuesta es NO, señale por qué:</div>
  <div class="dots"></div>
  <div class="dots"></div>
  <table class="yn">
    <tr>
      <td class="q">Firma el cuaderno de control diario</td>
      <td class="opts"><span class="cbx"></span>SI<span class="sp"><span class="cbx"></span>NO</span></td>
    </tr>
    <tr>
      <td class="q">Recoge su producto diariamente</td>
      <td class="opts"><span class="cbx"></span>SI<span class="sp"><span class="cbx"></span>NO</span></td>
    </tr>
    <tr>
      <td class="q">Presenta irregularidades en la administración, manejo y funcionamiento</td>
      <td class="opts"><span class="cbx"></span>SI<span class="sp"><span class="cbx"></span>NO</span></td>
    </tr>
  </table>
  <div style="font-weight:bold; margin:3px 0 2px">Si la respuesta es SI, precisar cuál es la irregularidad:</div>
  <div class="dots"></div>

  <div class="section-title">OBSERVACIONES DE PARTE DE LA REPRESENTANTE DEL CLUB DE MADRES:</div>
  <div class="dots"></div>
  <div class="dots"></div>

  <div class="section-title">CONCLUSIÓN DE LA VISITA DE SUPERVISIÓN:</div>
  <table class="yn">
    <tr>
      <td class="q concl">El Club de Madres y/o Comité <span class="underline">cumple con lo regulado</span> en el reglamento del Programa Vaso de Leche y disposiciones normativas de la Municipalidad Distrital de La Esperanza.</td>
      <td class="opts"><span class="cbx"></span>SI<span class="sp"><span class="cbx"></span>NO</span></td>
    </tr>
  </table>

  <div class="section-title">OBSERVACIONES Y RECOMENDACIONES DEL SUPERVISOR:</div>
  <div class="dots"></div>
  <div class="dots"></div>

  <table class="signatures">
    <tr>
      <td>
        <div class="sign-line"></div>
        <div class="sign-role">FIRMA DEL SUPERVISOR – MDE.</div>
        <div class="sign-detail">
          Nombres y Apellidos: <span class="u"></span><br><br>
          DNI: <span class="u"></span>
        </div>
      </td>
      <td>
        <div class="sign-line"></div>
        <div class="sign-role">FIRMA DE LA RESP. DE LA PREPARACIÓN</div>
        <div class="sign-detail">
          Nombres y Apellidos: <span class="u"></span><br><br>
          DNI: <span class="u"></span>
        </div>
      </td>
    </tr>
  </table>

</body>
</html>
