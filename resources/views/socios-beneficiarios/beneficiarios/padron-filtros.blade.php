@extends('layouts.main')

@section('title', 'Padrón de Beneficiarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Padrón de Beneficiarios del PVL
        </h3>
        <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <p class="text-earth mb-6">Seleccione los filtros para generar el padrón de beneficiarios:</p>

        <form action="{{ route('socios-beneficiarios.beneficiarios.padron') }}" method="GET" target="_blank" class="space-y-6" data-no-loading>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Comité --}}
                <div>
                    <label class="block text-sm font-bold text-charcoal mb-2">
                        <i class="fas fa-building mr-1"></i> Comité / Club de Madres
                    </label>
                    <select name="association_id" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccione un comité</option>
                        @foreach($associations as $association)
                        <option value="{{ $association->id }}" {{ request('association_id') == $association->id ? 'selected' : '' }}>
                            {{ $association->code }} - {{ $association->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Mes --}}
                <div>
                    <label class="block text-sm font-bold text-charcoal mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i> Mes
                    </label>
                    <select name="month" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @php
                        $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                        @endphp
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $mes == $i ? 'selected' : '' }}>
                            {{ $mesesNombres[$i] }}
                            </option>
                            @endfor
                    </select>
                </div>

                {{-- Año --}}
                <div>
                    <label class="block text-sm font-bold text-charcoal mb-2">
                        <i class="fas fa-calendar mr-1"></i> Año
                    </label>
                    <select name="year" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @for($y = date('Y') - 10; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>
                            {{ $y }}
                            </option>
                            @endfor
                    </select>
                </div>
            </div>

            <div class="flex justify-center">
                <button type="submit" class="btn-primary px-8 py-3 text-lg flex items-center gap-3">
                    <i class="fas fa-file-pdf"></i> Generar Padrón de Beneficiarios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection