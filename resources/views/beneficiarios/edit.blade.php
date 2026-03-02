@extends('layouts.main')

@section('title', 'Editar Beneficiario - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-edit text-leaf"></i> Editar Beneficiario
        </h3>
        <a href="{{ route('beneficiarios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('beneficiarios.update', $beneficiarie) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Persona</label>
                <select name="person_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach(\App\Models\People::all() as $person)
                        <option value="{{ $person->id }}" {{ old('person_id', $beneficiarie->person_id) == $person->id ? 'selected' : '' }}>
                            {{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }} - {{ $person->dni }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Socio</label>
                <select name="partner_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" {{ old('partner_id', $beneficiarie->partner_id) == $partner->id ? 'selected' : '' }}>
                            @if($partner->people)
                                {{ $partner->people->names }} {{ $partner->people->father_lastname }}
                            @else
                                Socio #{{ $partner->id }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Relación</label>
                <select name="relationship_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($relationships as $relationship)
                        <option value="{{ $relationship->id }}" {{ old('relationship_id', $beneficiarie->relationship_id) == $relationship->id ? 'selected' : '' }}>{{ $relationship->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Actualizar
            </button>
            <a href="{{ route('beneficiarios.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
