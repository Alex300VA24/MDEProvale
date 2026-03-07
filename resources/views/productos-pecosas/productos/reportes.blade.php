@extends('layouts.main')

@section('title', 'Reportes de Productos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-pdf text-leaf"></i> Reportes de Productos
        </h3>
        <a href="{{ route('productos-pecosas.productos.index') }}" class="btn-secondary flex items-center gap-2">
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
                        <h4 class="font-bold text-charcoal">Inventario General</h4>
                        <p class="text-earth text-xs">Todos los productos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte completo del inventario de productos.</p>
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
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Stock Bajo</h4>
                        <p class="text-earth text-xs">Productos con poco stock</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de productos con stock bajo o agotado.</p>
                <form class="space-y-2">
                    <input type="number" name="stock_minimo" placeholder="Stock mínimo" value="10" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                    <div class="flex gap-2">
                        <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                            <option value="portrait">📄 Vertical</option>
                            <option value="landscape">📃 Horizontal</option>
                        </select>
                        <button type="button" onclick="generarReporte('stock-bajo')" class="btn-primary px-4">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-sun rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Valorización</h4>
                        <p class="text-earth text-xs">Valor del inventario</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de valorización total del inventario.</p>
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

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-clay rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Con Movimientos</h4>
                        <p class="text-earth text-xs">Productos y transacciones</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de productos con historial de movimientos.</p>
                <div class="flex gap-2">
                    <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button onclick="generarReporte('movimientos')" class="btn-primary px-4">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>

            <div class="report-card bg-cream border-2 border-wheat rounded-xl p-5 hover:border-leaf hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center text-white text-xl">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-charcoal">Más Utilizados</h4>
                        <p class="text-earth text-xs">Top productos</p>
                    </div>
                </div>
                <p class="text-sm text-earth mb-4">Reporte de productos más utilizados.</p>
                <div class="flex gap-2">
                    <select name="orientacion" class="flex-1 px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf">
                        <option value="portrait">📄 Vertical</option>
                        <option value="landscape">📃 Horizontal</option>
                    </select>
                    <button onclick="generarReporte('top')" class="btn-primary px-4">
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
        let url = '{{ route("productos-pecosas.productos.generar-reporte", ":tipo") }}'.replace(':tipo', tipo);

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
