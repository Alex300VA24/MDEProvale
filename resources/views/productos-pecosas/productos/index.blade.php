@extends('layouts.main')

@section('title', 'Productos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat flex-wrap gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-box text-leaf"></i> Gestión de Productos
        </h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('productos-pecosas.pecosas.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            @if(Auth::user()->canCreateModule('productos'))
            <button onclick="openModal('modal-crear-producto')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Producto
            </button>
            @endif
        </div>
    </div>

    <form id="filtro-productos" method="GET" action="{{ route('productos-pecosas.productos.index') }}" class="flex flex-col sm:flex-row gap-2 sm:gap-4 px-4 sm:px-6 py-3 sm:py-4 flex-wrap border-b border-wheat bg-cream">
        <div class="w-full sm:w-72">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o abreviatura..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="flex-1 min-w-0 sm:min-w-36">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
            <select name="state_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-0 sm:min-w-36">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">UOM</label>
            <select name="uom_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($uoms as $uom)
                    <option value="{{ $uom->id }}" {{ request('uom_id') == $uom->id ? 'selected' : '' }}>{{ $uom->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <a href="{{ route('productos-pecosas.productos.index') }}" class="btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</a>
        </div>
    </form>

    <div id="productos-results">
    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="data-table w-full min-w-[600px] text-xs sm:text-sm">
            <thead>
                <tr>
                    <th class="px-3 sm:px-4 py-4 text-left">ID</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Nombre</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Abreviatura</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Estado</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td class="px-3 sm:px-4 text-earth font-mono text-sm">#{{ $product->id }}</td>
                    <td class="px-3 sm:px-4 font-semibold">{{ $product->title ?? 'Sin nombre' }}</td>
                    <td class="px-3 sm:px-4 text-earth">{{ $product->abbreviation ?? '-' }}</td>
                    <td class="px-3 sm:px-4">
                        @if($product->state)
                            <span class="badge {{ $product->state?->abbreviation === 'VIG' ? 'badge-current' : 'badge-expired' }}">{{ $product->state->title }}</span>
                        @else
                            <span class="badge badge-unknown">Sin estado</span>
                        @endif
                    </td>
                    <td class="px-3 sm:px-4">
                        <div class="flex gap-2">
                            <button onclick="openModal('modal-ver-producto-{{ $product->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if(Auth::user()->canEditModule('productos'))
                            <button onclick="openModal('modal-editar-producto-{{ $product->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endif
                            @if(Auth::user()->canDeleteModule('productos'))
                            <form id="form-delete-prod-{{ $product->id }}" action="{{ route('productos-pecosas.productos.destroy', $product) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                    onclick="confirmDelete('form-delete-prod-{{ $product->id }}', 'Se eliminará el producto {{ addslashes($product->title) }} de forma permanente.')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-3 sm:px-4 py-8 text-center text-earth">No hay registros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-t-2 border-wheat">
        <span class="text-xs sm:text-sm text-earth font-medium">Mostrando {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} de {{ $products->total() }} registros</span>
        {{ $products->appends(request()->query())->links() }}
@foreach($products as $product)

{{-- Modal Ver --}}
<div id="modal-ver-producto-{{ $product->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-lg mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-eye text-[#0284C7]"></i> Detalle del Producto
                </h3>
                <button type="button" onclick="closeModal('modal-ver-producto-{{ $product->id }}')" class="w-8 h-8 rounded bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all" aria-label="Cerrar modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">ID</p>
                    <p class="font-semibold text-charcoal">#{{ $product->id }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Nombre</p>
                    <p class="font-semibold text-charcoal">{{ $product->title }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Código</p>
                    <p class="font-semibold text-charcoal">{{ $product->code ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Abreviatura</p>
                    <p class="font-semibold text-charcoal">{{ $product->abbreviation ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Stock</p>
                    <p class="font-semibold text-charcoal">{{ $product->stock }} {{ $product->uom->abbreviation ?? '' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Precio Unitario</p>
                    <p class="font-semibold text-charcoal">S/ {{ number_format($product->unit_price, 2) }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Unidad de Medida</p>
                    <p class="font-semibold text-charcoal">{{ $product->uom->title ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Estado</p>
                    <span class="badge {{ $product->state?->abbreviation === 'VIG' ? 'badge-current' : ($product->state?->abbreviation === 'VEN' ? 'badge-expired' : 'badge-unknown') }}">
                        {{ $product->state->title ?? 'Sin estado' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar --}}
<div id="modal-editar-producto-{{ $product->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-[#D97706]"></i> Editar Producto
                </h3>
                <button onclick="closeModal('modal-editar-producto-{{ $product->id }}')" class="w-8 h-8 rounded bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('productos-pecosas.productos.update', $product) }}" method="POST" class="p-6" onsubmit="document.getElementById('loading-screen').classList.add('active');">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Producto</label>
                        <input type="text" name="title" value="{{ $product->title }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Código</label>
                        <input type="text" name="code" value="{{ $product->code }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Abreviatura</label>
                        <input type="text" name="abbreviation" value="{{ $product->abbreviation }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Unidad de Medida</label>
                        <select name="uom_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccione</option>
                            @foreach($uoms as $uom)
                                <option value="{{ $uom->id }}" {{ $product->uom_id == $uom->id ? 'selected' : '' }}>{{ $uom->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                        <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccione</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}" {{ $product->state_id == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-editar-producto-{{ $product->id }}')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach
    </div>
    </div>
</div>

{{-- Detalle Productos --}}
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mt-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-boxes text-leaf"></i> Detalle Productos
        </h3>
    </div>

    <div class="p-6">
        <form id="filtro-detalle-productos" method="GET" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar Producto</label>
                    <input type="text" name="search_detalle" value="{{ request('search_detalle') }}" placeholder="Nombre o abreviatura..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto</label>
                    <select name="product_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf select2">
                        <option value="">Todos los productos</option>
                        @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Período</label>
                    <select name="periodo" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="">Todos</option>
                        <option value="vigente" {{ request('periodo') == 'vigente' ? 'selected' : '' }}>Vigente</option>
                        <option value="vencido" {{ request('periodo') == 'vencido' ? 'selected' : '' }}>Vencido</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <a href="{{ route('productos-pecosas.productos.index') }}" class="btn-secondary">
                    <i class="fas fa-broom mr-2"></i> Limpiar
                </a>
            </div>
        </form>

        <div id="detalle-productos-results">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-earth">Producto</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Período</th>
                        <th class="px-4 py-3 text-right font-bold text-earth">Stock Inicial</th>
                        <th class="px-4 py-3 text-right font-bold text-earth">Precio Unit.</th>
                        <th class="px-4 py-3 text-right font-bold text-earth">Total Entrada</th>
                        <th class="px-4 py-3 text-right font-bold text-earth">Stock Usado</th>
                        <th class="px-4 py-3 text-right font-bold text-earth">Stock Actual</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($detailProducts as $detail)
                    @php
                        $stockInicial = $detail->quantity;
                        $stockUsado = $detail->used_quantity ?? 0;
                        $stockActual = $stockInicial - $stockUsado;
                        $totalEntrada = $stockInicial * $detail->unit_price;
                        
                        $today = now()->toDateString();
                        if ($detail->end_date < $today) {
                            $estado = 'Vencido';
                            $estadoClass = 'badge-expired';
                        } elseif ($detail->start_date > $today) {
                            $estado = 'Por venir';
                            $estadoClass = 'bg-yellow-100 text-yellow-800';
                        } else {
                            $estado = 'Vigente';
                            $estadoClass = 'badge-current';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-charcoal">{{ $detail->product->title }}</div>
                            <div class="text-xs text-gray-500">{{ $detail->product->abbreviation }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-xs">
                                <div class="font-medium">Desde: {{ date('d/m/Y', strtotime($detail->start_date)) }}</div>
                                <div class="font-medium">Hasta: {{ date('d/m/Y', strtotime($detail->end_date)) }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($stockInicial, 2) }}</td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format($detail->unit_price, 2) }}</td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format($totalEntrada, 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600">
                            @if($stockUsado > 0)
                                -{{ number_format($stockUsado, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-bold {{ $stockActual > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($stockActual, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $estadoClass }}">
                                {{ $estado }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-boxes text-4xl mb-3"></i>
                            <p>No hay registros de productos</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $detailProducts->appends(request()->query())->links() }}
        </div>
        </div>
    </div>
</div>

{{-- Modal Crear Producto --}}
<div id="modal-crear-producto" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-plus-circle text-leaf"></i> Nuevo Producto
                </h3>
                <button onclick="closeModal('modal-crear-producto')" class="w-8 h-8 rounded bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('productos-pecosas.productos.store') }}" method="POST" class="p-6" onsubmit="document.getElementById('loading-screen').classList.add('active');">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Producto</label>
                        <input type="text" name="title" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Código</label>
                        <input type="text" name="code" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Abreviatura</label>
                        <input type="text" name="abbreviation" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Unidad de Medida</label>
                        <select name="uom_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccione</option>
                            @foreach($uoms as $uom)
                                <option value="{{ $uom->id }}">{{ $uom->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                        <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccione</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}">{{ $state->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-crear-producto')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Seleccione una opción',
            allowClear: true
        });

        window.initLiveFilter({
            formEl: document.getElementById('filtro-productos'),
            resultsSelector: '#productos-results',
            url: '{{ route("productos-pecosas.productos.index") }}',
        });

        window.initLiveFilter({
            formEl: document.getElementById('filtro-detalle-productos'),
            resultsSelector: '#detalle-productos-results',
            url: '{{ route("productos-pecosas.productos.index") }}',
        });
    });
</script>
@endsection
