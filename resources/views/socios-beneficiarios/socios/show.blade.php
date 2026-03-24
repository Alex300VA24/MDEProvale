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
                @foreach($partner->beneficiaries as $index => $beneficiary)
                    <div class="p-4 bg-gray-50 rounded-xl border-2 border-wheat mb-4">
                        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-3">
                            <span class="text-xs font-bold text-leaf uppercase">Beneficiario #{{ $index + 1 }}</span>
                        </div>

                        <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-3">Datos del Beneficiario</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">DNI</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->person->dni ?? 'Sin DNI' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->person->names ?? '' }} {{ $beneficiary->person->father_lastname ?? '' }} {{ $beneficiary->person->mother_lastname ?? '' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Relación</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->relationship->title ?? 'Sin relación' }}</p>
                            </div>
                        </div>

                        <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-3 mt-2 border-t border-wheat pt-3">Historial / Datos Clínicos</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Peso (kg)</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->weight ?? 'Sin dato' }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Talla (cm)</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->height ?? 'Sin dato' }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">HMG (g/dL)</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->hmg ?? 'Sin dato' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">F. Inicio Beneficio</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->date_begin ? \Carbon\Carbon::parse($beneficiary->date_begin)->format('d/m/Y') : 'Sin fecha' }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">F. Fin Beneficio</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->date_end ? \Carbon\Carbon::parse($beneficiary->date_end)->format('d/m/Y') : 'Sin fecha' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Tipo de Beneficio</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->typeBenefit->title ?? 'Sin tipo' }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->historyState->title ?? 'Sin estado' }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Motivo Descalificación</label>
                                <p class="text-charcoal font-semibold">{{ $beneficiary->reasonDisqualification->title ?? 'Ninguno' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-gray-500">No hay beneficiarios registrados</p>
            @endif
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('socios-beneficiarios.socios.edit', $partner) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <form id="form-delete-socio-show" action="{{ route('socios-beneficiarios.socios.destroy', $partner) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="button" class="btn-danger" onclick="confirmDelete('form-delete-socio-show', 'Se eliminará este socio y todos sus beneficiarios de forma permanente.')">
                    <i class="fas fa-trash mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
