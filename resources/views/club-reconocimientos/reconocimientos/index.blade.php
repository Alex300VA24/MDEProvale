@extends('layouts.main')

@section('title', 'Resoluciones - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-contract text-leaf"></i> Resoluciones
        </h3>
        <div class="flex gap-3">
            <button onclick="openModal('modal-crear-resolucion')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nueva Resolución
            </button>
            <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="p-6">
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
                            <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
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
                            <option value="{{ $year }}" {{ request('anio') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn-primary"><i class="fas fa-search mr-1"></i> Buscar</button>
                <a href="{{ route('club-reconocimientos.reconocimientos.index') }}" class="btn-secondary"><i class="fas fa-times mr-1"></i> Limpiar</a>
            </div>
        </form>

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
                                <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-green-100 text-green-700"><i class="fas fa-check-circle mr-1"></i>Vigente</span>
                            @else
                                <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-700"><i class="fas fa-clock mr-1"></i>Vencida</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-charcoal">
                            @if($resolution->associations->count() > 0)
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold"><i class="fas fa-users mr-1"></i>{{ $resolution->associations->count() }} comité(s)</span>
                            @else
                                <span class="text-gray-400 text-xs">Sin comités</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 text-xs font-bold rounded-full {{ $resolution->state && $resolution->state->abbreviation == 'A' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $resolution->state->title ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openModal('modal-ver-resolucion-{{ $resolution->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="openModal('modal-editar-resolucion-{{ $resolution->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form id="form-delete-resolucion-{{ $resolution->id }}" action="{{ route('club-reconocimientos.reconocimientos.destroy', $resolution) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                        onclick="confirmDelete('form-delete-resolucion-{{ $resolution->id }}', 'Se eliminará esta resolución de forma permanente.')">
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
        <div class="mt-6">{{ $resolutions->links() }}</div>
    </div>
</div>

{{-- Modal Crear Resolución --}}
<div id="modal-crear-resolucion" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-lg mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-file-contract text-leaf"></i> Nueva Resolución
                </h3>
                <button onclick="closeModal('modal-crear-resolucion')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('club-reconocimientos.reconocimientos.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Documento *</label>
                    <input type="text" name="document" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Emisión *</label>
                        <input type="date" name="date_document" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Estado *</label>
                        <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccionar...</option>
                            @foreach($states as $state)
                            <option value="{{ $state->id }}">{{ $state->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Inicio *</label>
                        <input type="date" name="date_start" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Fin *</label>
                        <input type="date" name="date_end" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                    <button type="button" onclick="closeModal('modal-crear-resolucion')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($resolutions as $resolution)

{{-- Modal Ver Resolución --}}
<div id="modal-ver-resolucion-{{ $resolution->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-md mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-file-contract text-leaf"></i> Detalle de Resolución
                </h3>
                <button onclick="closeModal('modal-ver-resolucion-{{ $resolution->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div><span class="text-[11px] font-bold text-earth uppercase">Documento</span><p class="font-semibold text-charcoal">{{ $resolution->document }}</p></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Emisión</span><p>{{ \Carbon\Carbon::parse($resolution->date_document)->format('d/m/Y') }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Estado</span>
                        <p><span class="px-2 py-1 text-[10px] font-bold rounded-full {{ $resolution->state && $resolution->state->abbreviation == 'A' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $resolution->state->title ?? 'N/A' }}</span></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Inicio</span><p>{{ \Carbon\Carbon::parse($resolution->date_start)->format('d/m/Y') }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Fin</span><p>{{ \Carbon\Carbon::parse($resolution->date_end)->format('d/m/Y') }}</p></div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Comités asociados</span><p class="font-bold text-leaf">{{ $resolution->associations->count() }}</p></div>
            </div>
            <div class="px-6 pb-6">
                <button onclick="closeModal('modal-ver-resolucion-{{ $resolution->id }}')" class="btn-secondary w-full">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Resolución --}}
<div id="modal-editar-resolucion-{{ $resolution->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-lg mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-sun"></i> Editar Resolución
                </h3>
                <button onclick="closeModal('modal-editar-resolucion-{{ $resolution->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('club-reconocimientos.reconocimientos.update', $resolution) }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Documento *</label>
                    <input type="text" name="document" value="{{ $resolution->document }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Emisión *</label>
                        <input type="date" name="date_document" value="{{ $resolution->date_document ? \Carbon\Carbon::parse($resolution->date_document)->format('Y-m-d') : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Estado *</label>
                        <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ $resolution->state_id == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Inicio *</label>
                        <input type="date" name="date_start" value="{{ $resolution->date_start ? \Carbon\Carbon::parse($resolution->date_start)->format('Y-m-d') : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Fin *</label>
                        <input type="date" name="date_end" value="{{ $resolution->date_end ? \Carbon\Carbon::parse($resolution->date_end)->format('Y-m-d') : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Actualizar</button>
                    <button type="button" onclick="closeModal('modal-editar-resolucion-{{ $resolution->id }}')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach
@endsection
