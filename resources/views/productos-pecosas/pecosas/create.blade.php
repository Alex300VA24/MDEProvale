@extends('layouts.main')

@section('title', 'Crear Pecosa - PROVALE')

@section('content')
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
        <!-- Sección de Registro Rápido de Producto -->
        <div class="mb-8 p-6 bg-leaf-light rounded-2xl border-2 border-leaf relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 pointer-events-none translate-x-1/4 -translate-y-1/4">
                <i class="fas fa-box-open text-9xl text-leaf"></i>
            </div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-leaf rounded-xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-plus-square text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-leaf text-lg">Registro Rápido de Producto</h4>
                        <p class="text-leaf-dark text-xs font-semibold">Crea un producto nuevo (ej. Leche, Cereal) para incluirlo en la pecosa.</p>
                    </div>
                </div>

                <form id="quick-product-form" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-bold text-leaf-dark uppercase tracking-wider mb-1">Nombre del Producto</label>
                        <input type="text" name="title" id="quick_title" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold focus:border-leaf outline-none" placeholder="Ej: Leche Evaporada">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-leaf-dark uppercase tracking-wider mb-1">Abreviatura</label>
                        <input type="text" name="abbreviation" id="quick_abbreviation" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold focus:border-leaf outline-none" placeholder="Ej: LE">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-leaf-dark uppercase tracking-wider mb-1">U. Medida</label>
                        <select name="uom_id" id="quick_uom" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold focus:border-leaf outline-none">
                            @foreach($uoms as $uom)
                                <option value="{{ $uom->id }}">{{ $uom->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-leaf-dark uppercase tracking-wider mb-1">Stock Inicial</label>
                        <input type="number" name="stock" id="quick_stock" value="0" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold focus:border-leaf outline-none">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-leaf-dark uppercase tracking-wider mb-1">P. Unitario</label>
                        <input type="number" step="0.01" name="unit_price" id="quick_price" value="0.00" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold focus:border-leaf outline-none">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-leaf-dark uppercase tracking-wider mb-1">Estado</label>
                        <select name="state_id" id="quick_state" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold focus:border-leaf outline-none">
                            @foreach($states as $state)
                                <option value="{{ $state->id }}">{{ $state->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-1 flex justify-end items-end">
                        <button type="button" onclick="registerQuickProduct()" class="btn-primary w-full flex items-center justify-center gap-2 shadow-md">
                            <i class="fas fa-save"></i> Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <form action="{{ route('productos-pecosas.pecosas.store') }}" method="POST">
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
                    <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar club...</option>
                        @foreach($associations as $association)
                            <option value="{{ $association->id }}">{{ $association->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Entrega</label>
                    <input type="date" name="delivery_date" value="{{ date('Y-m-d') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Responsable de Recepción</label>
                    <select name="managing_partner_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Seleccionar responsable...</option>
                        @foreach($partners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->people->names }} {{ $partner->people->father_lastname }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                    <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
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

<script>
let detailCount = 0;
let registeredProducts = @json($products);

function addProductDetail() {
    const container = document.getElementById('details-container');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat mb-4 animate-fade-in';
    div.id = 'detail-row-' + detailCount;
    
    let productOptions = '<option value="">Seleccionar producto...</option>';
    registeredProducts.forEach(p => {
        productOptions += `<option value="${p.id}" data-price="${p.unit_price}">${p.title} (${p.abbreviation})</option>`;
    });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-2">
            <span class="text-xs font-bold text-leaf uppercase">Ítem #${detailCount + 1}</span>
            <button type="button" onclick="removeDetail(${detailCount})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto</label>
                <select name="details[${detailCount}][product_id]" class="product-select w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required onchange="updatePrice(this, ${detailCount})">
                    ${productOptions}
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                <input type="number" step="0.01" name="details[${detailCount}][quantity]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">P. Unitario</label>
                <input type="number" step="0.01" name="details[${detailCount}][unit_price]" id="price_${detailCount}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
        </div>
    `;
    
    container.appendChild(div);
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

async function registerQuickProduct() {
    const form = document.getElementById('quick-product-form');
    const formData = new FormData(form);
    
    try {
        const response = await fetch("{{ route('productos-pecosas.productos.store-ajax') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            const product = result.product;
            registeredProducts.push(product);
            
            // Actualizar todos los selectores de productos existentes
            const selects = document.querySelectorAll('.product-select');
            selects.forEach(select => {
                const option = new Option(`${product.title} (${product.abbreviation})`, product.id);
                option.setAttribute('data-price', product.unit_price);
                select.add(option);
            });

            // Limpiar formulario rápido
            form.reset();
            alert('Producto registrado y habilitado en el detalle.');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error(error);
        alert('Ocurrió un error al procesar la solicitud.');
    }
}

// Agregar un ítem por defecto
document.addEventListener('DOMContentLoaded', function() {
    addProductDetail();
});
</script>
@endsection
