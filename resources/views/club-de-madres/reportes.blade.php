@extends('layouts.main')

@section('title', 'Reportes de Club de Madres - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-pdf text-leaf"></i> Reportes de Club de Madres
        </h3>
        <a href="{{ route('club-de-madres.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <p class="text-earth mb-6">Seleccione el tipo de reporte que desea generar:</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="report-card bg-gradient-to-br from-leaf-light to-white border-2 border-leaf rounded-2xl p-6 hover:shadow-lg transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-leaf rounded-xl flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal text-lg">Listado General</h4>
                        <p class="text-earth text-sm">Todos los clubes</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte completo de todos los clubes de madres registrados.</p>
                <select name="orientacion" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all mb-3">
                    <option value="portrait">📄 Vertical</option>
                    <option value="landscape">📃 Horizontal</option>
                </select>
                <button onclick="generarReporte('general')" class="w-full btn-primary">
                    <i class="fas fa-download mr-2"></i> Generar PDF
                </button>
            </div>

            <div class="report-card bg-gradient-to-br from-sky-light to-white border-2 border-sky rounded-2xl p-6 hover:shadow-lg transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-[#0284C7] rounded-xl flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal text-lg">Con Socios</h4>
                        <p class="text-earth text-sm">Clubes y sus socios</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de clubes con el detalle de sus socios.</p>
                <select name="orientacion" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all mb-3">
                    <option value="portrait">📄 Vertical</option>
                    <option value="landscape">📃 Horizontal</option>
                </select>
                <button onclick="generarReporte('socios')" class="w-full btn-primary">
                    <i class="fas fa-download mr-2"></i> Generar PDF
                </button>
            </div>

            <div class="report-card bg-gradient-to-br from-sun-light to-white border-2 border-sun rounded-2xl p-6 hover:shadow-lg transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-sun rounded-xl flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal text-lg">Estadístico</h4>
                        <p class="text-earth text-sm">Análisis y gráficos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte estadístico con cantidad de socios por club.</p>
                <select name="orientacion" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all mb-3">
                    <option value="portrait">📄 Vertical</option>
                    <option value="landscape">📃 Horizontal</option>
                </select>
                <button onclick="generarReporte('estadistico')" class="w-full btn-primary">
                    <i class="fas fa-download mr-2"></i> Generar PDF
                </button>
            </div>

            <div class="report-card bg-gradient-to-br from-clay-light to-white border-2 border-clay rounded-2xl p-6 hover:shadow-lg transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-clay rounded-xl flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-award"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal text-lg">Con Reconocimientos</h4>
                        <p class="text-earth text-sm">Clubes premiados</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de clubes con sus reconocimientos.</p>
                <select name="orientacion" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all mb-3">
                    <option value="portrait">📄 Vertical</option>
                    <option value="landscape">📃 Horizontal</option>
                </select>
                <button onclick="generarReporte('reconocimientos')" class="w-full btn-primary">
                    <i class="fas fa-download mr-2"></i> Generar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function generarReporte(tipo) {
    const card = event.target.closest('.report-card');
    const form = card.querySelector('form');
    let url = '{{ route("club-de-madres.generar-reporte", ":tipo") }}'.replace(':tipo', tipo);
    
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
