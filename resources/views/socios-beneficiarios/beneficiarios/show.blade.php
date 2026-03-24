@extends('layouts.main')

@section('title', 'Ver Beneficiario - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-eye text-leaf"></i> Ver Beneficiario
        </h3>
        <a href="{{ route('socios-beneficiarios.beneficiarios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">ID</label>
                <p class="text-charcoal font-semibold">#{{ $beneficiarie->id }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre</label>
                <p class="text-charcoal font-semibold">
                    @if($beneficiarie->person)
                        {{ $beneficiarie->person->names }} {{ $beneficiarie->person->father_lastname }} {{ $beneficiarie->person->mother_lastname }}
                    @else
                        Sin nombre
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">DNI</label>
                <p class="text-charcoal font-semibold">{{ $beneficiarie->person->dni ?? 'Sin DNI' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Socio</label>
                <p class="text-charcoal font-semibold">
                    @if($beneficiarie->partner && $beneficiarie->partner->people)
                        {{ $beneficiarie->partner->people->names }} {{ $beneficiarie->partner->people->father_lastname }}
                    @else
                        Sin socio
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Relación</label>
                <p class="text-charcoal font-semibold">{{ $beneficiarie->relationship->title ?? 'Sin relación' }}</p>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('socios-beneficiarios.beneficiarios.edit', $beneficiarie) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <form id="form-delete-benef-show" action="{{ route('socios-beneficiarios.beneficiarios.destroy', $beneficiarie) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="button" class="btn-danger" onclick="confirmDelete('form-delete-benef-show', 'Se eliminará este beneficiario de forma permanente.')">
                    <i class="fas fa-trash mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
