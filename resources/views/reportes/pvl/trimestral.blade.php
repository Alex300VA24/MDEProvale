<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe trimestral PVL</title>
    <style>
        @page { margin: 15mm 20mm 20mm 25mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.35; }
        .lh { width: 100%; border-collapse: collapse; margin-bottom: 7mm; }
        .lh td { border: 0; padding: 0; vertical-align: middle; }
        .lh .logo { width: 24mm; }
        .lh img { width: 22mm; }
        .lh .txt { text-align: center; }
        .lh .muni { font-size: 15pt; font-weight: bold; white-space: nowrap; }
        .lh .small { font-size: 8pt; }
        .lh .area { font-size: 12pt; margin-top: 4mm; }
        .lh .prog { font-size: 9pt; font-weight: bold; }
        .title { font-weight: bold; text-decoration: underline; margin: 6mm 0 6mm; }
        table.meta { width: 100%; border-collapse: collapse; }
        table.meta td { border: 0; padding: 1.2mm 0; vertical-align: top; }
        table.meta .l { width: 32mm; }
        table.meta .c { width: 8mm; }
        .rule { border-top: 1px dashed #000; margin: 2mm 0 5mm; }
        p { margin: 0 0 3.5mm; text-align: justify; }
        h2 { margin: 6mm 0 2.5mm; font-size: 12pt; font-weight: bold; page-break-after: avoid; }
        ol { margin: 0 0 3.5mm 0; padding-left: 7mm; }
        li { margin-bottom: 1.5mm; text-align: justify; }
        .cap { font-size: 10pt; font-weight: bold; margin: 3mm 0 1.5mm; page-break-after: avoid; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
        table.data th, table.data td { border: .6pt solid #000; padding: 1.2mm 1.6mm; font-size: 9pt; text-align: left; }
        table.data th { font-weight: bold; }
        table.data .r { text-align: right; }
        table.data tr.total td { font-weight: bold; }
        table.data tr { page-break-inside: avoid; }
        .fig { text-align: center; margin: 2mm 0 4mm; page-break-inside: avoid; }
        .fig img { width: 135mm; }
        .fig .fc { font-size: 9pt; font-style: italic; }
        .sign { width: 70mm; margin: 22mm auto 0; text-align: center; page-break-inside: avoid; }
        .sign .line { border-top: .8pt solid #000; padding-top: 1.5mm; }
        .sign strong, .sign span { display: block; }
        .note { font-size: 10pt; margin-top: 6mm; }
    </style>
</head>
<body>
<table class="lh"><tr>
    <td class="logo">@if (is_file($logo))<img src="{{ $logo }}" alt="">@endif</td>
    <td class="txt">
        <div class="muni">{{ $letterhead['municipalidad'] }}</div>
        <div class="small">{{ $letterhead['creacion'] }}</div>
        <div class="small">{{ $letterhead['telefono'] }}</div>
        <div class="area">{{ $letterhead['area'] }}</div>
        <div class="prog">{{ $letterhead['programa'] }}</div>
    </td>
</tr></table>

@foreach ($blocks as $b)
    @switch($b['type'])
        @case('title') <div class="title">{{ $b['text'] }}</div> @break
        @case('meta')
            <table class="meta">
                @foreach ($b['rows'] as [$label, $lines])
                    <tr><td class="l">{{ $label }}</td><td class="c">:</td><td>
                        @foreach ($lines as [$text, $bold])
                            @if ($bold)<strong>{{ $text }}</strong>@else{{ $text }}@endif<br>
                        @endforeach
                    </td></tr>
                @endforeach
            </table>
            @break
        @case('rule') <div class="rule"></div> @break
        @case('h') <h2>{{ $b['text'] }}</h2> @break
        @case('p') <p>{{ $b['text'] }}</p> @break
        @case('ol') <ol>@foreach ($b['items'] as $item)<li>{{ $item }}</li>@endforeach</ol> @break
        @case('table')
            @if (!empty($b['caption']))<div class="cap">{{ $b['caption'] }}</div>@endif
            <table class="data">
                <thead><tr>@foreach ($b['head'] as $i => $h)<th class="{{ $b['align'][$i] === 'r' ? 'r' : '' }}">{{ $h }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($b['rows'] as $row)<tr>@foreach ($row as $i => $v)<td class="{{ $b['align'][$i] === 'r' ? 'r' : '' }}">{{ $v }}</td>@endforeach</tr>@endforeach
                    @if (!empty($b['total']))<tr class="total">@foreach ($b['total'] as $i => $v)<td class="{{ $b['align'][$i] === 'r' ? 'r' : '' }}">{{ $v }}</td>@endforeach</tr>@endif
                </tbody>
            </table>
            @break
        @case('image')
            @if (isset($charts[$b['key']]))<div class="fig"><img src="{{ $charts[$b['key']] }}" alt=""><div class="fc">{{ $b['caption'] }}</div></div>@endif
            @break
        @case('sign') <div class="sign"><div class="line"></div><strong>{{ $b['name'] }}</strong><span>{{ $b['role'] }}</span></div> @break
        @case('note') <div class="note">{{ $b['text'] }}</div> @break
    @endswitch
@endforeach
</body>
</html>
