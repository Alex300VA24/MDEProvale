@extends('layouts.main')

@section('title', 'Crear Movimiento - PROVALE')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css" rel="stylesheet" />

<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-plus-circle text-leaf"></i> Nuevo Movimiento
        </h3>
        <a href="{{ route('movimientos.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('movimientos.store') }}" method="POST" class="p-6">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Tipo de Movimiento</label>
                <select id="type_transaction_id" name="type_transaction_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all select2" required onchange="toggleFields()">
                    <option value="">Seleccionar tipo</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}">{{ $type->title }}</option>
                    @endforeach
                </select>
            </div>
            <div id="pecosa_field" style="display: none;">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Pecosa (para Salida)</label>
                <select name="pecosa_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all select2">
                    <option value="">Seleccionar pecosa</option>
                    @foreach($pecosas as $pecosa)
                        <option value="{{ $pecosa->id }}">{{ $pecosa->pecosa_number }} - {{ $pecosa->association->name ?? 'Sin club' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto</label>
                <select name="product_id" id="product_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all select2" required>
                    <option value="">Seleccionar producto</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->title }} ({{ $product->abbreviation }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Precio Unitario</label>
                <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                <input type="number" name="quantity" id="quantity" step="0.01" min="0" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Documento</label>
                <input type="text" name="document_number" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Movimiento</label>
                <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
            </div>
            <div id="periodo_fields">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha Inicio (Período)</label>
                    <input type="date" name="start_date" id="start_date" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
            </div>
            <div id="periodo_fields_end">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha Fin (Período)</label>
                    <input type="date" name="end_date" id="end_date" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Guardar
            </button>
            <a href="{{ route('movimientos.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap',
        placeholder: 'Seleccione una opción',
        allowClear: true
    });
});

function toggleFields() {
    const typeSelect = document.getElementById('type_transaction_id');
    const pecosaField = document.getElementById('pecosa_field');
    const periodoFields = document.getElementById('periodo_fields');
    const periodoFieldsEnd = document.getElementById('periodo_fields_end');
    const typeText = typeSelect.options[typeSelect.selectedIndex]?.text?.toLowerCase() || '';
    
    if (typeText.includes('salida')) {
        pecosaField.style.display = 'block';
        periodoFields.style.display = 'none';
        periodoFieldsEnd.style.display = 'none';
        document.getElementById('start_date').removeAttribute('required');
        document.getElementById('end_date').removeAttribute('required');
    } else {
        pecosaField.style.display = 'none';
        periodoFields.style.display = 'block';
        periodoFieldsEnd.style.display = 'block';
    }
}
</script>
@endsection