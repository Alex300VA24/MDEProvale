@extends('layouts.main')

@section('title', 'Reportes de Socios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-pdf text-leaf"></i> Reportes de Socios
        </h3>
        <a href="{{ route('socios-beneficiarios.socios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <p class="text-earth mb-6">Seleccione el tipo de reporte que desea generar:</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all cursor-pointer">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-leaf rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Listado General</h4>
                        <p class="text-earth text-xs">Todos los socios</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte completo de todos los socios registrados.</p>
                <div class="flex gap-2">
                    <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button onclick="generarReporte('general')" class="btn-primary px-4">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                </div>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-[#0284C7] rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Por Club de Madres</h4>
                        <p class="text-earth text-xs">Agrupado por club</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de socios agrupados por club de madres.</p>
                <form class="space-y-2">
                    <select name="association_id" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf" required>
                        <option value="">Seleccione club</option>
                        @foreach(\App\Models\Association::all() as $association)
                        <option value="{{ $association->id }}">{{ $association->name }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('club')" class="btn-primary px-4">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-sun rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Por Estado</h4>
                        <p class="text-earth text-xs">Activos/Inactivos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de socios filtrados por estado.</p>
                <form class="space-y-2">
                    <select name="state_id" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf" required>
                        <option value="">Seleccione estado</option>
                        @foreach(\App\Models\State::all() as $state)
                        <option value="{{ $state->id }}">{{ $state->title }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('estado')" class="btn-primary px-4">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-clay rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Por Rango de Fechas</h4>
                        <p class="text-earth text-xs">Fecha de registro</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de socios registrados en un período específico.</p>
                <form class="space-y-2">
                    <input type="date" name="fecha_inicio" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf" required>
                    <input type="date" name="fecha_fin" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf" required>
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('fecha')" class="btn-primary px-4">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Estadístico</h4>
                        <p class="text-earth text-xs">Gráficos y análisis</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte con estadísticas y gráficos de socios.</p>
                <div class="flex gap-2">
                    <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button onclick="generarReporte('estadistico')" class="btn-primary px-4">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                </div>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Con Beneficiarios</h4>
                        <p class="text-earth text-xs">Socios y sus beneficiarios</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de socios con el detalle de sus beneficiarios.</p>
                <div class="flex gap-2">
                    <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button onclick="generarReporte('beneficiarios')" class="btn-primary px-4">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function generarReporte(tipo) {
        const card = event.target.closest('.report-card');
        const form = card.querySelector('form');
        let url = '{{ route("socios-beneficiarios.socios.generar-reporte", ":tipo") }}'.replace(':tipo', tipo);

        let orientacion = 'portrait';
        if (form) {
            const formData = new FormData(form);
            orientacion = formData.get('orientacion') || 'portrait';
            const params = new URLSearchParams(formData).toString();
            url += '?' + params;
        } else {
            const orientacionSelect = card.querySelector('select[name="orientacion"]');
            if (orientacionSelect) {
                orientacion = orientacionSelect.value;
            }
            url += '?orientacion=' + orientacion;
        }

        window.open(url, '_blank');
    }
</script>
@endsection
