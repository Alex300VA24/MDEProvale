@extends('layouts.main')

@section('title', 'Crear Movimiento - PROVALE')

@section('content')
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
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto</label>
                <select name="product_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Tipo de Movimiento</label>
                <select name="type_transaction_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ old('type_transaction_id') == $type->id ? 'selected' : '' }}>{{ $type->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                <input type="number" name="quantity" value="{{ old('quantity', 0) }}" min="0" step="0.01" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Precio Unitario</label>
                <input type="number" name="unit_price" value="{{ old('unit_price', 0) }}" min="0" step="0.01" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
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
@endsection
