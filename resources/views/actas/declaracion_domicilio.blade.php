<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Declaración Jurada de Domicilio - Programa Vaso de Leche</title>
<style>
  @page { margin: 40px 50px; }
  body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 13px; line-height: 1.7; margin: 0; }
  .header { width: 100%; border: 2px solid #000; margin-bottom: 26px; border-collapse: collapse; }
  .header td { padding: 8px 12px; vertical-align: middle; }
  .header .logo { width: 90px; text-align: center; }
  .header .logo img { width: 70px; }
  .header .titles { text-align: center; }
  .header h1 { font-size: 14px; letter-spacing: 1px; margin: 0 0 2px; text-transform: uppercase; }
  .header h2 { font-size: 11px; margin: 0; font-weight: normal; letter-spacing: 1px; }
  .title { text-align: center; font-size: 18px; font-weight: bold; letter-spacing: 2px; margin-bottom: 26px; }
  p { text-align: justify; margin: 0 0 14px; }
  .signature { margin-top: 90px; text-align: center; }
  .signature .line { width: 260px; border-top: 1px solid #000; margin: 0 auto 4px; }
  .fingerprint { float: right; width: 80px; height: 100px; border: 1px solid #000; margin-top: -30px; }
  .fingerprint-label { font-size: 9px; text-align: center; margin-top: 104px; clear: both; }
</style>
</head>
<body>
  <table class="header">
    <tr>
      <td class="logo"><img src="{{ public_path('img/muni2.png') }}" alt="Municipalidad Distrital de La Esperanza"></td>
      <td class="titles">
        <h1>Municipalidad Distrital de La Esperanza</h1>
        <h2>Programa Vaso de Leche</h2>
      </td>
      <td class="logo"></td>
    </tr>
  </table>

  <div class="title">DECLARACIÓN JURADA DE DOMICILIO</div>

  <p>
    Yo, <strong>{{ $partnerName ?: '____________________________________' }}</strong>,
    identificada con DNI N° <strong>{{ $partnerDni ?: '____________' }}</strong>,
    socia del Comité <strong>{{ $committeeName ?: '____________________' }}</strong>
    @if($committeeCode) (código {{ $committeeCode }}) @endif
    del Programa Vaso de Leche de la Municipalidad Distrital de La Esperanza,
    DECLARO BAJO JURAMENTO que mi domicilio actual se encuentra ubicado en:
  </p>

  <p style="text-align:center; font-weight:bold; border-bottom:1px solid #000; padding-bottom:4px;">
    {{ $partnerAddress ?: ' ' }}
  </p>
  <p style="border-bottom:1px solid #000; height:20px;">&nbsp;</p>

  <p>
    Distrito de La Esperanza, provincia de Trujillo, departamento de La Libertad.
    Declaro que la información consignada es veraz, y me someto a las sanciones
    previstas en la Ley N° 27444 y el Código Penal en caso de falsedad.
  </p>

  <p>La Esperanza, {{ $dateLong ?: '____ de ______________ de ________' }}.</p>

  <div class="fingerprint"></div>
  <div class="signature">
    <div class="line"></div>
    <strong>{{ $partnerName ?: 'Nombres y apellidos' }}</strong><br>
    DNI N° {{ $partnerDni }}
  </div>
  <div class="fingerprint-label">Huella digital</div>
</body>
</html>
