@extends('layouts.main')

@section('title', 'Ver Socio - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-eye text-leaf"></i> Ver Socio
        </h3>
        <a href="{{ route('socios-beneficiarios.socios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">ID</label>
                <p class="text-charcoal font-semibold">#{{ $partner->id }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre</label>
                <p class="text-charcoal font-semibold">
                    @if($partner->people)
                        {{ $partner->people->names }} {{ $partner->people->father_lastname }} {{ $partner->people->mother_lastname }}
                    @else
                        Sin nombre
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">DNI</label>
                <p class="text-charcoal font-semibold">{{ $partner->people->dni ?? 'Sin DNI' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                <p class="text-charcoal font-semibold">{{ $partner->association->name ?? 'Sin club' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                <p class="text-charcoal font-semibold">{{ $partner->state->title ?? 'Sin estado' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Inicio</label>
                <p class="text-charcoal font-semibold">{{ \Carbon\Carbon::parse($partner->date_begin)->format('d/m/Y') }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Fin</label>
                <p class="text-charcoal font-semibold">{{ \Carbon\Carbon::parse($partner->date_end)->format('d/m/Y') }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                <p class="text-charcoal font-semibold">{{ $partner->observations ?? 'Sin observaciones' }}</p>
            </div>
        </div>

        <div class="mt-8 border-t-2 border-wheat pt-6">
            <h4 class="font-extrabold text-charcoal text-lg flex items-center gap-2 mb-4">
                <i class="fas fa-users text-leaf"></i> Beneficiarios
            </h4>
            
            @if($partner->beneficiaries && $partner->beneficiaries->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-bold text-earth">DNI</th>
                                <th class="px-4 py-2 text-left font-bold text-earth">Nombre</th>
                                <th class="px-4 py-2 text-left font-bold text-earth">Relación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($partner->beneficiaries as $beneficiary)
                                <tr>
                                    <td class="px-4 py-2">{{ $beneficiary->person->dni ?? 'Sin DNI' }}</td>
                                    <td class="px-4 py-2">{{ $beneficiary->person->names ?? '' }} {{ $beneficiary->person->father_lastname ?? '' }} {{ $beneficiary->person->mother_lastname ?? '' }}</td>
                                    <td class="px-4 py-2">{{ $beneficiary->relationship->title ?? 'Sin relación' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">No hay beneficiarios registrados</p>
            @endif
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('socios-beneficiarios.socios.edit', $partner) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <form action="{{ route('socios-beneficiarios.socios.destroy', $partner) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger" onclick="return confirm('¿Estás seguro de eliminar este socio y todos sus beneficiarios?')">
                    <i class="fas fa-trash mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
