@extends('layouts.main')

@section('title', 'Resoluciones - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-contract text-leaf"></i> Resoluciones
        </h3>
        <div class="flex gap-3">
            <a href="{{ route('club-reconocimientos.reconocimientos.reportes') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('club-reconocimientos.reconocimientos.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nueva Resolución
            </a>
            <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="p-6">
        <form method="GET" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por documento..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                    <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los estados</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>
                                {{ $state->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Vigencia</label>
                    <select name="vigencia" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todas</option>
                        <option value="vigentes" {{ request('vigencia') == 'vigentes' ? 'selected' : '' }}>Vigentes</option>
                        <option value="vencidas" {{ request('vigencia') == 'vencidas' ? 'selected' : '' }}>Vencidas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Año</label>
                    <select name="anio" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los años</option>
                        @for($year = date('Y'); $year >= 2020; $year--)
                            <option value="{{ $year }}" {{ request('anio') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search mr-1"></i> Buscar
                </button>
                <a href="{{ route('club-reconocimientos.reconocimientos.index') }}" class="btn-secondary">
                    <i class="fas fa-times mr-1"></i> Limpiar
                </a>
            </div>
        </form>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border-2 border-green-200 rounded-xl text-green-700">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border-2 border-red-200 rounded-xl text-red-700">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        @if(request()->hasAny(['search', 'state_id', 'vigencia', 'anio']))
            <div class="mb-4 p-3 bg-blue-50 border-2 border-blue-200 rounded-xl text-blue-700 text-sm">
                <i class="fas fa-filter mr-2"></i>
                Mostrando {{ $resolutions->total() }} resultado(s) filtrado(s)
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-wheat/30 border-b-2 border-wheat">
                        <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Documento</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Fecha Emisión</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Vigencia</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Comités</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-earth uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat">
                    @forelse($resolutions as $resolution)
                        <tr class="hover:bg-wheat/10 transition-colors">
                            <td class="px-4 py-3 text-sm font-semibold text-charcoal">{{ $resolution->document }}</td>
                            <td class="px-4 py-3 text-sm text-charcoal">{{ \Carbon\Carbon::parse($resolution->date_document)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm text-charcoal">
                                <div class="flex flex-col">
                                    <span>{{ \Carbon\Carbon::parse($resolution->date_start)->format('d/m/Y') }}</span>
                                    <span class="text-xs text-earth">al {{ \Carbon\Carbon::parse($resolution->date_end)->format('d/m/Y') }}</span>
                                </div>
                                @if(\Carbon\Carbon::parse($resolution->date_end)->isFuture())
                                    <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-green-100 text-green-700">
                                        <i class="fas fa-check-circle mr-1"></i>Vigente
                                    </span>
                                @else
                                    <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-700">
                                        <i class="fas fa-clock mr-1"></i>Vencida
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-charcoal">
                                @if($resolution->associations->count() > 0)
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">
                                        <i class="fas fa-users mr-1"></i>{{ $resolution->associations->count() }} comité(s)
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">Sin comités</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-3 py-1 text-xs font-bold rounded-full {{ $resolution->state->abbreviation == 'A' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $resolution->state->title ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('club-reconocimientos.reconocimientos.show', $resolution) }}" class="text-blue-600 hover:text-blue-800" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('club-reconocimientos.reconocimientos.edit', $resolution) }}" class="text-yellow-600 hover:text-yellow-800" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('club-reconocimientos.reconocimientos.destroy', $resolution) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar esta resolución?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>No hay resoluciones registradas</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $resolutions->links() }}
        </div>
    </div>
</div>
@endsection
