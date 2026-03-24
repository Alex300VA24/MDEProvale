@extends('layouts.main')

@section('title', 'Productos y Pecosas - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-box text-leaf"></i> Productos y Pecosas
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('productos-pecosas.productos-detalle') }}" class="btn-secondary flex items-center gap-2" title="Detalle Productos">
                <i class="fas fa-clipboard-list"></i> Detalle Productos
            </a>
            <a href="{{ route('productos-pecosas.pecosas.programacion-entrega') }}" target="_blank" class="btn-secondary flex items-center gap-2" title="Programación de Entrega">
                <i class="fas fa-truck"></i> Programación Entrega
            </a>
            <a href="{{ route('productos-pecosas.pecosas.reportes') }}" class="btn-secondary flex items-center gap-2" title="Reportes">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <button onclick="openModal('modal-crear-pecosa')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar"></i> Nueva Pecosa
            </button>
            <button onclick="openModal('modal-crear-producto')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Producto
            </button>
        </div>
    </div>

    <div class="p-6">
        <form method="GET" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar producto..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
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
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search mr-2"></i> Buscar
                </button>
                <a href="{{ route('productos-pecosas.index') }}" class="btn-secondary">
                    <i class="fas fa-broom mr-2"></i> Limpiar
                </a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-earth">ID</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Producto</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Abrev.</th>
                        <th class="px-4 py-3 text-right font-bold text-earth">Stock</th>
                        <th class="px-4 py-3 text-right font-bold text-earth">Precio</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Estado</th>
                        <th class="px-4 py-3 text-center font-bold text-earth">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">#{{ $product->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $product->title }}</td>
                        <td class="px-4 py-3">{{ $product->abbreviation ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-bold {{ $product->stock <= 10 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $product->stock }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">S/ {{ number_format($product->unit_price, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($product->state->title == 'Activo') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800 @endif">
                                {{ $product->state->title ?? 'Sin estado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('productos-pecosas.productos.show', $product) }}" class="text-blue-600 hover:text-blue-800" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('productos-pecosas.productos.edit', $product) }}" class="text-yellow-600 hover:text-yellow-800" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form id="form-delete-prod-idx-{{ $product->id }}" action="{{ route('productos-pecosas.productos.destroy', $product) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                        onclick="confirmDelete('form-delete-prod-idx-{{ $product->id }}', 'Se eliminará este producto de forma permanente.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-box text-4xl mb-3"></i>
                            <p>No hay productos registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    </div>
</div>

{{-- Modal Crear Pecosa --}}
<div id="modal-crear-pecosa" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-4xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-plus-circle text-leaf"></i> Nueva Pecosa
                </h3>
                <button onclick="closeModal('modal-crear-pecosa')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('productos-pecosas.pecosas.store') }}" method="POST" id="pecosa-form-modal">
                @csrf
                <div class="p-6">
                    <h4 class="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                        <i class="fas fa-file-invoice text-leaf"></i> Información de la Pecosa
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Pecosa</label>
                            <input type="text" name="pecosa_number" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required placeholder="000-000">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                            <select name="association_id" id="association_id_pp" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required onchange="loadPresidentaPP()">
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
                            <input type="text" id="president_name_pp" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-gray-100" readonly placeholder="Se autocompletará al seleccionar club">
                            <input type="hidden" name="managing_partner_id" id="president_id_pp" value="">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Jefe de Almacén</label>
                            @php $jefeActivo = isset($responsibles) ? $responsibles->where('type', 'chief')->first() : null; @endphp
                            <input type="text" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-gray-100" readonly value="{{ $jefeActivo ? ($jefeActivo->person->names ?? '') . ' ' . ($jefeActivo->person->father_lastname ?? '') : 'No hay jefe activo' }}">
                            <input type="hidden" name="chief_id" value="{{ $jefeActivo->id ?? '' }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Almacenero</label>
                            @php $almaceneroActivo = isset($responsibles) ? $responsibles->where('type', 'storekeeper')->first() : null; @endphp
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
                            <button type="button" onclick="addProductDetailPP()" class="btn-secondary text-sm">
                                <i class="fas fa-plus mr-1"></i> Agregar Producto
                            </button>
                        </div>
                        
                        <div id="details-container-pp" class="space-y-4">
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

{{-- Modal Crear Producto --}}
<div id="modal-crear-producto" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-2xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-plus-circle text-leaf"></i> Nuevo Producto
                </h3>
                <button onclick="closeModal('modal-crear-producto')" class="w-8 h-8 rounded bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('productos-pecosas.productos.store') }}" method="POST" class="p-6">
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
function loadPresidentaPP() {
    const associationSelect = document.getElementById('association_id_pp');
    const selectedOption = associationSelect.options[associationSelect.selectedIndex];
    const presidentId = selectedOption.getAttribute('data-president-id');
    const presidentName = selectedOption.getAttribute('data-president-name');
    
    document.getElementById('president_id_pp').value = presidentId || '';
    if (presidentName) {
        document.getElementById('president_name_pp').value = presidentName;
    } else if (presidentId) {
        document.getElementById('president_name_pp').value = 'Presidenta seleccionada (ID: ' + presidentId + ')';
    } else {
        document.getElementById('president_name_pp').value = 'Sin presidenta asignada';
    }
}

let detailCountPP = 0;
let detailProductsListPP = @json($detailProductsList ?? []);

function fmtDate(d) {
    if (!d) return '';
    const parts = d.split('-');
    return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : d;
}

function addProductDetailPP() {
    const container = document.getElementById('details-container-pp');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat mb-4 animate-fade-in';
    div.id = 'detail-row-pp-' + detailCountPP;
    
    let productOptions = '<option value="">Seleccionar producto...</option>';
    detailProductsListPP.forEach(dp => {
        if (dp.available_stock > 0) {
            const productName = dp.product ? dp.product.title + ' (' + dp.product.abbreviation + ')' : 'Sin nombre';
            const period = fmtDate(dp.start_date) + ' al ' + fmtDate(dp.end_date);
            productOptions += `<option value="${dp.id}" data-product-id="${dp.product_id}" data-price="${dp.unit_price}" data-stock="${dp.available_stock}">${productName} - Stock: ${dp.available_stock} (${period})</option>`;
        }
    });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-2">
            <span class="text-xs font-bold text-leaf uppercase">Ítem #${detailCountPP + 1}</span>
            <button type="button" onclick="removeDetailPP(${detailCountPP})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto (Detalle)</label>
                <select name="details[${detailCountPP}][detail_product_id]" class="product-select-modal w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required onchange="updateDetailPricePP(this, ${detailCountPP})">
                    ${productOptions}
                </select>
                <input type="hidden" name="details[${detailCountPP}][product_id]" id="product_id_pp_${detailCountPP}">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                <input type="number" step="0.01" name="details[${detailCountPP}][quantity]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="1">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">P. Unitario (S/)</label>
                <input type="number" step="0.01" name="details[${detailCountPP}][unit_price]" id="price_pp_${detailCountPP}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="0" step="0.01">
            </div>
        </div>
    `;
    
    container.appendChild(div);
    detailCountPP++;
}

function removeDetailPP(id) {
    document.getElementById('detail-row-pp-' + id).remove();
}

function updateDetailPricePP(select, id) {
    const selectedOption = select.options[select.selectedIndex];
    const productId = selectedOption.getAttribute('data-product-id');
    const price = selectedOption.getAttribute('data-price');
    
    document.getElementById('product_id_pp_' + id).value = productId;
    if (price) {
        document.getElementById('price_pp_' + id).value = price;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    addProductDetailPP();
});
</script>
    const price = selectedOption.getAttribute('data-price');
    
    document.getElementById('product_id_modal_pp_' + id).value = productId;
    if (price) {
        document.getElementById('price_modal_pp_' + id).value = price;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    addProductDetailPP();
});
</script>
@endsection