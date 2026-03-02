@extends('layouts.main')

@section('title', 'Editar Producto - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-edit text-leaf"></i> Editar Producto
        </h3>
        <a href="{{ route('productos.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('productos.update', $product) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Producto</label>
                <input type="text" name="title" value="{{ old('title', $product->title) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Abreviatura</label>
                <input type="text" name="abbreviation" value="{{ old('abbreviation', $product->abbreviation) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Stock</label>
                <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" min="0" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Precio Unitario</label>
                <input type="number" name="unit_price" value="{{ old('unit_price', $product->unit_price) }}" min="0" step="0.01" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Unidad de Medida</label>
                <select name="uom_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($uoms as $uom)
                        <option value="{{ $uom->id }}" {{ old('uom_id', $product->uom_id) == $uom->id ? 'selected' : '' }}>{{ $uom->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ old('state_id', $product->state_id) == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Actualizar
            </button>
            <a href="{{ route('productos.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
