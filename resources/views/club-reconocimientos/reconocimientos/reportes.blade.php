@extends('layouts.main')

@section('title', 'Reportes de Resoluciones - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-pdf text-leaf"></i> Reportes de Resoluciones
        </h3>
        <a href="{{ route('club-reconocimientos.reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Reporte General -->
            <div class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl border-2 border-blue-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-list text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Listado General</h4>
                        <p class="text-xs text-earth">Todas las resoluciones</p>
                    </div>
                </div>
                <form action="{{ route('club-reconocimientos.reconocimientos.generar-reporte', 'general') }}" method="GET" target="_blank">
                    <button type="submit" class="w-full btn-primary">
                        <i class="fas fa-download mr-2"></i> Generar PDF
                    </button>
                </form>
            </div>

            <!-- Reporte por Año -->
            <div class="p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border-2 border-green-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-calendar text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Por Año</h4>
                        <p class="text-xs text-earth">Resoluciones de un año específico</p>
                    </div>
                </div>
                <form action="{{ route('club-reconocimientos.reconocimientos.generar-reporte', 'anio') }}" method="GET" target="_blank">
                    <input type="number" name="anio" value="{{ date('Y') }}" min="2000" max="{{ date('Y') + 10 }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all mb-3" required>
                    <button type="submit" class="w-full btn-primary">
                        <i class="fas fa-download mr-2"></i> Generar PDF
                    </button>
                </form>
            </div>

            <!-- Reporte Vigentes -->
            <div class="p-6 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-2xl border-2 border-yellow-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Vigentes</h4>
                        <p class="text-xs text-earth">Resoluciones actualmente vigentes</p>
                    </div>
                </div>
                <form action="{{ route('club-reconocimientos.reconocimientos.generar-reporte', 'vigentes') }}" method="GET" target="_blank">
                    <button type="submit" class="w-full btn-primary">
                        <i class="fas fa-download mr-2"></i> Generar PDF
                    </button>
                </form>
            </div>

            <!-- Reporte Estadístico -->
            <div class="p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl border-2 border-purple-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-chart-bar text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Estadístico</h4>
                        <p class="text-xs text-earth">Análisis y estadísticas</p>
                    </div>
                </div>
                <form action="{{ route('club-reconocimientos.reconocimientos.generar-reporte', 'estadistico') }}" method="GET" target="_blank">
                    <button type="submit" class="w-full btn-primary">
                        <i class="fas fa-download mr-2"></i> Generar PDF
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
