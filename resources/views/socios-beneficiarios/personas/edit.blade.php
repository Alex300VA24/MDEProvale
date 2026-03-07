@extends('layouts.main')

@section('title', 'Editar Persona - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-user-edit text-leaf"></i> Editar Persona
        </h3>
        <a href="{{ route('socios-beneficiarios.personas.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <form action="{{ route('socios-beneficiarios.personas.update', $person) }}" method="POST" class="max-w-3xl">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">DNI *</label>
                    <input type="text" name="dni" maxlength="8" value="{{ old('dni', $person->dni) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required placeholder="8 dígitos">
                    @error('dni')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombres *</label>
                    <input type="text" name="names" value="{{ old('names', $person->names) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    @error('names')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Apellido Paterno *</label>
                    <input type="text" name="father_lastname" value="{{ old('father_lastname', $person->father_lastname) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    @error('father_lastname')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Apellido Materno *</label>
                    <input type="text" name="mother_lastname" value="{{ old('mother_lastname', $person->mother_lastname) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    @error('mother_lastname')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Nacimiento *</label>
                    <input type="date" name="birthdate" value="{{ old('birthdate', $person->birthdate) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    @error('birthdate')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Género *</label>
                    <select name="gender" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar...</option>
                        <option value="M" {{ old('gender', $person->gender) == 'M' ? 'selected' : '' }}>Masculino</option>
                        <option value="F" {{ old('gender', $person->gender) == 'F' ? 'selected' : '' }}>Femenino</option>
                    </select>
                    @error('gender')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Teléfono Casa</label>
                    <input type="text" name="telephone_number" maxlength="6" value="{{ old('telephone_number', $person->telephone_number) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Opcional">
                    @error('telephone_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Celular</label>
                    <input type="text" name="phone_number" maxlength="9" value="{{ old('phone_number', $person->phone_number) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Opcional">
                    @error('phone_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Sector *</label>
                    <select name="place_sector_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar sector...</option>
                        @foreach($placeSectors as $ps)
                            <option value="{{ $ps->id }}" {{ old('place_sector_id', $person->place_sector_id) == $ps->id ? 'selected' : '' }}>
                                {{ $ps->place->title ?? 'N/A' }} - {{ $ps->sector->title ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                    @error('place_sector_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Dirección *</label>
                    <input type="text" name="address" value="{{ old('address', $person->address) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    @error('address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Actualizar Persona
                </button>
                <a href="{{ route('socios-beneficiarios.personas.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
