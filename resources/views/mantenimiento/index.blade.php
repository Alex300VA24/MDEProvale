@extends('layouts.main')

@section('title', 'Mantenimiento - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-tools text-leaf"></i> Mantenimiento
        </h3>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                <div class="w-12 h-12 rounded-xl bg-leaf-light flex items-center justify-center text-leaf text-xl mb-4">
                    <i class="fas fa-building"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-2">Organizaciones</h4>
                <p class="text-sm text-earth mb-4">Gestionar organizaciones y sedes</p>
                <a href="{{ route('club-reconocimientos.club.index') }}" class="btn-primary w-full block text-center">Administrar</a>
            </div>

            <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                <div class="w-12 h-12 rounded-xl bg-sky-light flex items-center justify-center text-[#0284C7] text-xl mb-4">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-2">Ubigeo</h4>
                <p class="text-sm text-earth mb-4">Configurar departamentos, provincias y distritos</p>
                <button class="btn-secondary w-full">Configurar</button>
            </div>

            <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                <div class="w-12 h-12 rounded-xl bg-sun-light flex items-center justify-center text-[#D97706] text-xl mb-4">
                    <i class="fas fa-tags"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-2">Tablas Auxiliares</h4>
                <p class="text-sm text-earth mb-4">Administrar tablas de datos auxiliar</p>
                <button class="btn-secondary w-full">Ver Tablas</button>
            </div>
        </div>
    </div>
</div>
@endsection