@extends('layouts.main')

@section('title', 'Productos y Pecosas - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-box text-leaf"></i> Productos y Pecosas
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('productos-pecosas.pecosas.programacion-entrega') }}" target="_blank" class="btn-secondary flex items-center gap-2" title="Programación de Entrega">
                <i class="fas fa-truck"></i> Programación Entrega
            </a>
            <a href="{{ route('productos-pecosas.pecosas.reportes') }}" class="btn-secondary flex items-center gap-2" title="Reportes">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('productos-pecosas.productos.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Producto
            </a>
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
                                <form action="{{ route('productos-pecosas.productos.destroy', $product) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('¿Estás seguro de eliminar este producto?')" title="Eliminar">
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
@endsection