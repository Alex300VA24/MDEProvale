@extends('layouts.main')

@section('title', 'Repartición - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat flex-wrap gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-clipboard-list text-leaf"></i> Repartición - {{ $currentYear }}
        </h3>
        <div class="flex flex-wrap gap-3">
            <form method="GET" action="{{ route('movimientos.reparticion-tabla') }}" class="flex flex-wrap gap-2 items-center">
                <select name="year" class="px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold">
                    @for($y = date('Y'); $y >= 2020; $y--)
                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <select name="month" class="px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold">
                    @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $currentMonth == $m ? 'selected' : '' }}>{{ Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-secondary text-xs px-3 py-2">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </form>
            <a href="{{ route('movimientos.reparticion', ['year' => $currentYear, 'month' => $currentMonth]) }}" target="_blank" class="btn-primary flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Generar PDF
            </a>
            <a href="{{ route('movimientos.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="px-4 sm:px-6 py-3 sm:py-4 bg-cream border-b border-wheat">
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-8 text-xs sm:text-sm">
            <div>
                <span class="font-bold text-earth">Período:</span>
                <span class="text-charcoal">01 al {{ sprintf('%02d', $daysInMonth) }} de {{ Carbon\Carbon::create()->month($currentMonth)->format('F') }} del {{ $currentYear }}</span>
            </div>
            <div>
                <span class="font-bold text-earth">Ración Leche:</span>
                <span class="text-charcoal">{{ $racionLecheMl }} ml</span>
            </div>
            <div>
                <span class="font-bold text-earth">Ración Hojuelas:</span>
                <span class="text-charcoal">{{ $racionHojuelasGr }} g</span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="w-full text-xs sm:text-sm min-w-[700px]">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth">N°</th>
                    <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Club de Madres</th>
                    <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Presidenta</th>
                    <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Dirección</th>
                    <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth">Benef.</th>
                    <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth bg-leaf-light text-leaf">Leche</th>
                    <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth">Cajas</th>
                    <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth">Tarros</th>
                    <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth bg-sun-light text-[#D97706]">Hojuelas</th>
                    <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth">Sacos</th>
                    <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth">Kilos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($associations as $index => $club)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 sm:px-4 py-3 text-center font-mono">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-3 sm:px-4 py-3 font-semibold">{{ $club['nombre'] }}</td>
                    <td class="px-3 sm:px-4 py-3">{{ $club['presidenta'] }}</td>
                    <td class="px-3 sm:px-4 py-3 text-earth">{{ $club['direccion'] }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center font-bold">{{ $club['beneficiarios'] }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center font-bold text-leaf">{{ round($club['leche_litros']) }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center">{{ $club['leche_cajas'] ?? 0 }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center">{{ $club['leche_tarros'] ?? 0 }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center font-bold text-[#D97706]">{{ round($club['hojuelas_kg']) }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center">{{ $club['hojuelas_sacos'] ?? 0 }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center">{{ $club['hojuelas_kilos'] ?? 0 }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="px-3 sm:px-4 py-8 text-center text-gray-500">
                        No hay comités con beneficiarios activos
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-100 font-bold">
                <tr>
                    <td colspan="4" class="px-3 sm:px-4 py-3 text-right">TOTALES:</td>
                    <td class="px-3 sm:px-4 py-3 text-center">{{ $totalBeneficiarios }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center text-leaf">{{ round($totalLecheLitros) }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center">{{ intdiv((int) round($totalLecheLitros), 48) }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center">{{ (int) round($totalLecheLitros) % 48 }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center text-[#D97706]">{{ round($totalHojuelasKg) }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center">{{ intdiv((int) round($totalHojuelasKg), 30) }}</td>
                    <td class="px-3 sm:px-4 py-3 text-center">{{ (int) round($totalHojuelasKg) % 30 }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
