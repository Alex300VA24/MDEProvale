@extends('layouts.main')

@section('title', 'Ver Persona - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-user text-leaf"></i> Datos de la Persona
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('socios-beneficiarios.personas.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('socios-beneficiarios.personas.edit', $person) }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-4 bg-gray-50 rounded-xl">
                <p class="text-xs font-bold text-earth uppercase mb-1">DNI</p>
                <p class="font-mono text-lg font-semibold text-charcoal">{{ $person->dni }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
                <p class="text-xs font-bold text-earth uppercase mb-1">Género</p>
                <p class="text-lg font-semibold text-charcoal">
                    {{ $person->gender == 'M' ? 'Masculino' : 'Femenino' }}
                </p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
                <p class="text-xs font-bold text-earth uppercase mb-1">Nombres</p>
                <p class="text-lg font-semibold text-charcoal">{{ $person->names }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
                <p class="text-xs font-bold text-earth uppercase mb-1">Apellidos</p>
                <p class="text-lg font-semibold text-charcoal">{{ $person->father_lastname }} {{ $person->mother_lastname }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
                <p class="text-xs font-bold text-earth uppercase mb-1">Fecha de Nacimiento</p>
                <p class="text-lg font-semibold text-charcoal">{{ \Carbon\Carbon::parse($person->birthdate)->format('d/m/Y') }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
                <p class="text-xs font-bold text-earth uppercase mb-1">Edad</p>
                <p class="text-lg font-semibold text-leaf">{{ $person->age_formatted }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
                <p class="text-xs font-bold text-earth uppercase mb-1">Teléfono Casa</p>
                <p class="text-lg font-semibold text-charcoal">{{ $person->telephone_number ?? 'No registrado' }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
                <p class="text-xs font-bold text-earth uppercase mb-1">Celular</p>
                <p class="text-lg font-semibold text-charcoal">{{ $person->phone_number ?? 'No registrado' }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl md:col-span-2">
                <p class="text-xs font-bold text-earth uppercase mb-1">Dirección</p>
                <p class="text-lg font-semibold text-charcoal">{{ $person->address }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl md:col-span-2">
                <p class="text-xs font-bold text-earth uppercase mb-1">Sector</p>
                <p class="text-lg font-semibold text-charcoal">
                    {{ $person->placeSector->place->title ?? 'N/A' }} - {{ $person->placeSector->sector->title ?? 'N/A' }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
