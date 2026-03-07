@extends('layouts.main')

@section('title', 'Reportes de Movimientos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
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
            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all cursor-pointer">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-leaf rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Todos los Movimientos</h4>
                        <p class="text-earth text-xs">Historial completo</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte completo de todos los movimientos.</p>
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
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Solo Ingresos</h4>
                        <p class="text-earth text-xs">Entradas de productos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de movimientos de ingreso.</p>
                <form class="space-y-2">
                    <input type="date" name="fecha_inicio" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                    <input type="date" name="fecha_fin" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('ingresos')" class="btn-primary px-4">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-sun rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Solo Salidas</h4>
                        <p class="text-earth text-xs">Entregas de productos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de movimientos de salida.</p>
                <form class="space-y-2">
                    <input type="date" name="fecha_inicio" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                    <input type="date" name="fecha_fin" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('salidas')" class="btn-primary px-4">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-clay rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Por Producto</h4>
                        <p class="text-earth text-xs">Movimientos específicos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de movimientos de un producto.</p>
                <form class="space-y-2">
                    <select name="product_id" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf" required>
                        <option value="">Seleccione producto</option>
                        @foreach(\App\Models\Product::all() as $product)
                            <option value="{{ $product->id }}">{{ $product->title }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('producto')" class="btn-primary px-4">
                            <i class="fas fa-download"></i>
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
                        <h4 class="font-bold text-charcoal">Estadístico Mensual</h4>
                        <p class="text-earth text-xs">Análisis por mes</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte estadístico de movimientos por mes.</p>
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

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Valorización</h4>
                        <p class="text-earth text-xs">Valor de movimientos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de valorización de movimientos.</p>
                <div class="flex gap-2">
                    <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button onclick="generarReporte('valorizacion')" class="btn-primary px-4">
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
