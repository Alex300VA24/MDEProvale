@extends('layouts.main')

@section('title', 'Repartición - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-clipboard-list text-leaf"></i> Repartición - {{ $currentYear }}
        </h3>
        <div class="flex gap-3">
            <a href="{{ route('movimientos.reparticion') }}" target="_blank" class="btn-primary flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Generar PDF
            </a>
            <a href="{{ route('movimientos.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="px-6 py-4 bg-cream border-b border-wheat">
        <div class="flex gap-8 text-sm">
            <div>
                <span class="font-bold text-earth">Período:</span>
                <span class="text-charcoal">01 al {{ sprintf('%02d', $daysInMonth) }} de {{ date('F') }} del {{ $currentYear }}</span>
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

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center font-bold text-earth">N°</th>
                    <th class="px-4 py-3 text-left font-bold text-earth">Club de Madres</th>
                    <th class="px-4 py-3 text-left font-bold text-earth">Presidenta</th>
                    <th class="px-4 py-3 text-left font-bold text-earth">Dirección</th>
                    <th class="px-4 py-3 text-center font-bold text-earth">Benef.</th>
                    <th class="px-4 py-3 text-center font-bold text-earth bg-leaf-light text-leaf">Leche (L)</th>
                    <th class="px-4 py-3 text-center font-bold text-earth bg-leaf-light text-leaf">Redondeado</th>
                    <th class="px-4 py-3 text-center font-bold text-earth bg-sun-light text-[#D97706]">Hojuelas (kg)</th>
                    <th class="px-4 py-3 text-center font-bold text-earth bg-sun-light text-[#D97706]">Redondeado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($associations as $index => $club)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-center font-mono">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $club['nombre'] }}</td>
                    <td class="px-4 py-3">{{ $club['presidenta'] }}</td>
                    <td class="px-4 py-3 text-earth">{{ $club['direccion'] }}</td>
                    <td class="px-4 py-3 text-center font-bold">{{ $club['beneficiarios'] }}</td>
                    <td class="px-4 py-3 text-center font-bold text-leaf">{{ number_format($club['leche_litros'], 2) }}</td>
                    <td class="px-4 py-3 text-center">{{ round($club['leche_litros']) }}</td>
                    <td class="px-4 py-3 text-center font-bold text-[#D97706]">{{ number_format($club['hojuelas_kg'], 2) }}</td>
                    <td class="px-4 py-3 text-center">{{ round($club['hojuelas_kg']) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        No hay comités con beneficiarios activos
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-100 font-bold">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-right">TOTALES:</td>
                    <td class="px-4 py-3 text-center">{{ $totalBeneficiarios }}</td>
                    <td class="px-4 py-3 text-center text-leaf">{{ number_format($totalLecheLitros, 2) }}</td>
                    <td class="px-4 py-3 text-center">{{ round($totalLecheLitros) }}</td>
                    <td class="px-4 py-3 text-center text-[#D97706]">{{ number_format($totalHojuelasKg, 2) }}</td>
                    <td class="px-4 py-3 text-center">{{ round($totalHojuelasKg) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
