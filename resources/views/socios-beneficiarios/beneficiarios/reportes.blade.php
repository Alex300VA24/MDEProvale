@extends('layouts.main')

@section('title', 'Reportes de Beneficiarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-pdf text-leaf"></i> Reportes de Beneficiarios
        </h3>
        <a href="{{ route('socios-beneficiarios.beneficiarios.index') }}" class="btn-secondary flex items-center gap-2">
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
                        <p class="text-earth text-xs">Todos los beneficiarios</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte completo de todos los beneficiarios.</p>
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
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Por Socio</h4>
                        <p class="text-earth text-xs">Beneficiarios de un socio</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de beneficiarios de un socio específico.</p>
                <form class="space-y-2">
                    <select name="partner_id" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf" required>
                        <option value="">Seleccione socio</option>
                        @foreach(\App\Models\Partner::with('people')->get() as $partner)
                        <option value="{{ $partner->id }}">
                            @if($partner->people)
                            {{ $partner->people->names }} {{ $partner->people->father_lastname }}
                            @else
                            Socio #{{ $partner->id }}
                            @endif
                        </option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('socio')" class="btn-primary px-4">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-sun rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-link"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Por Relación</h4>
                        <p class="text-earth text-xs">Tipo de parentesco</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de beneficiarios por tipo de relación.</p>
                <form class="space-y-2">
                    <select name="relationship_id" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf" required>
                        <option value="">Seleccione relación</option>
                        @foreach(\App\Models\Relationship::all() as $relationship)
                        <option value="{{ $relationship->id }}">{{ $relationship->title }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('relacion')" class="btn-primary px-4">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-clay rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Estadístico</h4>
                        <p class="text-earth text-xs">Análisis y gráficos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte estadístico de beneficiarios.</p>
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
                    <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Padrón PVL</h4>
                        <p class="text-earth text-xs">Club de Madres</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Padrón de beneficiarios del Programa Vaso de Leche.</p>
                <a href="{{ route('socios-beneficiarios.beneficiarios.padron') }}" class="btn-primary w-full flex items-center justify-center gap-2">
                    <i class="fas fa-file-pdf"></i> Generar Padrón
                </a>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-teal-500 rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Ficha de Beneficiario</h4>
                        <p class="text-earth text-xs">Formato individual</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Imprimir ficha individual de beneficiario.</p>
                <button onclick="window.open('{{ route('socios-beneficiarios.beneficiarios.imprimir') }}', '_blank')" class="btn-primary w-full flex items-center justify-center gap-2">
                    <i class="fas fa-print"></i> Imprimir Ficha
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function generarReporte(tipo) {
        const card = event.target.closest('.report-card');
        const form = card.querySelector('form');
        let url = '{{ route("socios-beneficiarios.beneficiarios.generar-reporte", ":tipo") }}'.replace(':tipo', tipo);

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
