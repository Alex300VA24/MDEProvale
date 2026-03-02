@extends('layouts.main')

@section('title', 'Reportes de Movimientos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-pdf text-leaf"></i> Reportes de Movimientos
        </h3>
        <a href="{{ route('movimientos.index') }}" class="btn-secondary flex items-center gap-2">
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
                        <h4 class="font-bold text-charcoal text-lg">Todos los Movimientos</h4>
                        <p class="text-earth text-sm">Historial completo</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte completo de todos los movimientos registrados.</p>
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
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal text-lg">Solo Ingresos</h4>
                        <p class="text-earth text-sm">Entradas de productos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de movimientos de ingreso de productos.</p>
                <form action="#" method="GET" class="space-y-3">
                    <input type="date" name="fecha_inicio" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    <input type="date" name="fecha_fin" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    <select name="orientacion" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button type="button" onclick="generarReporte('ingresos')" class="w-full btn-primary">
                        <i class="fas fa-download mr-2"></i> Generar PDF
                    </button>
                </form>
            </div>

            <div class="report-card bg-gradient-to-br from-sun-light to-white border-2 border-sun rounded-2xl p-6 hover:shadow-lg transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-sun rounded-xl flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal text-lg">Solo Salidas</h4>
                        <p class="text-earth text-sm">Entregas de productos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de movimientos de salida de productos.</p>
                <form action="#" method="GET" class="space-y-3">
                    <input type="date" name="fecha_inicio" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    <input type="date" name="fecha_fin" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    <select name="orientacion" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button type="button" onclick="generarReporte('salidas')" class="w-full btn-primary">
                        <i class="fas fa-download mr-2"></i> Generar PDF
                    </button>
                </form>
            </div>

            <div class="report-card bg-gradient-to-br from-clay-light to-white border-2 border-clay rounded-2xl p-6 hover:shadow-lg transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-clay rounded-xl flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal text-lg">Por Producto</h4>
                        <p class="text-earth text-sm">Movimientos específicos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de movimientos de un producto específico.</p>
                <form action="#" method="GET" class="space-y-3">
                    <select name="product_id" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccione producto</option>
                        @foreach(\App\Models\Product::all() as $product)
                            <option value="{{ $product->id }}">{{ $product->title }}</option>
                        @endforeach
                    </select>
                    <select name="orientacion" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button type="button" onclick="generarReporte('producto')" class="w-full btn-primary">
                        <i class="fas fa-download mr-2"></i> Generar PDF
                    </button>
                </form>
            </div>

            <div class="report-card bg-gradient-to-br from-purple-100 to-white border-2 border-purple-300 rounded-2xl p-6 hover:shadow-lg transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-purple-600 rounded-xl flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal text-lg">Estadístico Mensual</h4>
                        <p class="text-earth text-sm">Análisis por mes</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte estadístico de movimientos por mes.</p>
                <select name="orientacion" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all mb-3">
                    <option value="portrait">📄 Vertical</option>
                    <option value="landscape">📃 Horizontal</option>
                </select>
                <button onclick="generarReporte('estadistico')" class="w-full btn-primary">
                    <i class="fas fa-download mr-2"></i> Generar PDF
                </button>
            </div>

            <div class="report-card bg-gradient-to-br from-green-100 to-white border-2 border-green-300 rounded-2xl p-6 hover:shadow-lg transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-green-600 rounded-xl flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal text-lg">Valorización</h4>
                        <p class="text-earth text-sm">Valor de movimientos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de valorización de movimientos.</p>
                <select name="orientacion" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all mb-3">
                    <option value="portrait">📄 Vertical</option>
                    <option value="landscape">📃 Horizontal</option>
                </select>
                <button onclick="generarReporte('valorizacion')" class="w-full btn-primary">
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
    let url = '{{ route("movimientos.generar-reporte", ":tipo") }}'.replace(':tipo', tipo);
    
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
