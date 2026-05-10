@extends('layouts.main')

@section('title', 'Dashboard - PROVALE')

@section('content')
@php
    $totalPartners = \App\Models\Partner::count();
    $activePartners = \App\Models\Partner::where('state_id', 1)->count();
    $totalBeneficiaries = \App\Models\Beneficiarie::count();
    $totalAssociations = \App\Models\Association::count();
    $totalProducts = \App\Models\Product::count();
    
    $partnersByClub = \App\Models\Partner::selectRaw('associations.name as club, COUNT(partners.id) as total')
        ->join('associations', 'partners.association_id', '=', 'associations.id')
        ->groupBy('associations.name')
        ->orderByDesc('total')
        ->limit(10)
        ->get();
    
    $pecosasByMonth = \App\Models\Pecosa::selectRaw('MONTH(delivery_date) as month, COUNT(*) as total')
        ->whereNotNull('delivery_date')
        ->whereYear('delivery_date', date('Y'))
        ->groupByRaw('MONTH(delivery_date)')
        ->get();
    $pecosaData = array_fill(0, 12, 0);
    foreach ($pecosasByMonth as $item) {
        $pecosaData[$item->month - 1] = $item->total;
    }
    
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
    
    $clubsWithBeneficiarios = \App\Models\Association::selectRaw('associations.name as club, COUNT(beneficiaries.id) as total')
        ->join('partners', 'partners.association_id', '=', 'associations.id')
        ->join('beneficiaries', 'beneficiaries.partner_id', '=', 'partners.id')
        ->groupBy('associations.id', 'associations.name')
        ->orderByDesc('total')
        ->limit(10)
        ->get();
    
    $totalStock = \App\Models\DetailProduct::get()->sum(function($dp) {
        $used = \App\Models\ProductStock::where('detail_product_id', $dp->id)->sum('quantity');
        return $dp->quantity - $used;
    });
    
    $sociosActivos = $activePartners;
    $beneficiariosActivos = $totalBeneficiaries;
    
    $totalPecosasAnio = \App\Models\Pecosa::whereYear('delivery_date', date('Y'))->count();
@endphp

    <div class="relative rounded-3xl overflow-hidden mb-8 shadow-lg">
        <div class="absolute inset-0">
            <img src="{{ asset('img/niños.jpg') }}" alt="Banner" class="w-full h-full object-cover">
        </div>
        <div class="absolute inset-0 bg-gradient-to-r from-blue/60 to-navy/40"></div>
        <div class="relative z-10 flex items-center justify-between p-8 gap-8">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full pulse-dot" style="background:#D6EAFC"></span>
                    <span class="text-white/90 text-xs font-semibold uppercase tracking-widest">Sistema activo</span>
                </div>
                <h1 class="text-white font-extrabold text-3xl leading-tight mb-2">Panel de Control<br><span style="color:#FEF3DC">PROVALE</span></h1>
                <p class="text-white/70 text-sm font-medium max-w-md">Gestiona beneficiarios, club de madres y entregas de manera eficiente.</p>
                <div class="flex gap-3 mt-5">
                    <a href="{{ route('productos-pecosas.pecosas.index') }}" class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white font-semibold rounded-lg text-sm hover:bg-white/30 transition-all border border-white/20">
                        <i class="fas fa-plus mr-1"></i>Nueva Pecosa
                    </a>
                    <a href="{{ route('club-reconocimientos.index')}}" class="px-4 py-2 bg-white text-blue font-semibold rounded-lg text-sm hover:bg-blue-light transition-all shadow-sm">
                        <i class="fas fa-file-alt mr-1"></i>Comites
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="stat-card bg-white rounded-2xl p-5 border border-mist shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue to-sky rounded-t-2xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-light flex items-center justify-center text-blue text-lg">
                    <i class="fas fa-users"></i>
                </div>
                <span class="text-[11px] font-bold text-blue bg-blue-light px-2 py-0.5 rounded-full">+12%</span>
            </div>
            <div class="text-3xl font-bold text-navy leading-none mb-1">{{ $totalPartners }}</div>
            <div class="text-xs font-medium text-slate">Total Socios</div>
        </div>

        <div class="stat-card bg-white rounded-2xl p-5 border border-mist shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-sky to-[#7ec3e8] rounded-t-2xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-sky-light flex items-center justify-center text-sky text-lg">
                    <i class="fas fa-user-check"></i>
                </div>
                <span class="text-[11px] font-bold text-sky bg-sky-light px-2 py-0.5 rounded-full">+8%</span>
            </div>
            <div class="text-3xl font-bold text-navy leading-none mb-1">{{ $totalBeneficiaries }}</div>
            <div class="text-xs font-medium text-slate">Beneficiarios</div>
        </div>

        <div class="stat-card bg-white rounded-2xl p-5 border border-mist shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber to-[#f0c567] rounded-t-2xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-amber-light flex items-center justify-center text-amber text-lg">
                    <i class="fas fa-heart"></i>
                </div>
                <span class="text-[11px] font-bold text-amber bg-amber-light px-2 py-0.5 rounded-full">+5%</span>
            </div>
            <div class="text-3xl font-bold text-navy leading-none mb-1">{{ $totalAssociations }}</div>
            <div class="text-xs font-medium text-slate">Club de Madres</div>
        </div>

        <div class="stat-card bg-white rounded-2xl p-5 border border-mist shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-teal to-[#5ec4b3] rounded-t-2xl"></div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-teal-light flex items-center justify-center text-teal text-lg">
                    <i class="fas fa-box"></i>
                </div>
                <span class="text-[11px] font-bold text-teal bg-teal-light px-2 py-0.5 rounded-full">-3%</span>
            </div>
            <div class="text-3xl font-bold text-navy leading-none mb-1">{{ $totalStock }}</div>
            <div class="text-xs font-medium text-slate">Stock Total</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 border border-mist shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-navy font-bold text-base">PECOSAs por Mes</h3>
                    <p class="text-slate text-sm">Salidas {{ date('Y') }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-bold bg-blue-light text-blue rounded-lg">{{ $totalPecosasAnio }} total</span>
            </div>
            <div class="chart-wrap h-48">
                <canvas id="pecosasChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-mist shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-navy font-bold text-base">Productos Distribuidos</h3>
                    <p class="text-slate text-sm">Leche y Hojuelas - {{ date('Y') }}</p>
                </div>
                <div class="flex gap-2">
                    <span class="px-2 py-1 text-xs font-semibold bg-blue-light text-blue rounded">Leche</span>
                    <span class="px-2 py-1 text-xs font-semibold bg-amber-light text-amber rounded">Hojuelas</span>
                </div>
            </div>
            <div class="chart-wrap h-48">
                <canvas id="productosChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-5 border border-mist shadow-sm">
            <h3 class="text-navy font-bold text-base mb-1">Socios vs Beneficiarios</h3>
            <p class="text-slate text-sm mb-3">Comparativa total</p>
            <div class="chart-wrap h-36">
                <canvas id="sociosBenefChart"></canvas>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <div class="text-center p-2 bg-blue-light rounded-lg">
                    <div class="text-xl font-bold text-blue">{{ $sociosActivos }}</div>
                    <div class="text-[10px] font-medium text-slate uppercase">Socios</div>
                </div>
                <div class="text-center p-2 bg-sky-light rounded-lg">
                    <div class="text-xl font-bold text-sky">{{ $beneficiariosActivos }}</div>
                    <div class="text-[10px] font-medium text-slate uppercase">Beneficiarios</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-mist shadow-sm">
            <h3 class="text-navy font-bold text-base mb-1">Top Comités</h3>
            <p class="text-slate text-sm mb-3">Con más beneficiarios</p>
            <div class="chart-wrap h-44">
                <canvas id="topClubsChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-mist shadow-sm">
            <h3 class="text-navy font-bold text-base mb-4">Reportes Rápidos</h3>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('socios-beneficiarios.beneficiarios.padron') }}" class="quick-btn flex flex-col items-center gap-1 p-3 rounded-xl bg-blue-light hover:bg-blue/10 transition-all group">
                    <div class="w-8 h-8 bg-blue rounded-lg flex items-center justify-center text-white text-sm group-hover:scale-105 transition-all">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <span class="text-[10px] font-semibold text-blue text-center leading-tight">Padrón Beneficiarios</span>
                </a>
                <a href="{{ route('club-reconocimientos.club.padron') }}" class="quick-btn flex flex-col items-center gap-1 p-3 rounded-xl bg-amber-light hover:bg-amber/10 transition-all group">
                    <div class="w-8 h-8 bg-amber rounded-lg flex items-center justify-center text-white text-sm group-hover:scale-105 transition-all">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <span class="text-[10px] font-semibold text-amber text-center leading-tight">Padrón Club</span>
                </a>
                <a href="{{ route('productos-pecosas.pecosas.generar-reporte', 'reparticion') }}" class="quick-btn flex flex-col items-center gap-1 p-3 rounded-xl bg-teal-light hover:bg-teal/10 transition-all group">
                    <div class="w-8 h-8 bg-teal rounded-lg flex items-center justify-center text-white text-sm group-hover:scale-105 transition-all">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <span class="text-[10px] font-semibold text-teal text-center leading-tight">Repartición</span>
                </a>
                <a href="{{ route('productos-pecosas.pecosas.index') }}" class="quick-btn flex flex-col items-center gap-1 p-3 rounded-xl bg-sky-light hover:bg-sky/10 transition-all group">
                    <div class="w-8 h-8 bg-sky rounded-lg flex items-center justify-center text-white text-sm group-hover:scale-105 transition-all">
                        <i class="fas fa-box"></i>
                    </div>
                    <span class="text-[10px] font-semibold text-sky text-center leading-tight">Pecosas</span>
                </a>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartFont = { family: 'Plus Jakarta Sans', size: 11 };
    const gridColor = '#D4E4F7';
    const tickColor = '#5A7FA8';

    new Chart(document.getElementById('pecosasChart'), {
        type: 'bar',
        data: {
            labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
            datasets: [{
                label: 'PECOSAs',
                data: [{{ implode(',', $pecosaData) }}],
                backgroundColor: '#4A90D9',
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: chartFont, color: tickColor } },
                y: { grid: { color: gridColor }, ticks: { font: chartFont, color: tickColor } }
            }
        }
    });

    new Chart(document.getElementById('productosChart'), {
        type: 'line',
        data: {
            labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
            datasets: [
                {
                    label: 'Leche',
                    data: [{{ implode(',', $lecheData) }}],
                    borderColor: '#1E5799',
                    backgroundColor: 'rgba(30,87,153,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#1E5799',
                    pointRadius: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Hojuelas',
                    data: [{{ implode(',', $hojuelasData) }}],
                    borderColor: '#E5930A',
                    backgroundColor: 'rgba(229,147,10,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#E5930A',
                    pointRadius: 3,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'top', labels: { font: chartFont, boxWidth: 12 } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: chartFont, color: tickColor } },
                y: { grid: { color: gridColor }, ticks: { font: chartFont, color: tickColor } }
            }
        }
    });

    new Chart(document.getElementById('sociosBenefChart'), {
        type: 'doughnut',
        data: {
            labels: ['Socios', 'Beneficiarios'],
            datasets: [{
                data: [{{ $sociosActivos }}, {{ $beneficiariosActivos }}],
                backgroundColor: ['#4A90D9', '#0E8A7A'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { display: false } }
        }
    });

    new Chart(document.getElementById('topClubsChart'), {
        type: 'bar',
        data: {
            labels: [@foreach($clubsWithBeneficiarios as $club)'{{ substr($club->club, 0, 12) }}'@if(!$loop->last),@endif @endforeach],
            datasets: [{
                label: 'Beneficiarios',
                data: [@foreach($clubsWithBeneficiarios as $club){{ $club->total }}@if(!$loop->last),@endif @endforeach],
                backgroundColor: '#4A90D9',
                borderRadius: 3,
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
});
</script>
@endsection
