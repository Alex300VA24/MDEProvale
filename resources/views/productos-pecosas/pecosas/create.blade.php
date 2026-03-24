@extends('layouts.main')

@section('title', 'Crear Pecosa - PROVALE')
@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css" rel="stylesheet" />

<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-plus-circle text-leaf"></i> Nueva Pecosa
        </h3>
        <a href="{{ route('productos-pecosas.pecosas.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <form action="{{ route('productos-pecosas.pecosas.store') }}" method="POST" id="pecosa-form">
            @csrf
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
                    <select name="association_id" id="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all select2" required onchange="loadPresidenta()">
                        <option value="">Seleccionar club...</option>
                        @foreach($associations as $association)
                            <option value="{{ $association->id }}" data-president-id="{{ $association->president_partner_id }}">
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
                    <input type="text" id="president_name" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-gray-100" readonly placeholder="Se autocompletará al seleccionar club">
                    <input type="hidden" name="managing_partner_id" id="president_id" value="">
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
                    <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all select2" required>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}">{{ $state->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                    <textarea name="observation" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Detalles adicionales de la entrega..."></textarea>
                </div>
            </div>

            <div class="mt-8 border-t-2 border-wheat pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                        <i class="fas fa-list text-leaf"></i> Detalle de Productos
                    </h4>
                    <button type="button" onclick="addProductDetail()" class="btn-secondary text-sm">
                        <i class="fas fa-plus mr-1"></i> Agregar Producto
                    </button>
                </div>
                
                <div id="details-container" class="space-y-4">
                </div>
            </div>

            <div class="flex gap-3 mt-10">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Guardar Pecosa
                </button>
                <a href="{{ route('productos-pecosas.pecosas.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let detailCount = 0;
let detailProductsList = @json($detailProductsList ?? []);

function fmtDate(d) {
    if (!d) return '';
    const parts = d.split('-');
    return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : d;
}

console.log('detailProductsList:', detailProductsList);

if (detailProductsList.length === 0) {
    console.warn('No hay productos disponibles. Asegúrate de crear ingresos primero.');
}

$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap',
        placeholder: 'Seleccione una opción',
        allowClear: true
    });
    
    // Auto-add first product row
    addProductDetail();
});

function loadPresidenta() {
    const associationSelect = document.getElementById('association_id');
    const selectedOption = associationSelect.options[associationSelect.selectedIndex];
    const presidentId = selectedOption.getAttribute('data-president-id');
    
    document.getElementById('president_id').value = presidentId || '';
    document.getElementById('president_name').value = presidentId ? 'Presidenta seleccionada (ID: ' + presidentId + ')' : '';
}

function addProductDetail() {
    const container = document.getElementById('details-container');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat mb-4 animate-fade-in';
    div.id = 'detail-row-' + detailCount;
    
    let productOptions = '<option value="">Seleccionar producto...</option>';
    detailProductsList.forEach(dp => {
        if (dp.available_stock > 0) {
            const productName = dp.product ? dp.product.title + ' (' + dp.product.abbreviation + ')' : 'Sin nombre';
            const period = fmtDate(dp.start_date) + ' al ' + fmtDate(dp.end_date);
            productOptions += `<option value="${dp.id}" data-product-id="${dp.product_id}" data-price="${dp.unit_price}" data-stock="${dp.available_stock}">${productName} - Stock: ${dp.available_stock} (${period})</option>`;
        }
    });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-2">
            <span class="text-xs font-bold text-leaf uppercase">Ítem #${detailCount + 1}</span>
            <button type="button" onclick="removeDetail(${detailCount})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto (Detalle)</label>
                <select name="details[${detailCount}][detail_product_id]" class="product-select w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all select2" required onchange="updateDetailPrice(this, ${detailCount})">
                    ${productOptions}
                </select>
                <input type="hidden" name="details[${detailCount}][product_id]" id="product_id_${detailCount}">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                <input type="number" step="0.01" name="details[${detailCount}][quantity]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="1">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">P. Unitario (S/)</label>
                <input type="number" step="0.01" name="details[${detailCount}][unit_price]" id="price_${detailCount}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="0" step="0.01">
            </div>
        </div>
    `;
    
    container.appendChild(div);
    
    $(div).find('.select2').select2({
        theme: 'bootstrap',
        placeholder: 'Seleccionar producto',
        allowClear: true
    });
    
    detailCount++;
}

function removeDetail(id) {
    document.getElementById('detail-row-' + id).remove();
}

function updatePrice(select, id) {
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    if (price) {
        document.getElementById('price_' + id).value = price;
    }
}

function updateDetailPrice(select, id) {
    const selectedOption = select.options[select.selectedIndex];
    const productId = selectedOption.getAttribute('data-product-id');
    const price = selectedOption.getAttribute('data-price');
    
    document.getElementById('product_id_' + id).value = productId;
    if (price) {
        document.getElementById('price_' + id).value = price;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    addProductDetail();
});
</script>
@endsection