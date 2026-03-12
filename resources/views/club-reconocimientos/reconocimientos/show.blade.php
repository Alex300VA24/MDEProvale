@extends('layouts.main')

@section('title', 'Ver Resolución - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-contract text-leaf"></i> Detalle de Resolución
        </h3>
        <div class="flex gap-3">
            <a href="{{ route('club-reconocimientos.reconocimientos.edit', $resolution) }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="{{ route('club-reconocimientos.reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="p-4 bg-gray-50 rounded-xl border-2 border-wheat">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Documento</label>
                <p class="text-sm font-semibold text-charcoal">{{ $resolution->document }}</p>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border-2 border-wheat">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Emisión</label>
                <p class="text-sm font-semibold text-charcoal">{{ \Carbon\Carbon::parse($resolution->date_document)->format('d/m/Y') }}</p>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border-2 border-wheat">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Vigencia Inicio</label>
                <p class="text-sm font-semibold text-charcoal">{{ \Carbon\Carbon::parse($resolution->date_start)->format('d/m/Y') }}</p>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border-2 border-wheat">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Vigencia Fin</label>
                <p class="text-sm font-semibold text-charcoal">{{ \Carbon\Carbon::parse($resolution->date_end)->format('d/m/Y') }}</p>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border-2 border-wheat">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                <span class="px-3 py-1 text-xs font-bold rounded-full {{ $resolution->state->abbreviation == 'A' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $resolution->state->title ?? 'N/A' }}
                </span>
            </div>
        </div>

        @if($resolution->associations->count() > 0)
            <div class="mt-8">
                <h4 class="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                    <i class="fas fa-users text-leaf"></i> Comités Asociados
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-wheat/30 border-b-2 border-wheat">
                                <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Dirección</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-wheat">
                            @foreach($resolution->associations as $association)
                                <tr class="hover:bg-wheat/10 transition-colors">
                                    <td class="px-4 py-3 text-sm font-semibold text-charcoal">{{ $association->code }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-charcoal">{{ $association->name }}</td>
                                    <td class="px-4 py-3 text-sm text-charcoal">{{ $association->address }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 text-xs font-bold rounded-full {{ $association->state->abbreviation == 'ACTI' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $association->state->title ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
