@extends('layouts.main')

@section('title', 'Pecosas - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat flex-wrap gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-alt text-leaf"></i> Gestión de Pecosas
        </h3>
        <div class="flex flex-wrap gap-3">

            @if(Auth::user()->canCreateModule('pecosas'))
            <button onclick="openModal('modal-crear-pecosa')" class="btn-primary flex items-center gap-2 text-xs sm:text-sm">
                <i class="fas fa-plus"></i> Nueva Pecosa
            </button>
            @endif
            <a href="{{ route('productos-pecosas.productos.index') }}" class="btn-secondary flex items-center gap-2 text-xs sm:text-sm">
                <i class="fas fa-clipboard-list"></i> Detalle Productos
            </a>
        </div>
    </div>

    <form id="filtro-pecosas" method="GET" action="{{ route('productos-pecosas.pecosas.index') }}" class="flex flex-col sm:flex-row gap-2 sm:gap-4 px-4 sm:px-6 py-4 flex-wrap border-b border-wheat bg-cream">
        <div class="w-full sm:w-72">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar Número</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por número de pecosa..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="flex-1 min-w-44">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
            <select name="association_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($associations as $association)
                    <option value="{{ $association->id }}" {{ request('association_id') == $association->id ? 'selected' : '' }}>{{ $association->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-36">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
            <select name="state_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <a href="{{ route('productos-pecosas.pecosas.index') }}" class="btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</a>
        </div>
    </form>

    <div id="pecosas-results">
    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="data-table w-full min-w-[800px]">
            <thead>
                <tr>
                    <th class="px-3 sm:px-4 py-4 text-left text-xs sm:text-sm">ID</th>
                    <th class="px-3 sm:px-4 py-4 text-left text-xs sm:text-sm">Número Pecosa</th>
                    <th class="px-3 sm:px-4 py-4 text-left text-xs sm:text-sm">Club de Madres</th>
                    <th class="px-3 sm:px-4 py-4 text-left text-xs sm:text-sm">Fecha Entrega</th>
                    <th class="px-3 sm:px-4 py-4 text-left text-xs sm:text-sm">Responsable</th>
                    <th class="px-3 sm:px-4 py-4 text-left text-xs sm:text-sm">Estado</th>
                    <th class="px-3 sm:px-4 py-4 text-left text-xs sm:text-sm">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pecosas as $pecosa)
                <tr>
                    <td class="px-3 sm:px-4 text-earth font-mono text-xs sm:text-sm">#{{ $pecosa->id }}</td>
                    <td class="px-3 sm:px-4 font-semibold text-xs sm:text-sm">{{ $pecosa->pecosa_number ?? 'Sin número' }}</td>
                    <td class="px-3 sm:px-4 text-xs sm:text-sm">
                        @if($pecosa->association)
                            <span class="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">{{ $pecosa->association->name }}</span>
                        @else -
                        @endif
                    </td>
                    <td class="px-3 sm:px-4 text-earth text-xs sm:text-sm">
                        {{ $pecosa->delivery_date ? \Carbon\Carbon::parse($pecosa->delivery_date)->format('d/m/Y') : '-' }}
                    </td>
                    <td class="px-3 sm:px-4 text-xs sm:text-sm">
                        {{ $pecosa->president_name ?? '-' }}
                    </td>
                    <td class="px-3 sm:px-4 text-xs sm:text-sm">
                        @if($pecosa->isVigente())
                            <span class="badge-active px-3 py-1 rounded-full text-xs font-bold">Vigente</span>
                        @else
                            <span class="badge-inactive px-3 py-1 rounded-full text-xs font-bold">Vencido</span>
                        @endif
                    </td>
                    <td class="px-3 sm:px-4 text-xs sm:text-sm">
                        <div class="flex gap-2">
                            <a href="{{ route('productos-pecosas.pecosas.comprobante', $pecosa) }}" target="_blank" class="btn-action bg-leaf-light text-leaf hover:bg-leaf hover:text-white" title="Generar Comprobante">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <button onclick="openModal('modal-ver-pecosa-{{ $pecosa->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if(Auth::user()->canEditModule('pecosas'))
                            <button onclick="openModal('modal-editar-pecosa-{{ $pecosa->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endif
                            @if(Auth::user()->canDeleteModule('pecosas'))
                            <form id="form-delete-pecosa-{{ $pecosa->id }}" action="{{ route('productos-pecosas.pecosas.destroy', $pecosa) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white"
                                    onclick="confirmDelete('form-delete-pecosa-{{ $pecosa->id }}', 'Se eliminará esta pecosa de forma permanente.')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-3 sm:px-4 py-8 text-center text-earth">No hay registros</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-t-2 border-wheat">
        <span class="text-sm text-earth font-medium">Mostrando {{ $pecosas->firstItem() ?? 0 }} - {{ $pecosas->lastItem() ?? 0 }} de {{ $pecosas->total() }} registros</span>
        {{ $pecosas->appends(request()->query())->links() }}
@foreach($pecosas as $pecosa)

{{-- Modal Ver Pecosa --}}
<div id="modal-ver-pecosa-{{ $pecosa->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-lg mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-file-alt text-leaf"></i> Detalle de Pecosa
                </h3>
                <button onclick="closeModal('modal-ver-pecosa-{{ $pecosa->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4 sm:p-6 space-y-3 text-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Número</span><p class="font-semibold text-charcoal">{{ $pecosa->pecosa_number ?? '-' }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Estado</span>
                        <p><span class="px-2 py-1 text-[10px] font-bold rounded-full {{ $pecosa->isVigente() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $pecosa->vigencia }}</span></p>
                    </div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Club de Madres</span><p>{{ $pecosa->association->name ?? '-' }}</p></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Entrega</span>
                        <p>{{ $pecosa->delivery_date ? \Carbon\Carbon::parse($pecosa->delivery_date)->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Presidenta</span>
                        <p>{{ $pecosa->president_name ?? '-' }}</p>
                    </div>
                </div>
                @if($pecosa->observation)
                <div><span class="text-[11px] font-bold text-earth uppercase">Observación</span><p class="text-earth">{{ $pecosa->observation }}</p></div>
                @endif
                <div><span class="text-[11px] font-bold text-earth uppercase">Productos</span>
                    <p class="font-bold text-leaf text-lg">{{ $pecosa->detailPecosas ? $pecosa->detailPecosas->count() : 0 }}</p>
                </div>
            </div>
            <div class="px-6 pb-6 flex gap-3">
                <a href="{{ route('productos-pecosas.pecosas.comprobante', $pecosa) }}" target="_blank" class="btn-secondary flex-1 text-center"><i class="fas fa-file-pdf mr-2"></i> Comprobante</a>
                <button onclick="closeModal('modal-ver-pecosa-{{ $pecosa->id }}')" class="btn-secondary flex-1">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Pecosa --}}
<div id="modal-editar-pecosa-{{ $pecosa->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-4xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-sun"></i> Editar Pecosa
                </h3>
                <button onclick="closeModal('modal-editar-pecosa-{{ $pecosa->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('productos-pecosas.pecosas.update', $pecosa) }}" method="POST" id="pecosa-edit-form-{{ $pecosa->id }}" onsubmit="document.getElementById('loading-screen').classList.add('active');">
                @csrf
                @method('PUT')
                <div class="p-4 sm:p-6">
                    <h4 class="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                        <i class="fas fa-file-invoice text-leaf"></i> Información de la Pecosa
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Pecosa</label>
                            <input type="text" name="pecosa_number" value="{{ $pecosa->pecosa_number }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                            <select name="association_id" id="association_id_edit_{{ $pecosa->id }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required onchange="loadPresidentaEdit({{ $pecosa->id }})">
                                <option value="">Seleccionar club...</option>
                                @foreach($associationsForModal as $assoc)
                                    <option value="{{ $assoc->id }}"
                                        data-president-id="{{ $assoc->president_partner_id }}"
                                        data-president-name="{{ $assoc->president_name }}"
                                        {{ $pecosa->association_id == $assoc->id ? 'selected' : '' }}>
                                        {{ $assoc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Entrega</label>
                            <input type="date" name="delivery_date" value="{{ $pecosa->delivery_date ? \Carbon\Carbon::parse($pecosa->delivery_date)->format('Y-m-d') : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Presidenta del Comité</label>
                            @php
                                $currentPresident = $pecosa->president_name ?? 'Sin presidenta asignada';
                            @endphp
                            <input type="text" id="president_name_edit_{{ $pecosa->id }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-gray-100" readonly value="{{ $currentPresident }}">
                            <input type="hidden" name="managing_partner_id" id="president_id_edit_{{ $pecosa->id }}" value="{{ $pecosa->president_id ?? '' }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Jefe de Almacén</label>
                            @php $jefeActivo = $responsibles->where('type', 'chief')->first(); @endphp
                            <input type="text" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-gray-100" readonly value="{{ $jefeActivo ? ($jefeActivo->person->names ?? '') . ' ' . ($jefeActivo->person->father_lastname ?? '') : 'No hay jefe activo' }}">
                            <input type="hidden" name="chief_id" value="{{ $jefeActivo->id ?? '' }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Almacenero</label>
                            @php $almaceneroActivo = $responsibles->where('type', 'storekeeper')->first(); @endphp
                            <input type="text" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-gray-100" readonly value="{{ $almaceneroActivo ? ($almaceneroActivo->person->names ?? '') . ' ' . ($almaceneroActivo->person->father_lastname ?? '') : 'No hay almacenero activo' }}">
                            <input type="hidden" name="storekeeper_id" value="{{ $almaceneroActivo->id ?? '' }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                            <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ $pecosa->state_id == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                            <textarea name="observation" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">{{ $pecosa->observation }}</textarea>
                        </div>
                    </div>

                    <div class="mt-8 border-t-2 border-wheat pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                                <i class="fas fa-list text-leaf"></i> Detalle de Productos
                            </h4>
                            <button type="button" onclick="addProductDetailEdit({{ $pecosa->id }})" class="btn-secondary text-sm">
                                <i class="fas fa-plus mr-1"></i> Agregar Producto
                            </button>
                        </div>

                        <div id="details-container-edit-{{ $pecosa->id }}" class="space-y-4">
                            @foreach($pecosa->detailPecosas as $i => $detail)
                            <div class="grid grid-cols-1 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat mb-4" id="detail-row-edit-{{ $pecosa->id }}-{{ $i }}">
                                <div class="flex items-center justify-between border-b border-wheat pb-2 mb-2">
                                    <span class="text-xs font-bold text-leaf uppercase">Ítem #{{ $i + 1 }}</span>
                                    <button type="button" onclick="removeDetailEdit({{ $pecosa->id }}, {{ $i }})" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="details[{{ $i }}][detail_pecosa_id]" value="{{ $detail->id }}">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto (Detalle)</label>
                                        <select name="details[{{ $i }}][detail_product_id]" class="select2-product-edit w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required onchange="updateDetailPriceEdit(this, {{ $pecosa->id }}, {{ $i }})">
                                            <option value="">Seleccionar producto...</option>
                                            @php
                                                $currentDpInList = $detailProductsList->firstWhere('id', $detail->detail_product_id);
                                            @endphp
                                            @if($detail->detailProduct)
                                                @php $dp = $detail->detailProduct; @endphp
                                                <option value="{{ $dp->id }}"
                                                    data-product-id="{{ $dp->product_id }}"
                                                    data-price="{{ $dp->unit_price }}"
                                                    data-stock="{{ $currentDpInList ? $currentDpInList->available_stock : 0 }}"
                                                    selected>
                                                    {{ $dp->product ? $dp->product->title . ' (' . $dp->product->abbreviation . ')' : 'Sin nombre' }} - Stock: {{ $currentDpInList ? $currentDpInList->available_stock : 0 }} ({{ \Carbon\Carbon::parse($dp->start_date)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($dp->end_date)->format('d/m/Y') }})
                                                </option>
                                            @endif
                                        </select>
                                        <input type="hidden" name="details[{{ $i }}][product_id]" id="product_id_edit_{{ $pecosa->id }}_{{ $i }}" value="{{ $detail->detailProduct->product_id ?? '' }}">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                                        <input type="number" step="0.01" name="details[{{ $i }}][quantity]" value="{{ $detail->quantity }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="1">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">P. Unitario (S/)</label>
                                        <input type="number" step="0.01" name="details[{{ $i }}][unit_price]" id="price_edit_{{ $pecosa->id }}_{{ $i }}" value="{{ $detail->unit_price }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="0">
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-3 mt-10">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i> Actualizar Pecosa
                        </button>
                        <button type="button" onclick="closeModal('modal-editar-pecosa-{{ $pecosa->id }}')" class="btn-secondary">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach
    </div>
    </div>
</div>

{{-- Modal Crear Pecosa --}}
<div id="modal-crear-pecosa" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-4xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-plus-circle text-leaf"></i> Nueva Pecosa
                </h3>
                <button onclick="closeModal('modal-crear-pecosa')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('productos-pecosas.pecosas.store') }}" method="POST" id="pecosa-form-modal" onsubmit="document.getElementById('loading-screen').classList.add('active');">
                @csrf
                <div class="p-4 sm:p-6">
                    <h4 class="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                        <i class="fas fa-file-invoice text-leaf"></i> Información de la Pecosa
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Pecosa</label>
                            <input type="text" name="pecosa_number" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required placeholder="000-000">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                            <select name="association_id" id="association_id_modal" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required onchange="loadPresidentaModal()">
                                <option value="">Seleccionar club...</option>
                                @foreach($associationsForModal as $association)
                                    <option value="{{ $association->id }}" data-president-id="{{ $association->president_partner_id }}" data-president-name="{{ $association->president_name }}">
                                        {{ $association->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Entrega</label>
                            <input type="date" name="delivery_date" value="{{ date('Y-m-d') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Presidenta del Comité</label>
                            <input type="text" id="president_name_modal" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-gray-100" readonly placeholder="Se autocompletará al seleccionar club">
                            <input type="hidden" name="managing_partner_id" id="president_id_modal" value="">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Subgerencia de Programas Sociales</label>
                            @php $jefeActivo = $responsibles->where('type', 'chief')->first(); @endphp
                            <input type="text" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-gray-100" readonly value="{{ $jefeActivo ? ($jefeActivo->person->names ?? '') . ' ' . ($jefeActivo->person->father_lastname ?? '') : 'No hay jefe activo' }}">
                            <input type="hidden" name="chief_id" value="{{ $jefeActivo->id ?? '' }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Programa Vaso de Leche</label>
                            @php $almaceneroActivo = $responsibles->where('type', 'storekeeper')->first(); @endphp
                            <input type="text" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-gray-100" readonly value="{{ $almaceneroActivo ? ($almaceneroActivo->person->names ?? '') . ' ' . ($almaceneroActivo->person->father_lastname ?? '') : 'No hay almacenero activo' }}">
                            <input type="hidden" name="storekeeper_id" value="{{ $almaceneroActivo->id ?? '' }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                            <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                            <textarea name="observation" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Detalles adicionales de la entrega..."></textarea>
                        </div>
                    </div>

                    <div class="mt-8 border-t-2 border-wheat pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                                <i class="fas fa-list text-leaf"></i> Detalle de Productos
                            </h4>
                            <button type="button" onclick="addProductDetailModal()" class="btn-secondary text-sm">
                                <i class="fas fa-plus mr-1"></i> Agregar Producto
                            </button>
                        </div>
                        
                        <div id="details-container-modal" class="space-y-4">
                        </div>
                    </div>

                    <div class="flex gap-3 mt-10">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i> Guardar Pecosa
                        </button>
                        <button type="button" onclick="closeModal('modal-crear-pecosa')" class="btn-secondary">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let detailCountModal = 0;
let detailProductsListModal = @json($detailProductsList ?? []);

function fmtDate(d) {
    if (!d) return '';
    const datePart = d.split('T')[0];
    const parts = datePart.split('-');
    return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : d;
}

function loadPresidentaModal() {
    const associationSelect = document.getElementById('association_id_modal');
    const selectedOption = associationSelect.options[associationSelect.selectedIndex];
    const presidentId = selectedOption.getAttribute('data-president-id');
    const presidentName = selectedOption.getAttribute('data-president-name');
    
    document.getElementById('president_id_modal').value = presidentId || '';
    if (presidentName) {
        document.getElementById('president_name_modal').value = presidentName;
    } else if (presidentId) {
        document.getElementById('president_name_modal').value = 'Presidenta seleccionada (ID: ' + presidentId + ')';
    } else {
        document.getElementById('president_name_modal').value = 'Sin presidenta asignada';
    }
}

function addProductDetailModal() {
    const container = document.getElementById('details-container-modal');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat mb-4 animate-fade-in';
    div.id = 'detail-row-modal-' + detailCountModal;
    
    let productOptions = '<option value="">Seleccionar producto...</option>';
    detailProductsListModal.forEach(dp => {
        if (dp.available_stock > 0) {
            const productName = dp.product ? dp.product.title : 'Sin nombre';
            const abbreviation = dp.product ? dp.product.abbreviation.trim() : '';
            const startDate = fmtDate(dp.start_date);
            const endDate = fmtDate(dp.end_date);
            productOptions += `<option value="${dp.id}" data-product-id="${dp.product_id}" data-price="${dp.unit_price}" data-stock="${dp.available_stock}">${productName} (${abbreviation}) ${startDate} ${endDate}</option>`;
        }
    });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-2">
            <span class="text-xs font-bold text-leaf uppercase">Ítem #${detailCountModal + 1}</span>
            <button type="button" onclick="removeDetailModal(${detailCountModal})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto (Detalle)</label>
                <select name="details[${detailCountModal}][detail_product_id]" class="product-select-modal w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required onchange="updateDetailPriceModal(this, ${detailCountModal})">
                    ${productOptions}
                </select>
                <input type="hidden" name="details[${detailCountModal}][product_id]" id="product_id_modal_${detailCountModal}">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                <input type="number" step="0.01" name="details[${detailCountModal}][quantity]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="1">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">P. Unitario (S/)</label>
                <input type="number" step="0.01" name="details[${detailCountModal}][unit_price]" id="price_modal_${detailCountModal}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="0" step="0.01">
            </div>
        </div>
    `;
    
    container.appendChild(div);
    detailCountModal++;
}

function removeDetailModal(id) {
    document.getElementById('detail-row-modal-' + id).remove();
}

function updateDetailPriceModal(select, id) {
    const selectedOption = select.options[select.selectedIndex];
    const productId = selectedOption.getAttribute('data-product-id');
    const price = selectedOption.getAttribute('data-price');
    
    document.getElementById('product_id_modal_' + id).value = productId;
    if (price) {
        document.getElementById('price_modal_' + id).value = price;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    addProductDetailModal();

    window.initLiveFilter({
        formEl: document.getElementById('filtro-pecosas'),
        resultsSelector: '#pecosas-results',
        url: '{{ route("productos-pecosas.pecosas.index") }}',
    });
});

function loadPresidentaEdit(pecosaId) {
    const select = document.getElementById('association_id_edit_' + pecosaId);
    const selected = select.options[select.selectedIndex];
    const presidentId = selected.getAttribute('data-president-id');
    const presidentName = selected.getAttribute('data-president-name');
    document.getElementById('president_id_edit_' + pecosaId).value = presidentId || '';
    document.getElementById('president_name_edit_' + pecosaId).value = presidentName || 'Sin presidenta asignada';
}

let detailCountsEdit = {};

function addProductDetailEdit(pecosaId) {
    const container = document.getElementById('details-container-edit-' + pecosaId);
    if (!detailCountsEdit[pecosaId]) {
        detailCountsEdit[pecosaId] = container.querySelectorAll('[id^="detail-row-edit-' + pecosaId + '-"]').length;
    }
    const idx = detailCountsEdit[pecosaId];
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat mb-4 animate-fade-in';
    div.id = 'detail-row-edit-' + pecosaId + '-' + idx;

    let productOptions = '<option value="">Seleccionar producto...</option>';
    detailProductsListModal.forEach(dp => {
        if (dp.available_stock > 0) {
            const productName = dp.product ? dp.product.title + ' (' + dp.product.abbreviation + ')' : 'Sin nombre';
            const period = fmtDate(dp.start_date) + ' al ' + fmtDate(dp.end_date);
            productOptions += `<option value="${dp.id}" data-product-id="${dp.product_id}" data-price="${dp.unit_price}" data-stock="${dp.available_stock}">${productName} - Stock: ${dp.available_stock} (${period})</option>`;
        }
    });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-2">
            <span class="text-xs font-bold text-leaf uppercase">Ítem #${idx + 1}</span>
            <button type="button" onclick="removeDetailEdit(${pecosaId}, ${idx})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto (Detalle)</label>
                <select name="details[${idx}][detail_product_id]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required onchange="updateDetailPriceEdit(this, ${pecosaId}, ${idx})">
                    ${productOptions}
                </select>
                <input type="hidden" name="details[${idx}][product_id]" id="product_id_edit_${pecosaId}_${idx}">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                <input type="number" step="0.01" name="details[${idx}][quantity]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="1">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">P. Unitario (S/)</label>
                <input type="number" step="0.01" name="details[${idx}][unit_price]" id="price_edit_${pecosaId}_${idx}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="0">
            </div>
        </div>
    `;
    container.appendChild(div);
    detailCountsEdit[pecosaId] = idx + 1;
}

function removeDetailEdit(pecosaId, idx) {
    document.getElementById('detail-row-edit-' + pecosaId + '-' + idx).remove();
}

function updateDetailPriceEdit(select, pecosaId, idx) {
    const selected = select.options[select.selectedIndex];
    const productId = selected.getAttribute('data-product-id');
    const price = selected.getAttribute('data-price');
    const productIdInput = document.getElementById('product_id_edit_' + pecosaId + '_' + idx);
    if (productIdInput) productIdInput.value = productId || '';
    const priceInput = document.getElementById('price_edit_' + pecosaId + '_' + idx);
    if (priceInput && price) priceInput.value = price;
}


document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        var s2ProductsData = detailProductsListModal.filter(function(dp) {
            return dp.available_stock > 0;
        }).map(function(dp) {
            var productName = dp.product ? dp.product.title + ' (' + dp.product.abbreviation.trim() + ')' : 'Sin nombre';
            var period = fmtDate(dp.start_date) + ' al ' + fmtDate(dp.end_date);
            return {
                id: dp.id,
                text: productName + ' - Stock: ' + dp.available_stock + ' (' + period + ')',
                product_id: dp.product_id,
                price: dp.unit_price,
                stock: dp.available_stock
            };
        });

        // Initialize Select2 on existing edit dropdowns
        $('.select2-product-edit').select2({
            data: s2ProductsData,
            width: '100%',
            placeholder: 'Seleccionar producto...',
            templateSelection: function(data) {
                if (!data.id) { return data.text; }
                if ($(data.element).length > 0 && data.element.selected) {
                    return data.text; // Es la opcion original (que ya tiene el texto completo)
                }
                return data.text;
            }
        });
        
        // Asignamos data a la creación dinámica
        window.s2ProductsData = s2ProductsData;
    }
});
</script>
@endpush

@endsection
