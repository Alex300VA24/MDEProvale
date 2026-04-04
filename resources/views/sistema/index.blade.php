@extends('layouts.main')

@section('title', 'Sistema - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-cog text-leaf"></i> Configuración del Sistema
        </h3>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                <div class="w-12 h-12 rounded-xl bg-sky-light flex items-center justify-center text-[#0284C7] text-xl mb-4">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-2">Gestión de Usuarios</h4>
                <p class="text-sm text-earth mb-4">Administrar usuarios y permisos del sistema</p>
                <a href="{{ route('sistema.usuarios') }}" class="btn-primary w-full text-center block">Administrar</a>
            </div>

            <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                <div class="w-12 h-12 rounded-xl bg-purple-light flex items-center justify-center text-purple-600 text-xl mb-4">
                    <i class="fas fa-user-tag"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-2">Gestión de Roles</h4>
                <p class="text-sm text-earth mb-4">Crear roles y asignar módulos del sistema</p>
                <a href="{{ route('sistema.roles') }}" class="btn-primary w-full text-center block">Administrar</a>
            </div>

            <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                <div class="w-12 h-12 rounded-xl bg-green-light flex items-center justify-center text-green-600 text-xl mb-4">
                    <i class="fas fa-th-large"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-2">Gestión de Módulos</h4>
                <p class="text-sm text-earth mb-4">Administrar módulos del sistema</p>
                <a href="{{ route('sistema.modulos') }}" class="btn-primary w-full text-center block">Administrar</a>
            </div>

            <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                <div class="w-12 h-12 rounded-xl bg-leaf-light flex items-center justify-center text-leaf text-xl mb-4">
                    <i class="fas fa-database"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-2">Respaldo de BD</h4>
                <p class="text-sm text-earth mb-4">Crear copia de seguridad de la base de datos</p>
                <button class="btn-primary w-full">Generar Backup</button>
            </div>

            <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                <div class="w-12 h-12 rounded-xl bg-clay-light flex items-center justify-center text-clay text-xl mb-4">
                    <i class="fas fa-history"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-2">Auditoría</h4>
                <p class="text-sm text-earth mb-4">Ver registros de auditoría del sistema</p>
                <button class="btn-primary w-full">Ver Logs</button>
            </div>
        </div>
    </div>
</div>
@endsection
