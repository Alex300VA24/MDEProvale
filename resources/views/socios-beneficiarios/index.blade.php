@extends('layouts.main')

@section('title', 'Socios y Beneficiarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Socios y Beneficiarios
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('socios-beneficiarios.personas.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-users"></i> Personas
            </a>
            <a href="{{ route('socios-beneficiarios.beneficiarios.reportes') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('socios-beneficiarios.beneficiarios.padron') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-clipboard-list"></i> Padrón PVL
            </a>
            <a href="{{ route('socios-beneficiarios.beneficiarios.imprimir') }}" target="_blank" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-id-card"></i> Ficha Beneficiario
            </a>
            @if(Auth::user()->canCreateModule('socios-beneficiarios'))
            <button onclick="openModal('modal-crear-socio')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Socio
            </button>
            @endif
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
                <div class="md:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o DNI..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <div>
                    <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los Clubes</option>
                        @foreach($associations as $association)
                        <option value="{{ $association->id }}" {{ request('association_id') == $association->id ? 'selected' : '' }}>{{ $association->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los Estados</option>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn-primary"><i class="fas fa-search mr-2"></i> Buscar</button>
                <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary"><i class="fas fa-broom mr-2"></i> Limpiar</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-earth">ID</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Socio</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">DNI</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Club</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Estado</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Beneficiarios</th>
                        <th class="px-4 py-3 text-center font-bold text-earth">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($partners as $partner)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">#{{ $partner->id }}</td>
                        <td class="px-4 py-3 font-medium">
                            {{ $partner->people ? $partner->people->names . ' ' . $partner->people->father_lastname : 'Sin nombre' }}
                        </td>
                        <td class="px-4 py-3">{{ $partner->people->dni ?? 'Sin DNI' }}</td>
                        <td class="px-4 py-3">{{ $partner->association->name ?? 'Sin club' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                @if($partner->state && $partner->state->title == 'Activo') bg-green-100 text-green-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ $partner->state->title ?? 'Sin estado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold text-leaf">{{ $partner->beneficiaries->count() }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openModal('modal-ver-socio-{{ $partner->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if(Auth::user()->canEditModule('socios-beneficiarios'))
                                <button onclick="openModal('modal-editar-socio-{{ $partner->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                                @if(Auth::user()->canDeleteModule('socios-beneficiarios'))
                                <form id="form-delete-socio-{{ $partner->id }}" action="{{ route('socios-beneficiarios.socios.destroy', $partner) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                        onclick="confirmDelete('form-delete-socio-{{ $partner->id }}', 'Se eliminará este socio y todos sus beneficiarios de forma permanente.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-3"></i>
                            <p>No hay socios registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $partners->links() }}</div>
    </div>
</div>

{{-- Modal Crear Socio --}}
<div id="modal-crear-socio" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-4xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user-plus text-leaf"></i> Nuevo Socio
                </h3>
                <button onclick="closeModal('modal-crear-socio')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('socios-beneficiarios.socios.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Persona *</label>
                    <select name="person_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar persona...</option>
                        @foreach($people as $person)
                        <option value="{{ $person->id }}">{{ $person->names }} {{ $person->father_lastname }} ({{ $person->dni }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Club *</label>
                    <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar club...</option>
                        @foreach($associations as $association)
                        <option value="{{ $association->id }}">{{ $association->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Inicio *</label>
                        <input type="date" name="date_begin" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Fin *</label>
                        <input type="date" name="date_end" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
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
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Observaciones</label>
                    <textarea name="observations" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"></textarea>
                </div>

                <div class="border-t-2 border-wheat pt-4 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-bold text-charcoal flex items-center gap-2">
                            <i class="fas fa-users text-leaf"></i> Beneficiarios
                        </h4>
                        <button type="button" onclick="addBeneficiaryCreate()" class="btn-secondary text-xs">
                            <i class="fas fa-plus mr-1"></i> Agregar
                        </button>
                    </div>
                    <div id="beneficiaries-container-create" class="space-y-3">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                    <button type="button" onclick="closeModal('modal-crear-socio')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($partners as $partner)

{{-- Modal Ver Socio --}}
<div id="modal-ver-socio-{{ $partner->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-4xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user text-leaf"></i> Detalle del Socio
                </h3>
                <button onclick="closeModal('modal-ver-socio-{{ $partner->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div><span class="text-[11px] font-bold text-earth uppercase">Nombre</span>
                    <p class="font-semibold text-charcoal">{{ $partner->people ? $partner->people->names . ' ' . $partner->people->father_lastname . ' ' . $partner->people->mother_lastname : 'Sin nombre' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">DNI</span><p>{{ $partner->people->dni ?? '-' }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Estado</span>
                        <p><span class="px-2 py-1 text-[10px] font-bold rounded-full {{ $partner->state && $partner->state->title == 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $partner->state->title ?? 'N/A' }}</span></p>
                    </div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Club</span><p>{{ $partner->association->name ?? '-' }}</p></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Inicio</span><p>{{ $partner->date_begin ? \Carbon\Carbon::parse($partner->date_begin)->format('d/m/Y') : '-' }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Fin</span><p>{{ $partner->date_end ? \Carbon\Carbon::parse($partner->date_end)->format('d/m/Y') : '-' }}</p></div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Beneficiarios</span><p class="font-bold text-leaf text-lg">{{ $partner->beneficiaries->count() }}</p></div>
                @if($partner->beneficiaries->count() > 0)
                <div class="mt-3">
                    <span class="text-[11px] font-bold text-earth uppercase">Lista de Beneficiarios</span>
                    <div class="mt-2 space-y-2">
                        @foreach($partner->beneficiaries as $beneficiary)
                        @php $latestHistory = $beneficiary->histories->first(); @endphp
                        <div class="p-3 bg-gray-50 rounded-lg border border-wheat text-sm">
                            <div class="flex items-center justify-between border-b border-wheat pb-2 mb-2">
                                <span class="text-xs font-bold text-leaf uppercase">Beneficiario</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                                <div>
                                    <span class="text-[10px] font-bold text-earth uppercase">Nombre</span>
                                    <p class="font-semibold">{{ $beneficiary->person->names ?? '' }} {{ $beneficiary->person->father_lastname ?? '' }} {{ $beneficiary->person->mother_lastname ?? '' }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-earth uppercase">DNI</span>
                                    <p>{{ $beneficiary->person->dni ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                                <div>
                                    <span class="text-[10px] font-bold text-earth uppercase">Parentesco</span>
                                    <p>{{ $beneficiary->relationship->title ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="border-t border-wheat pt-2 mt-2">
                                <span class="text-[10px] font-bold text-earth uppercase">Datos Clínicos</span>
                                <div class="grid grid-cols-3 gap-2 mt-1">
                                    <div><span class="text-[9px] text-earth uppercase">Peso</span><p class="text-xs">{{ $latestHistory->weight ?? '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">Talla</span><p class="text-xs">{{ $latestHistory->height ?? '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">HMG</span><p class="text-xs">{{ $latestHistory->hmg ?? '-' }}</p></div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 mt-1">
                                    <div><span class="text-[9px] text-earth uppercase">F. Inicio</span><p class="text-xs">{{ $latestHistory && $latestHistory->date_begin ? \Carbon\Carbon::parse($latestHistory->date_begin)->format('d/m/Y') : '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">F. Fin</span><p class="text-xs">{{ $latestHistory && $latestHistory->date_end ? \Carbon\Carbon::parse($latestHistory->date_end)->format('d/m/Y') : '-' }}</p></div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-1">
                                    <div><span class="text-[9px] text-earth uppercase">Tipo Beneficio</span><p class="text-xs">{{ $latestHistory->typeBenefit->title ?? '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">Estado</span><p class="text-xs">{{ $latestHistory->state->title ?? '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">Motivo Descalif.</span><p class="text-xs">{{ $latestHistory->reasonDisqualification->title ?? 'Ninguno' }}</p></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @if($partner->observations)
                <div><span class="text-[11px] font-bold text-earth uppercase">Observaciones</span><p class="text-earth">{{ $partner->observations }}</p></div>
                @endif
            </div>
            <div class="px-6 pb-6">
                <button onclick="closeModal('modal-ver-socio-{{ $partner->id }}')" class="btn-secondary w-full">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Socio --}}
<div id="modal-editar-socio-{{ $partner->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-4xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-sun"></i> Editar Socio
                </h3>
                <button onclick="closeModal('modal-editar-socio-{{ $partner->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('socios-beneficiarios.socios.update', $partner) }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Persona *</label>
                    <select name="person_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($allPeople as $person)
                        <option value="{{ $person->id }}" {{ $partner->person_id == $person->id ? 'selected' : '' }}>{{ $person->names }} {{ $person->father_lastname }} ({{ $person->dni }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Club *</label>
                    <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($associations as $association)
                        <option value="{{ $association->id }}" {{ $partner->association_id == $association->id ? 'selected' : '' }}>{{ $association->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Inicio *</label>
                        <input type="date" name="date_begin" value="{{ $partner->date_begin }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Fin *</label>
                        <input type="date" name="date_end" value="{{ $partner->date_end }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Estado *</label>
                    <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ $partner->state_id == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Observaciones</label>
                    <textarea name="observations" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">{{ $partner->observations }}</textarea>
                </div>

                <div class="border-t-2 border-wheat pt-4 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-bold text-charcoal flex items-center gap-2">
                            <i class="fas fa-users text-leaf"></i> Beneficiarios
                        </h4>
                        <button type="button" onclick="addBeneficiaryEdit({{ $partner->id }})" class="btn-secondary text-xs">
                            <i class="fas fa-plus mr-1"></i> Agregar
                        </button>
                    </div>
                    <div id="beneficiaries-container-edit-{{ $partner->id }}" class="space-y-3">
                        @foreach($partner->beneficiaries as $index => $beneficiary)
                        @php $latestHistory = $beneficiary->histories->first(); @endphp
                        <div class="p-3 bg-gray-50 rounded-lg border border-wheat" id="beneficiary-row-edit-{{ $partner->id }}-{{ $index }}">
                            <div class="flex items-center justify-between border-b border-wheat pb-2 mb-3">
                                <span class="text-xs font-bold text-leaf uppercase">Beneficiario #{{ $index + 1 }}</span>
                                <button type="button" onclick="removeBeneficiaryEdit({{ $partner->id }}, {{ $index }})" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Persona *</label>
                                    <select name="beneficiaries[{{ $index }}][person_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" required>
                                        @foreach($allPeople as $person)
                                        <option value="{{ $person->id }}" {{ $beneficiary->person_id == $person->id ? 'selected' : '' }}>{{ $person->names }} {{ $person->father_lastname }} ({{ $person->dni }})</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="beneficiaries[{{ $index }}][id]" value="{{ $beneficiary->id }}">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Parentesco *</label>
                                    <select name="beneficiaries[{{ $index }}][relationship_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" required>
                                        @foreach($relationships as $relationship)
                                        <option value="{{ $relationship->id }}" {{ $beneficiary->relationship_id == $relationship->id ? 'selected' : '' }}>{{ $relationship->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p class="text-[10px] font-bold text-earth uppercase mb-2 border-t border-wheat pt-2">Datos Clínicos <span class="font-normal normal-case text-gray-400">(opcional)</span></p>
                            <div class="grid grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Peso (kg)</label>
                                    <input type="number" step="0.01" min="0" name="beneficiaries[{{ $index }}][weight]" value="{{ $latestHistory->weight ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" placeholder="65.50">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Talla (cm)</label>
                                    <input type="number" step="0.01" min="0" name="beneficiaries[{{ $index }}][height]" value="{{ $latestHistory->height ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" placeholder="160.00">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">HMG (g/dL)</label>
                                    <input type="number" step="0.01" min="0" name="beneficiaries[{{ $index }}][hmg]" value="{{ $latestHistory->hmg ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" placeholder="12.50">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">F. Inicio Beneficio</label>
                                    <input type="date" name="beneficiaries[{{ $index }}][date_begin]" value="{{ $latestHistory->date_begin ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">F. Fin Beneficio</label>
                                    <input type="date" name="beneficiaries[{{ $index }}][date_end]" value="{{ $latestHistory->date_end ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Tipo de Beneficio</label>
                                    <select name="beneficiaries[{{ $index }}][type_benefit_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                        <option value="">Seleccionar...</option>
                                        @foreach($typeBenefits as $tb)
                                        <option value="{{ $tb->id }}" {{ ($latestHistory->type_benefit_id ?? null) == $tb->id ? 'selected' : '' }}>{{ $tb->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Estado</label>
                                    <select name="beneficiaries[{{ $index }}][history_state_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                        <option value="">Seleccionar...</option>
                                        @foreach($states as $st)
                                        <option value="{{ $st->id }}" {{ ($latestHistory->state_id ?? null) == $st->id ? 'selected' : '' }}>{{ $st->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Motivo Descalificación</label>
                                    <select name="beneficiaries[{{ $index }}][reason_disqualification_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                        <option value="">Ninguno</option>
                                        @foreach($reasonDisqualifications as $rd)
                                        <option value="{{ $rd->id }}" {{ ($latestHistory->reason_disqualification_id ?? null) == $rd->id ? 'selected' : '' }}>{{ $rd->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Actualizar</button>
                    <button type="button" onclick="closeModal('modal-editar-socio-{{ $partner->id }}')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach

<script>
let beneficiaryCountCreate = 0;
let allPeople = @json($allPeople);
let allRelationships = @json($relationships);
let allTypeBenefits = @json($typeBenefits);
let allStates = @json($states);
let allReasonDisqualifications = @json($reasonDisqualifications);

function buildOpts(items, placeholder, labelKey) {
    let html = `<option value="">${placeholder}</option>`;
    items.forEach(i => { html += `<option value="${i.id}">${i[labelKey]}</option>`; });
    return html;
}

function addBeneficiaryCreate() {
    const container = document.getElementById('beneficiaries-container-create');
    const idx = beneficiaryCountCreate;
    const div = document.createElement('div');
    div.className = 'p-3 bg-gray-50 rounded-lg border border-wheat';
    div.id = 'beneficiary-row-create-' + idx;

    const sc = 'w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white';
    const ic = 'w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white';
    const lc = 'block text-[10px] font-bold text-earth uppercase mb-1';

    let personOpts = '<option value="">Seleccionar persona...</option>';
    allPeople.forEach(p => { personOpts += `<option value="${p.id}">${p.names} ${p.father_lastname} (${p.dni})</option>`; });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-3">
            <span class="text-xs font-bold text-leaf uppercase">Beneficiario #${idx + 1}</span>
            <button type="button" onclick="removeBeneficiaryCreate(${idx})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div>
                <label class="${lc}">Persona *</label>
                <select name="beneficiaries[${idx}][person_id]" class="${sc}" required>${personOpts}</select>
            </div>
            <div>
                <label class="${lc}">Parentesco *</label>
                <select name="beneficiaries[${idx}][relationship_id]" class="${sc}" required>${buildOpts(allRelationships, 'Seleccionar...', 'title')}</select>
            </div>
        </div>
        <p class="text-[10px] font-bold text-earth uppercase mb-2 border-t border-wheat pt-2">Datos Clínicos <span class="font-normal normal-case text-gray-400">(opcional)</span></p>
        <div class="grid grid-cols-3 gap-3 mb-3">
            <div>
                <label class="${lc}">Peso (kg)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${idx}][weight]" class="${ic}" placeholder="65.50">
            </div>
            <div>
                <label class="${lc}">Talla (cm)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${idx}][height]" class="${ic}" placeholder="160.00">
            </div>
            <div>
                <label class="${lc}">HMG (g/dL)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${idx}][hmg]" class="${ic}" placeholder="12.50">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <label class="${lc}">F. Inicio Beneficio</label>
                <input type="date" name="beneficiaries[${idx}][date_begin]" class="${ic}">
            </div>
            <div>
                <label class="${lc}">F. Fin Beneficio</label>
                <input type="date" name="beneficiaries[${idx}][date_end]" class="${ic}">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="${lc}">Tipo de Beneficio</label>
                <select name="beneficiaries[${idx}][type_benefit_id]" class="${sc}">${buildOpts(allTypeBenefits, 'Seleccionar...', 'title')}</select>
            </div>
            <div>
                <label class="${lc}">Estado</label>
                <select name="beneficiaries[${idx}][history_state_id]" class="${sc}">${buildOpts(allStates, 'Seleccionar...', 'title')}</select>
            </div>
            <div>
                <label class="${lc}">Motivo Descalificación</label>
                <select name="beneficiaries[${idx}][reason_disqualification_id]" class="${sc}">${buildOpts(allReasonDisqualifications, 'Ninguno', 'title')}</select>
            </div>
        </div>
    `;

    container.appendChild(div);
    beneficiaryCountCreate++;
}

function removeBeneficiaryCreate(id) {
    document.getElementById('beneficiary-row-create-' + id).remove();
}

let beneficiaryCountEdit = 0;

function addBeneficiaryEdit(partnerId) {
    const container = document.getElementById('beneficiaries-container-edit-' + partnerId);
    const existingCount = container.querySelectorAll('[id^="beneficiary-row-edit-' + partnerId + '-"]').length;
    const div = document.createElement('div');
    div.className = 'p-3 bg-gray-50 rounded-lg border border-wheat';
    div.id = 'beneficiary-row-edit-' + partnerId + '-' + existingCount;

    const sc = 'w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white';
    const ic = 'w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white';
    const lc = 'block text-[10px] font-bold text-earth uppercase mb-1';

    let personOpts = '<option value="">Seleccionar persona...</option>';
    allPeople.forEach(p => { personOpts += `<option value="${p.id}">${p.names} ${p.father_lastname} (${p.dni})</option>`; });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-3">
            <span class="text-xs font-bold text-leaf uppercase">Beneficiario #${existingCount + 1}</span>
            <button type="button" onclick="removeBeneficiaryEdit(${partnerId}, ${existingCount})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div>
                <label class="${lc}">Persona *</label>
                <select name="beneficiaries[${existingCount}][person_id]" class="${sc}" required>${personOpts}</select>
            </div>
            <div>
                <label class="${lc}">Parentesco *</label>
                <select name="beneficiaries[${existingCount}][relationship_id]" class="${sc}" required>${buildOpts(allRelationships, 'Seleccionar...', 'title')}</select>
            </div>
        </div>
        <p class="text-[10px] font-bold text-earth uppercase mb-2 border-t border-wheat pt-2">Datos Clínicos <span class="font-normal normal-case text-gray-400">(opcional)</span></p>
        <div class="grid grid-cols-3 gap-3 mb-3">
            <div>
                <label class="${lc}">Peso (kg)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${existingCount}][weight]" class="${ic}" placeholder="65.50">
            </div>
            <div>
                <label class="${lc}">Talla (cm)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${existingCount}][height]" class="${ic}" placeholder="160.00">
            </div>
            <div>
                <label class="${lc}">HMG (g/dL)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${existingCount}][hmg]" class="${ic}" placeholder="12.50">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <label class="${lc}">F. Inicio Beneficio</label>
                <input type="date" name="beneficiaries[${existingCount}][date_begin]" class="${ic}">
            </div>
            <div>
                <label class="${lc}">F. Fin Beneficio</label>
                <input type="date" name="beneficiaries[${existingCount}][date_end]" class="${ic}">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="${lc}">Tipo de Beneficio</label>
                <select name="beneficiaries[${existingCount}][type_benefit_id]" class="${sc}">${buildOpts(allTypeBenefits, 'Seleccionar...', 'title')}</select>
            </div>
            <div>
                <label class="${lc}">Estado</label>
                <select name="beneficiaries[${existingCount}][history_state_id]" class="${sc}">${buildOpts(allStates, 'Seleccionar...', 'title')}</select>
            </div>
            <div>
                <label class="${lc}">Motivo Descalificación</label>
                <select name="beneficiaries[${existingCount}][reason_disqualification_id]" class="${sc}">${buildOpts(allReasonDisqualifications, 'Ninguno', 'title')}</select>
            </div>
        </div>
    `;
    
    container.appendChild(div);
}

function removeBeneficiaryEdit(partnerId, index) {
    document.getElementById('beneficiary-row-edit-' + partnerId + '-' + index).remove();
}
</script>
@endsection
