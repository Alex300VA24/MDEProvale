@extends('layouts.main')

@section('title', 'Ver Movimiento - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-eye text-leaf"></i> Ver Movimiento
        </h3>
        <a href="{{ route('movimientos.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">ID</label>
                <p class="text-charcoal font-semibold">#{{ $transaction->id }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto</label>
                <p class="text-charcoal font-semibold">{{ $transaction->product->title ?? 'Sin producto' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Tipo de Movimiento</label>
                <p class="text-charcoal font-semibold">{{ $transaction->typeTransaction->title ?? 'Sin tipo' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                <p class="text-charcoal font-semibold">{{ $transaction->quantity }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Precio Unitario</label>
                <p class="text-charcoal font-semibold">S/ {{ number_format($transaction->unit_price, 2) }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Precio Total</label>
                <p class="text-charcoal font-semibold">S/ {{ number_format($transaction->total_price, 2) }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha</label>
                <p class="text-charcoal font-semibold">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('movimientos.edit', $transaction) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <form action="{{ route('movimientos.destroy', $transaction) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger" onclick="return confirm('¿Estás seguro de eliminar este movimiento?')">
                    <i class="fas fa-trash mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
