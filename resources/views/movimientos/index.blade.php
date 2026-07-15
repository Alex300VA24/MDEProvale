@extends('layouts.main')

@section('title', 'Movimientos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat flex-wrap gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-exchange-alt text-leaf"></i> Gestión de Movimientos
        </h3>
        <div class="flex flex-wrap gap-3">
            @if(Auth::user()->canCreateModule('movimientos'))
            <button onclick="openModal('modal-crear-ingreso')" class="btn-primary flex items-center gap-2 text-xs sm:text-sm">
                <i class="fas fa-plus"></i> Nuevo Ingreso
            </button>
            @endif
            <a href="{{ route('movimientos.reparticion-tabla') }}" class="btn-secondary flex items-center gap-2 text-xs sm:text-sm">
                <i class="fas fa-clipboard-list"></i> Repartición
            </a>
        </div>
    </div>

    <form id="filtro-movimientos" method="GET" action="{{ route('movimientos.index') }}" class="flex flex-col sm:flex-row gap-2 sm:gap-4 px-6 py-4 flex-wrap border-b border-wheat bg-cream">
        <div class="w-full sm:w-72">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar Producto</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por producto..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="flex-1 min-w-44">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Tipo de Movimiento</label>
            <select name="type_transaction_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($types as $type)
                <option value="{{ $type->id }}" {{ request('type_transaction_id') == $type->id ? 'selected' : '' }}>{{ $type->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-36">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha Desde</label>
            <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full sm:w-auto px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="min-w-36">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha Hasta</label>
            <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="w-full sm:w-auto px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="flex items-end gap-2">
            <a href="{{ route('movimientos.index') }}" class="btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</a>
        </div>
    </form>

    <div id="movimientos-results">
    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="data-table w-full min-w-[700px] text-xs sm:text-sm">
            <thead>
                <tr>
                    <th class="px-3 sm:px-4 py-4 text-left">ID</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Producto</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Tipo</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Cantidad</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Precio Unit.</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Total</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Fecha</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                <tr>
                    <td class="px-3 sm:px-4 text-earth font-mono text-sm">#{{ $transaction->id }}</td>
                    <td class="px-3 sm:px-4 font-semibold">{{ $transaction->product->title ?? 'Sin producto' }}</td>
                    <td class="px-3 sm:px-4">
                        @if($transaction->typeTransaction)
                            @if($transaction->typeTransaction->title == 'Ingreso')
                                <span class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs font-bold"><i class="fas fa-arrow-down mr-1"></i>{{ $transaction->typeTransaction->title }}</span>
                            @else
                                <span class="px-2 py-1 rounded bg-red-100 text-red-700 text-xs font-bold"><i class="fas fa-arrow-up mr-1"></i>{{ $transaction->typeTransaction->title }}</span>
                            @endif
                        @else - @endif
                    </td>
                    <td class="px-3 sm:px-4 text-earth font-mono">{{ $transaction->quantity }}</td>
                    <td class="px-3 sm:px-4 text-earth font-mono">S/ {{ number_format($transaction->unit_price, 2) }}</td>
                    <td class="px-3 sm:px-4 font-bold text-charcoal">S/ {{ number_format($transaction->total_price, 2) }}</td>
                    <td class="px-3 sm:px-4 text-earth text-sm">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y') }}</td>
                    <td class="px-3 sm:px-4">
                        <div class="flex gap-1 sm:gap-2">
                            <button onclick="openModal('modal-ver-movimiento-{{ $transaction->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if(Auth::user()->canEditModule('movimientos') && $transaction->typeTransaction && $transaction->typeTransaction->title == 'Ingreso')
                            <button onclick="openModal('modal-editar-movimiento-{{ $transaction->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endif
                            @if(Auth::user()->canDeleteModule('movimientos'))
                            <form id="form-delete-mov-{{ $transaction->id }}" action="{{ route('movimientos.destroy', $transaction) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                    onclick="confirmDelete('form-delete-mov-{{ $transaction->id }}', 'Se eliminará este movimiento de forma permanente.')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-3 sm:px-4 py-8 text-center text-earth">No hay registros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-t-2 border-wheat">
        <span class="text-sm text-earth font-medium">Mostrando {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} de {{ $transactions->total() }} registros</span>
        {{ $transactions->appends(request()->query())->links() }}
@foreach($transactions as $transaction)

{{-- Modal Ver --}}
<div id="modal-ver-movimiento-{{ $transaction->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-lg mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-eye text-[#0284C7]"></i> Detalle del Movimiento
                </h3>
                <button onclick="closeModal('modal-ver-movimiento-{{ $transaction->id }}')" class="w-8 h-8 rounded bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">ID</p>
                    <p class="font-semibold text-charcoal">#{{ $transaction->id }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Producto</p>
                    <p class="font-semibold text-charcoal">{{ $transaction->product->title ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Tipo</p>
                    <p class="font-semibold text-charcoal">{{ $transaction->typeTransaction->title ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Cantidad</p>
                    <p class="font-semibold text-charcoal">{{ $transaction->quantity }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Precio Unitario</p>
                    <p class="font-semibold text-charcoal">S/ {{ number_format($transaction->unit_price, 2) }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Total</p>
                    <p class="font-bold text-charcoal text-lg">S/ {{ number_format($transaction->total_price, 2) }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha</p>
                    <p class="font-semibold text-charcoal">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i') }}</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 pb-6">
                <button type="button" onclick="closeModal('modal-ver-movimiento-{{ $transaction->id }}')" class="btn-secondary">Cerrar</button>
                <button onclick="closeModal('modal-ver-movimiento-{{ $transaction->id }}'); openModal('modal-editar-movimiento-{{ $transaction->id }}')" class="btn-primary">
                    <i class="fas fa-edit mr-2"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar --}}
<div id="modal-editar-movimiento-{{ $transaction->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-[#D97706]"></i> Editar Movimiento
                </h3>
                <button onclick="closeModal('modal-editar-movimiento-{{ $transaction->id }}')" class="w-8 h-8 rounded bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('movimientos.update', $transaction) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="type_transaction_id" value="{{ $transaction->type_transaction_id }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto</label>
                        <select name="product_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ $transaction->product_id == $product->id ? 'selected' : '' }}>{{ $product->title }} ({{ $product->abbreviation }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Precio Unitario</label>
                        <input type="number" name="unit_price" value="{{ $transaction->unit_price }}" step="0.01" min="0" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                        <input type="number" name="quantity" value="{{ $transaction->quantity }}" step="0.01" min="0" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Documento</label>
                        <input type="text" name="document_number" value="{{ $transaction->document_number }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Movimiento</label>
                        <input type="date" name="transaction_date" value="{{ $transaction->transaction_date ?? date('Y-m-d') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha Inicio (Período)</label>
                        <input type="date" name="start_date" value="{{ $transaction->detail_start_date ? \Carbon\Carbon::parse($transaction->detail_start_date)->format('Y-m-d') : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha Fin (Período)</label>
                        <input type="date" name="end_date" value="{{ $transaction->detail_end_date ? \Carbon\Carbon::parse($transaction->detail_end_date)->format('Y-m-d') : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-editar-movimiento-{{ $transaction->id }}')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach
    </div>
    </div>
</div>

{{-- Modal Crear Ingreso --}}
<div id="modal-crear-ingreso" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-plus-circle text-leaf"></i> Nuevo Ingreso
                </h3>
                <button onclick="closeModal('modal-crear-ingreso')" class="w-8 h-8 rounded bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('movimientos.store') }}" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="type_transaction_id" value="{{ $types->firstWhere('title', 'Ingreso')->id ?? '' }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Producto</label>
                        <select name="product_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccionar producto</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->title }} ({{ $product->abbreviation }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Precio Unitario</label>
                        <input type="number" name="unit_price" step="0.01" min="0" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Cantidad</label>
                        <input type="number" name="quantity" step="0.01" min="0" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Documento</label>
                        <input type="text" name="document_number" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Movimiento</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha Inicio (Período)</label>
                        <input type="date" name="start_date" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha Fin (Período)</label>
                        <input type="date" name="end_date" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-crear-ingreso')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales Ver / Editar por movimiento --}}

<script>
document.addEventListener('DOMContentLoaded', function() {
    window.initLiveFilter({
        formEl: document.getElementById('filtro-movimientos'),
        resultsSelector: '#movimientos-results',
        url: '{{ route("movimientos.index") }}',
    });
});

function toggleCreateFields() {
    const sel = document.getElementById('create_type_id');
    const title = sel.options[sel.selectedIndex]?.getAttribute('data-title') || '';
    const isSalida = title.includes('salida');
    document.getElementById('create_pecosa_field').classList.toggle('hidden', !isSalida);
    document.getElementById('create_start_date_field').classList.toggle('hidden', isSalida);
    document.getElementById('create_end_date_field').classList.toggle('hidden', isSalida);
}
</script>
@endsection
