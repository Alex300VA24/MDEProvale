@extends('layouts.main')

@section('title', 'Movimientos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-exchange-alt text-leaf"></i> Gestión de Movimientos
        </h3>
        <div class="flex gap-3">
            <a href="{{ route('movimientos.reportes') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('movimientos.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Movimiento
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('movimientos.index') }}" class="flex gap-4 px-6 py-4 flex-wrap border-b border-wheat bg-cream">
        <div class="flex-1 min-w-48">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar Producto</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por producto..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="min-w-40">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Tipo de Movimiento</label>
            <select name="type_transaction_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($types as $type)
                    <option value="{{ $type->id }}" {{ request('type_transaction_id') == $type->id ? 'selected' : '' }}>
                        {{ $type->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-primary">
                <i class="fas fa-search mr-1"></i> Buscar
            </button>
            <a href="{{ route('movimientos.index') }}" class="btn-secondary">
                <i class="fas fa-times mr-1"></i> Limpiar
            </a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-6 py-4 text-left">ID</th>
                    <th class="px-6 py-4 text-left">Producto</th>
                    <th class="px-6 py-4 text-left">Tipo</th>
                    <th class="px-6 py-4 text-left">Cantidad</th>
                    <th class="px-6 py-4 text-left">Precio Unit.</th>
                    <th class="px-6 py-4 text-left">Total</th>
                    <th class="px-6 py-4 text-left">Fecha</th>
                    <th class="px-6 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                <tr>
                    <td class="px-6 text-earth font-mono text-sm">#{{ $transaction->id }}</td>
                    <td class="px-6 font-semibold">
                        {{ $transaction->product->title ?? 'Sin producto' }}
                    </td>
                    <td class="px-6">
                        @if($transaction->typeTransaction)
                            @if($transaction->typeTransaction->title == 'Ingreso')
                                <span class="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">
                                    <i class="fas fa-arrow-down mr-1"></i>{{ $transaction->typeTransaction->title }}
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-lg bg-clay-light text-clay text-xs font-bold">
                                    <i class="fas fa-arrow-up mr-1"></i>{{ $transaction->typeTransaction->title }}
                                </span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 text-earth font-mono">{{ $transaction->quantity }}</td>
                    <td class="px-6 text-earth font-mono">S/ {{ number_format($transaction->unit_price, 2) }}</td>
                    <td class="px-6 font-bold text-charcoal">S/ {{ number_format($transaction->total_price, 2) }}</td>
                    <td class="px-6 text-earth text-sm">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y') }}</td>
                    <td class="px-6">
                        <div class="flex gap-2">
                            <a href="{{ route('movimientos.show', $transaction) }}" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('movimientos.edit', $transaction) }}" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('movimientos.destroy', $transaction) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" onclick="return confirm('¿Estás seguro de eliminar este movimiento?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-earth">No hay registros</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-6 py-4 border-t-2 border-wheat">
        <span class="text-sm text-earth font-medium">Mostrando {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} de {{ $transactions->total() }} registros</span>
        {{ $transactions->appends(request()->query())->links() }}
    </div>
</div>
@endsection
