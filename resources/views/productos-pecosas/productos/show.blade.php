@extends('layouts.main')

@section('title', 'Ver Producto - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-eye text-leaf"></i> Ver Producto
        </h3>
        <a href="{{ route('productos-pecosas.productos.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">ID</label>
                <p class="text-charcoal font-semibold">#{{ $product->id }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre</label>
                <p class="text-charcoal font-semibold">{{ $product->title ?? 'Sin nombre' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Abreviatura</label>
                <p class="text-charcoal font-semibold">{{ $product->abbreviation ?? 'Sin abreviatura' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Stock</label>
                <p class="text-charcoal font-semibold">{{ $product->stock }} {{ $product->uom->abbreviation ?? '' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Precio Unitario</label>
                <p class="text-charcoal font-semibold">S/ {{ number_format($product->unit_price, 2) }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Unidad de Medida</label>
                <p class="text-charcoal font-semibold">{{ $product->uom->title ?? 'Sin unidad' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                <p class="text-charcoal font-semibold">{{ $product->state->title ?? 'Sin estado' }}</p>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('productos-pecosas.productos.edit', $product) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <form id="form-delete-prod-show" action="{{ route('productos-pecosas.productos.destroy', $product) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="button" class="btn-danger" onclick="confirmDelete('form-delete-prod-show', 'Se eliminará este producto de forma permanente.')">
                    <i class="fas fa-trash mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
