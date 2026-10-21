<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Declaración Jurada de Parentesco - Programa Vaso de Leche</title>
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
  table.list { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
  table.list th, table.list td { border: 1px solid #000; padding: 6px 8px; font-size: 12px; line-height: 1.3; }
  table.list th { background: #f2f2f2; }
  .signature { margin-top: 80px; text-align: center; }
  .signature .line { width: 260px; border-top: 1px solid #000; margin: 0 auto 4px; }
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

  <div class="title">DECLARACIÓN JURADA DE PARENTESCO</div>

  <p>
    Yo, <strong>{{ $partnerName ?: '____________________________________' }}</strong>,
    identificada con DNI N° <strong>{{ $partnerDni ?: '____________' }}</strong>,
    socia del Comité <strong>{{ $committeeName ?: '____________________' }}</strong>
    del Programa Vaso de Leche de la Municipalidad Distrital de La Esperanza,
    DECLARO BAJO JURAMENTO que mantengo el siguiente parentesco con los beneficiarios que registro:
  </p>

  <table class="list">
    <thead>
      <tr><th style="width:30px">N°</th><th>Apellidos y nombres</th><th style="width:90px">DNI</th><th style="width:130px">Parentesco</th></tr>
    </thead>
    <tbody>
      @forelse($beneficiaries as $i => $beneficiary)
        <tr>
          <td style="text-align:center">{{ $i + 1 }}</td>
          <td>{{ $beneficiary['name'] }}</td>
          <td>{{ $beneficiary['dni'] }}</td>
          <td>{{ $beneficiary['relationship'] }}</td>
        </tr>
      @empty
        @for($i = 1; $i <= 5; $i++)
          <tr><td style="text-align:center">{{ $i }}</td><td>&nbsp;</td><td></td><td></td></tr>
        @endfor
      @endforelse
    </tbody>
  </table>

  <p>
    Declaro que la información consignada es veraz, y me someto a las sanciones
    previstas en la Ley N° 27444 y el Código Penal en caso de falsedad.
  </p>

  <p>La Esperanza, {{ $dateLong ?: '____ de ______________ de ________' }}.</p>

  <div class="signature">
    <div class="line"></div>
    <strong>{{ $partnerName ?: 'Nombres y apellidos' }}</strong><br>
    DNI N° {{ $partnerDni }}
  </div>
</body>
</html>
