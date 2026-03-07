@extends('layouts.main')

@section('title', 'Reportes de Reconocimientos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-pdf text-leaf"></i> Reportes de Reconocimientos
        </h3>
        <a href="{{ route('club-reconocimientos.reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
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
                        <h4 class="font-bold text-charcoal">Todos los Reconocimientos</h4>
                        <p class="text-earth text-xs">Historial completo</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte completo de todos los reconocimientos.</p>
                <div class="flex gap-2">
                    <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button onclick="generarReporte('general')" class="btn-primary px-4">
                        <i class="fas fa-download"></i>
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
                        <p class="text-earth text-xs">Reconocimientos por club</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de reconocimientos de un club específico.</p>
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
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-sun rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Por Año</h4>
                        <p class="text-earth text-xs">Reconocimientos anuales</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de reconocimientos otorgados en un año.</p>
                <form class="space-y-2">
                    <input type="number" name="anio" placeholder="Año" value="{{ date('Y') }}" min="2020" max="{{ date('Y') }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf" required>
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('anio')" class="btn-primary px-4">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-clay rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Vigentes</h4>
                        <p class="text-earth text-xs">Reconocimientos activos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de reconocimientos actualmente vigentes.</p>
                <div class="flex gap-2">
                    <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button onclick="generarReporte('vigentes')" class="btn-primary px-4">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Estadístico</h4>
                        <p class="text-earth text-xs">Análisis y gráficos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte estadístico de reconocimientos por club.</p>
                <div class="flex gap-2">
                    <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button onclick="generarReporte('estadistico')" class="btn-primary px-4">
                        <i class="fas fa-download"></i>
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
    let url = '{{ route("club-reconocimientos.reconocimientos.generar-reporte", ":tipo") }}'.replace(':tipo', tipo);
    
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
