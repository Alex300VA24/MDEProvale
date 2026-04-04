@extends('layouts.main')

@section('title', 'Dashboard - PROVALE')

@section('content')
@php
    $totalPartners = \App\Models\Partner::count();
    $activePartners = \App\Models\Partner::where('state_id', 1)->count();
    $totalBeneficiaries = \App\Models\Beneficiarie::count();
    $totalAssociations = \App\Models\Association::count();
    $totalProducts = \App\Models\Product::count();
    
    // Socios por club (top 10)
    $partnersByClub = \App\Models\Partner::selectRaw('associations.name as club, COUNT(partners.id) as total')
        ->join('associations', 'partners.association_id', '=', 'associations.id')
        ->groupBy('associations.name')
        ->orderByDesc('total')
        ->limit(10)
        ->get();
    
    // PECOSAs por mes del año actual
    $pecosasByMonth = \App\Models\Pecosa::selectRaw('MONTH(delivery_date) as month, COUNT(*) as total')
        ->whereNotNull('delivery_date')
        ->whereYear('delivery_date', date('Y'))
        ->groupByRaw('MONTH(delivery_date)')
        ->get();
    $pecosaData = array_fill(0, 12, 0);
    foreach ($pecosasByMonth as $item) {
        $pecosaData[$item->month - 1] = $item->total;
    }
    
    // Ingresos por mes (transactions tipo 1 = entrada)
    $ingresosByMonth = \App\Models\Transaction::selectRaw('MONTH(transaction_date) as month, SUM(quantity) as total')
        ->where('type_transaction_id', 1)
        ->whereNotNull('transaction_date')
        ->whereYear('transaction_date', date('Y'))
        ->groupByRaw('MONTH(transaction_date)')
        ->get();
    $ingresosData = array_fill(0, 12, 0);
    foreach ($ingresosByMonth as $item) {
        $ingresosData[$item->month - 1] = (int)$item->total;
    }
    
    // Productos distribuidos (Leche y Hojuelas) por mes
    $productosByMonth = \App\Models\DetailPecosa::selectRaw('MONTH(pecosas.delivery_date) as month, SUM(detail_pecosas.quantity) as total, products.title as product')
        ->join('pecosas', 'detail_pecosas.pecosa_id', '=', 'pecosas.id')
        ->join('detail_products', 'detail_pecosas.detail_product_id', '=', 'detail_products.id')
        ->join('products', 'detail_products.product_id', '=', 'products.id')
        ->whereYear('pecosas.delivery_date', date('Y'))
        ->groupByRaw('MONTH(pecosas.delivery_date), products.title')
        ->get();
    
    $lecheData = array_fill(0, 12, 0);
    $hojuelasData = array_fill(0, 12, 0);
    foreach ($productosByMonth as $item) {
        $mes = $item->month - 1;
        if (stripos($item->product, 'leche') !== false) {
            $lecheData[$mes] = (int)$item->total;
        } elseif (stripos($item->product, 'hojuela') !== false) {
            $hojuelasData[$mes] = (int)$item->total;
        }
    }
    
    // Clubes con más PECOSAs
    $clubsWithPecosas = \App\Models\Pecosa::selectRaw('associations.name as club, COUNT(pecosas.id) as total')
        ->join('associations', 'pecosas.association_id', '=', 'associations.id')
        ->whereYear('pecosas.delivery_date', date('Y'))
        ->groupBy('associations.name')
        ->orderByDesc('total')
        ->limit(10)
        ->get();
    
    // Total stock disponible
    $totalStock = \App\Models\DetailProduct::get()->sum(function($dp) {
        $used = \App\Models\ProductStock::where('detail_product_id', $dp->id)->sum('quantity');
        return $dp->quantity - $used;
    });
    
    // Socios vs Beneficiarios
    $sociosActivos = $activePartners;
    $beneficiariosActivos = $totalBeneficiaries;
    
    // Total PECOSAs año actual
    $totalPecosasAnio = \App\Models\Pecosa::whereYear('delivery_date', date('Y'))->count();
@endphp

    <div class="relative rounded-3xl overflow-hidden mb-8 shadow-xl">
    <div class="absolute inset-0">
        <img src="{{ asset('img/banner2.png') }}" alt="Banner" class="w-full h-full object-cover">
    </div>
    <div class="absolute inset-0 bg-gradient-to-r from-primary/40 to-primary-dark/30"></div>
    <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 80% 50%, #F4A261 0%, transparent 60%);"></div>
    <div class="absolute -right-8 -top-8 w-64 h-64 bg-white/5 rounded-full"></div>
    <div class="absolute -right-4 bottom-0 w-40 h-40 bg-white/5 rounded-full"></div>
    <div class="relative z-10 flex items-center justify-between p-10 gap-8">
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-sun pulse-dot"></span>
                <span class="text-sun text-xs font-bold uppercase tracking-widest">Sistema activo</span>
            </div>
            <h1 class="text-white font-extrabold text-4xl leading-tight mb-3">Panel de Control<br><span class="text-sun">PROVALE</span></h1>
            <p class="text-white/75 text-base font-medium max-w-md">Gestiona beneficiarios, club de madres y entregas de manera eficiente. Todo en un solo lugar.</p>
            <div class="flex gap-3 mt-6">
                <a href="" class="px-5 py-2.5 bg-white text-primary font-bold rounded-xl text-sm hover:bg-teal-50 transition-all shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Crear Pecosa
                </a>
                <a href="" class="px-5 py-2.5 bg-white/15 text-white font-bold rounded-xl text-sm hover:bg-white/25 transition-all backdrop-blur-sm border border-white/20">
                    <i class="fas fa-file-alt mr-2"></i>Ver Movimientos
                </a>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="stat-card bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm relative overflow-hidden">
        <div class="accent-bar absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-leaf to-leaf-dark rounded-t-2xl"></div>
        <div class="flex items-center justify-between mb-5">
            <div class="w-12 h-12 rounded-xl bg-leaf-light flex items-center justify-center text-leaf text-xl">
                <i class="fas fa-users"></i>
            </div>
            <span class="text-[13px] font-bold text-leaf bg-leaf-light px-3 py-1 rounded-full"><i class="fas fa-arrow-up text-xs"></i> {{ $totalPartners > 0 ? round(($activePartners / $totalPartners) * 100) : 0 }}%</span>
        </div>
        <div class="text-4xl font-extrabold text-charcoal leading-none mb-2">{{ $totalPartners }}</div>
        <div class="text-xs font-bold text-earth uppercase tracking-wider">Total Socios</div>
    </div>

    <div class="stat-card bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm relative overflow-hidden">
        <div class="accent-bar absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-sky to-[#5ba3c5] rounded-t-2xl"></div>
        <div class="flex items-center justify-between mb-5">
            <div class="w-12 h-12 rounded-xl bg-sky-light flex items-center justify-center text-[#0284C7] text-xl">
                <i class="fas fa-user-check"></i>
            </div>
            <span class="text-[13px] font-bold text-[#0284C7] bg-sky-light px-3 py-1 rounded-full"><i class="fas fa-arrow-up text-xs"></i> {{ $totalBeneficiaries > 0 ? round(($totalBeneficiaries / max($totalPartners, 1)) * 100) : 0 }}%</span>
        </div>
        <div class="text-4xl font-extrabold text-charcoal leading-none mb-2">{{ $totalBeneficiaries }}</div>
        <div class="text-xs font-bold text-earth uppercase tracking-wider">Beneficiarios</div>
    </div>

    <div class="stat-card bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm relative overflow-hidden">
        <div class="accent-bar absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-sun to-[#e69553] rounded-t-2xl"></div>
        <div class="flex items-center justify-between mb-5">
            <div class="w-12 h-12 rounded-xl bg-sun-light flex items-center justify-center text-[#D97706] text-xl">
                <i class="fas fa-heart"></i>
            </div>
            <span class="text-[13px] font-bold text-[#D97706] bg-sun-light px-3 py-1 rounded-full"><i class="fas fa-arrow-up text-xs"></i> {{ $totalAssociations }}</span>
        </div>
        <div class="text-4xl font-extrabold text-charcoal leading-none mb-2">{{ $totalAssociations }}</div>
        <div class="text-xs font-bold text-earth uppercase tracking-wider">Club de Madres</div>
    </div>

    <div class="stat-card bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm relative overflow-hidden">
        <div class="accent-bar absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-clay to-[#d55d3f] rounded-t-2xl"></div>
        <div class="flex items-center justify-between mb-5">
            <div class="w-12 h-12 rounded-xl bg-clay-light flex items-center justify-center text-clay text-xl">
                <i class="fas fa-box"></i>
            </div>
            <span class="text-[13px] font-bold text-clay bg-clay-light px-3 py-1 rounded-full"><i class="fas fa-arrow-down text-xs"></i> {{ $totalStock > 500 ? 'Alto' : 'Normal' }}</span>
        </div>
        <div class="text-4xl font-extrabold text-charcoal leading-none mb-2">{{ $totalStock }}</div>
        <div class="text-xs font-bold text-earth uppercase tracking-wider">Stock Total</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-charcoal font-bold text-lg">PECOSAs por Mes</h3>
                <p class="text-earth text-sm font-medium">Salidas - {{ date('Y') }}</p>
            </div>
            <span class="px-3 py-1.5 text-xs font-bold bg-sun text-white rounded-lg">{{ $totalPecosasAnio }} total</span>
        </div>
        <div class="chart-wrap h-52">
            <canvas id="pecosasChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-charcoal font-bold text-lg">Productos Distribuidos</h3>
                <p class="text-earth text-sm font-medium">Leche y Hojuelas - {{ date('Y') }}</p>
            </div>
            <div class="flex gap-2">
                <span class="px-2 py-1 text-xs font-bold bg-blue-100 text-blue-700 rounded">Leche</span>
                <span class="px-2 py-1 text-xs font-bold bg-amber-100 text-amber-700 rounded">Hojuelas</span>
            </div>
        </div>
        <div class="chart-wrap h-52">
            <canvas id="productosChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <h3 class="text-charcoal font-bold text-lg mb-1">Socios vs Beneficiarios</h3>
        <p class="text-earth text-sm font-medium mb-4">Comparativa total</p>
        <div class="chart-wrap h-40">
            <canvas id="sociosBenefChart"></canvas>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="text-center p-3 bg-leaf-light rounded-xl">
                <div class="text-2xl font-extrabold text-leaf">{{ $sociosActivos }}</div>
                <div class="text-xs font-bold text-leaf uppercase">Socios</div>
            </div>
            <div class="text-center p-3 bg-sky-light rounded-xl">
                <div class="text-2xl font-extrabold text-[#0284C7]">{{ $beneficiariosActivos }}</div>
                <div class="text-xs font-bold text-[#0284C7] uppercase">Beneficiarios</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <h3 class="text-charcoal font-bold text-lg mb-1">Top Comités</h3>
        <p class="text-earth text-sm font-medium mb-4">Con más PECOSAs - {{ date('Y') }}</p>
        <div class="chart-wrap h-40">
            <canvas id="topClubsChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <h3 class="text-charcoal font-bold text-lg mb-5">Acciones Rápidas</h3>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('socios-beneficiarios.index') }}" class="quick-btn flex flex-col items-center gap-2 p-4 rounded-xl bg-green-100 hover:bg-green/20 transition-all group">
                <div class="w-10 h-10 bg-[#2EE67B] rounded-xl flex items-center justify-center text-white text-base group-hover:scale-110 transition-all">
                    <i class="fas fa-users"></i>
                </div>
                <span class="text-xs font-bold text-[#2EE67B] text-center leading-tight">Socios y Beneficiarios</span>
            </a>
            <a href="{{ route('club-reconocimientos.index') }}" class="quick-btn flex flex-col items-center gap-2 p-4 rounded-xl bg-sky-light hover:bg-sky/20 transition-all group">
                <div class="w-10 h-10 bg-[#0284C7] rounded-xl flex items-center justify-center text-white text-base group-hover:scale-110 transition-all">
                    <i class="fas fa-user-check"></i>
                </div>
                <span class="text-xs font-bold text-[#0284C7] text-center leading-tight">Club de Madres</span>
            </a>
            <a href="{{ route('productos-pecosas.index') }}" class="quick-btn flex flex-col items-center gap-2 p-4 rounded-xl bg-sun-light hover:bg-sun/20 transition-all group">
                <div class="w-10 h-10 bg-sun rounded-xl flex items-center justify-center text-white text-base group-hover:scale-110 transition-all">
                    <i class="fas fa-truck"></i>
                </div>
                <span class="text-xs font-bold text-[#D97706] text-center leading-tight">Productos y Pecosas</span>
            </a>
            <a href="{{ route('movimientos.index') }}" class="quick-btn flex flex-col items-center gap-2 p-4 rounded-xl bg-clay-light hover:bg-clay/20 transition-all group">
                <div class="w-10 h-10 bg-clay rounded-xl flex items-center justify-center text-white text-base group-hover:scale-110 transition-all">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <span class="text-xs font-bold text-clay text-center leading-tight">Movimientos</span>
            </a>
        </div>
    </div>
</div>

<script>
    const chartFont = { family: 'Plus Jakarta Sans', size: 11 };
    const gridColor = '#F5E6D3';
    const tickColor = '#8B7355';

    // Chart: PECOSAs por mes (Salidas)
    new Chart(document.getElementById('pecosasChart'), {
        type: 'bar',
        data: {
            labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
            datasets: [{
                label: 'PECOSAs',
                data: [{{ implode(',', $pecosaData) }}],
                backgroundColor: '#F59E0B',
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: chartFont, color: tickColor } },
                y: { grid: { color: gridColor }, ticks: { font: chartFont, color: tickColor, stepSize: 5 } }
            }
        }
    });

    // Chart: Productos distribuidos (Leche y Hojuelas)
    new Chart(document.getElementById('productosChart'), {
        type: 'line',
        data: {
            labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
            datasets: [
                {
                    label: 'Leche',
                    data: [{{ implode(',', $lecheData) }}],
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#3B82F6',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Hojuelas',
                    data: [{{ implode(',', $hojuelasData) }}],
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245,158,11,0.1)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#F59E0B',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'top', labels: { font: chartFont } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: chartFont, color: tickColor } },
                y: { grid: { color: gridColor }, ticks: { font: chartFont, color: tickColor } }
            }
        }
    });

    // Chart: Socios vs Beneficiarios
    new Chart(document.getElementById('sociosBenefChart'), {
        type: 'doughnut',
        data: {
            labels: ['Socios', 'Beneficiarios'],
            datasets: [{
                data: [{{ $sociosActivos }}, {{ $beneficiariosActivos }}],
                backgroundColor: ['#4A7C59', '#0EA5E9'],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: { legend: { display: false } }
        }
    });

    // Chart: Top Comités con más PECOSAs
    new Chart(document.getElementById('topClubsChart'), {
        type: 'bar',
        data: {
            labels: [@foreach($clubsWithPecosas as $club)'{{ substr($club->club, 0, 15) }}'@if(!$loop->last),@endif @endforeach],
            datasets: [{
                label: 'PECOSAs',
                data: [@foreach($clubsWithPecosas as $club){{ $club->total }}@if(!$loop->last),@endif @endforeach],
                backgroundColor: '#4A7C59',
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { font: chartFont, color: tickColor } },
                y: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, color: tickColor } }
            }
        }
    });
</script>
@endsection
