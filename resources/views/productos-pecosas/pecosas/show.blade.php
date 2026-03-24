@extends('layouts.main')

@section('title', 'Ver Pecosa - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-eye text-leaf"></i> Detalle de Pecosa
        </h3>
        <div class="flex gap-2">
            <a href="{{ route('productos-pecosas.pecosas.comprobante', $pecosa) }}" target="_blank" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-print"></i> Imprimir
            </a>
            <a href="{{ route('productos-pecosas.pecosas.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="p-6">
        <div class="bg-cream rounded-xl p-6 mb-6 border-2 border-wheat">
            <h4 class="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                <i class="fas fa-file-invoice text-leaf"></i> Información de la Pecosa
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Pecosa</label>
                    <p class="text-charcoal font-semibold text-lg">{{ $pecosa->pecosa_number }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                    <p class="text-charcoal font-semibold">{{ $pecosa->association->name ?? 'Sin club' }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Entrega</label>
                    <p class="text-charcoal font-semibold">{{ date('d/m/Y', strtotime($pecosa->delivery_date)) }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Responsable de Recepción</label>
                    <p class="text-charcoal font-semibold">
                        @if($pecosa->managingPartner && $pecosa->managingPartner->people)
                            {{ $pecosa->managingPartner->people->names }} {{ $pecosa->managingPartner->people->father_lastname }}
                        @else
                            Sin responsable
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full 
                        @if($pecosa->state->title == 'Activo') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ $pecosa->state->title ?? 'Sin estado' }}
                    </span>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                    <p class="text-charcoal">{{ $pecosa->observation ?? 'Sin observaciones' }}</p>
                </div>
            </div>
        </div>

        <div class="border-2 border-wheat rounded-xl overflow-hidden">
            <div class="bg-leaf-light px-6 py-4 border-b-2 border-wheat">
                <h4 class="font-extrabold text-leaf text-lg flex items-center gap-2">
                    <i class="fas fa-list"></i> Detalle de Productos
                </h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-earth">#</th>
                            <th class="px-4 py-3 text-left font-bold text-earth">Producto</th>
                            <th class="px-4 py-3 text-center font-bold text-earth">Cant. Solicitada</th>
                            <th class="px-4 py-3 text-center font-bold text-earth">Cant. Entregada</th>
                            <th class="px-4 py-3 text-right font-bold text-earth">P. Unitario</th>
                            <th class="px-4 py-3 text-right font-bold text-earth">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @php $total = 0; @endphp
                        @forelse($pecosa->detailPecosas as $index => $detail)
                        @php 
                            $subtotal = $detail->quantity * $detail->unit_price;
                            $total += $subtotal;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-charcoal">{{ $detail->detailProduct->product->title ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $detail->detailProduct->product->abbreviation ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">{{ number_format($detail->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-center">{{ number_format($detail->delivered_quantity ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right">S/ {{ number_format($detail->unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold">S/ {{ number_format($subtotal, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No hay productos registrados
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right font-bold text-charcoal">TOTAL:</td>
                            <td class="px-4 py-3 text-right font-bold text-leaf text-lg">S/ {{ number_format($total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('productos-pecosas.pecosas.edit', $pecosa) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <form id="form-delete-pecosa-show" action="{{ route('productos-pecosas.pecosas.destroy', $pecosa) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="button" class="btn-danger" onclick="confirmDelete('form-delete-pecosa-show', 'Se eliminará esta pecosa de forma permanente.')">
                    <i class="fas fa-trash mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection