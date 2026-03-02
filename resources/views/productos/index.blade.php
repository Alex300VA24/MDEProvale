@extends('layouts.main')

@section('title', 'Productos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-box text-leaf"></i> Gestión de Productos
        </h3>
        <div class="flex gap-3">
            <a href="{{ route('productos.reportes') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('productos.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Producto
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('productos.index') }}" class="flex gap-4 px-6 py-4 flex-wrap border-b border-wheat bg-cream">
        <div class="flex-1 min-w-48">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o abreviatura..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="min-w-32">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
            <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>
                        {{ $state->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-primary">
                <i class="fas fa-search mr-1"></i> Buscar
            </button>
            <a href="{{ route('productos.index') }}" class="btn-secondary">
                <i class="fas fa-times mr-1"></i> Limpiar
            </a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-6 py-4 text-left">ID</th>
                    <th class="px-6 py-4 text-left">Nombre</th>
                    <th class="px-6 py-4 text-left">Abreviatura</th>
                    <th class="px-6 py-4 text-left">Stock</th>
                    <th class="px-6 py-4 text-left">Precio Unitario</th>
                    <th class="px-6 py-4 text-left">Estado</th>
                    <th class="px-6 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td class="px-6 text-earth font-mono text-sm">#{{ $product->id }}</td>
                    <td class="px-6 font-semibold">{{ $product->title ?? 'Sin nombre' }}</td>
                    <td class="px-6 text-earth">{{ $product->abbreviation ?? '-' }}</td>
                    <td class="px-6">
                        <span class="px-2 py-1 rounded-lg {{ $product->stock > 0 ? 'bg-leaf-light text-leaf' : 'bg-clay-light text-clay' }} text-xs font-bold">
                            {{ $product->stock }} {{ $product->uom->abbreviation ?? '' }}
                        </span>
                    </td>
                    <td class="px-6 text-earth font-mono">S/ {{ number_format($product->unit_price, 2) }}</td>
                    <td class="px-6">
                        @if($product->state)
                            @if($product->state->title == 'Activo')
                                <span class="badge-active px-3 py-1 rounded-full text-xs font-bold">Activo</span>
                            @else
                                <span class="badge-inactive px-3 py-1 rounded-full text-xs font-bold">Inactivo</span>
                            @endif
                        @else
                            <span class="badge-inactive px-3 py-1 rounded-full text-xs font-bold">Sin estado</span>
                        @endif
                    </td>
                    <td class="px-6">
                        <div class="flex gap-2">
                            <a href="{{ route('productos.show', $product) }}" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('productos.edit', $product) }}" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('productos.destroy', $product) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-earth">No hay registros</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-6 py-4 border-t-2 border-wheat">
        <span class="text-sm text-earth font-medium">Mostrando {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} de {{ $products->total() }} registros</span>
        {{ $products->appends(request()->query())->links() }}
    </div>
</div>
@endsection
