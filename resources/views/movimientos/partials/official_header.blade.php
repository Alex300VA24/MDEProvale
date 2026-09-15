@php
    $officialIssuedAt = $issuedAt ?? now();
@endphp
<table class="official-document-header" style="width: 100%; border-collapse: collapse; border-spacing: 0; margin-bottom: 7px; color: #000; font-family: Arial, sans-serif;">
    <tr>
        <td style="width: 150px; border: 0; padding: 0; text-align: left; vertical-align: middle;">
            <img src="{{ public_path('img/muni2.png') }}"
                style="width: 50px; height: auto; vertical-align: middle; margin-right: 5px;"
                alt="Logo de la Municipalidad Distrital de La Esperanza">
            <div style="display: inline-block; width: 85px; vertical-align: middle; text-align: left; line-height: 1.2;">
                <div style="font-size: 6pt; font-weight: bold;">MUNICIPALIDAD DISTRITAL</div>
                <div style="font-size: 6pt; font-weight: bold;">DE LA ESPERANZA</div>
                <div style="font-size: 6pt;">O.F. Vaso de Leche</div>
            </div>
        </td>
        <td style="border: 0; padding: 0 8px; text-align: center; vertical-align: middle; line-height: 1.2;">
            <div style="font-size: 11pt; font-weight: bold; text-transform: uppercase;">{{ $headerTitle }}</div>
            @if(!empty($headerSubtitle))
                <div style="font-size: 8pt; font-weight: bold; margin-top: 2px; text-transform: uppercase;">{{ $headerSubtitle }}</div>
            @endif
        </td>
        <td style="width: 125px; border: 0; padding: 0; text-align: right; vertical-align: top; font-size: 7pt; line-height: 1.45; white-space: nowrap;">
            <div>FECHA: {{ $officialIssuedAt->format('d/m/Y') }}</div>
            <div>HORA: {{ $officialIssuedAt->format('H:i:s') }}</div>
        </td>
    </tr>
</table>
