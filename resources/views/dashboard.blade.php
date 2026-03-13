@extends('layouts.main')

@section('title', 'Dashboard - PROVALE')

@section('content')
@php
    $totalPartners = \App\Models\Partner::count();
    $activePartners = \App\Models\Partner::where('state_id', 1)->count();
    $totalBeneficiaries = \App\Models\Beneficiarie::count();
    $totalAssociations = \App\Models\Association::count();
    $totalProducts = \App\Models\Product::count();
    
    // Socios por club
    $partnersByClub = \App\Models\Partner::selectRaw('associations.name as club, COUNT(partners.id) as total')
        ->join('associations', 'partners.association_id', '=', 'associations.id')
        ->groupBy('associations.name')
        ->get();
    
    $monthlyData = array_fill(0, 12, 0);
    if (\Illuminate\Support\Facades\Schema::hasColumn('beneficiaries', 'created_at')) {
        $beneficiariesByMonth = \App\Models\Beneficiarie::selectRaw('DATEPART(MONTH, created_at) as month, COUNT(*) as total')
            ->whereNotNull('created_at')
            ->whereYear('created_at', date('Y'))
        ->groupByRaw('DATEPART(MONTH, created_at)')
            ->get();
        foreach ($beneficiariesByMonth as $item) {
            $monthlyData[$item->month - 1] = $item->total;
        }
    }
    
    // Transacciones por tipo
    $transactionsIn = \App\Models\Transaction::where('type_transaction_id', 1)->count();
    $transactionsOut = \App\Models\Transaction::where('type_transaction_id', 2)->count();
    
    // Stock total
    $totalStock = \App\Models\Product::sum('stock');
    
    // PECOSAS por mes - usando DATEPART para SQL Server
    $pecosasByMonth = \App\Models\Pecosa::selectRaw('DATEPART(MONTH, delivery_date) as month, COUNT(*) as total')
        ->whereNotNull('delivery_date')
        ->whereYear('delivery_date', date('Y'))
        ->groupByRaw('DATEPART(MONTH, delivery_date)')
        ->get();
    
    $pecosaData = array_fill(0, 12, 0);
    foreach ($pecosasByMonth as $item) {
        $pecosaData[$item->month - 1] = $item->total;
    }
@endphp

    <div class="relative rounded-3xl overflow-hidden mb-8 shadow-xl">
    <div class="absolute inset-0">
        <img src="{{ asset('img/banner2.png') }}" alt="Banner" class="w-full h-full object-cover opacity-80">
    </div>
    <div class="absolute inset-0 bg-gradient-to-r from-primary/80 to-primary-dark/60"></div>
    <div class="absolute inset-0 opacity-20" style="background: radial-gradient(circle at 80% 50%, #F4A261 0%, transparent 60%);"></div>
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
                <a href="{{ route('socios-beneficiarios.index') }}" class="px-5 py-2.5 bg-white text-primary font-bold rounded-xl text-sm hover:bg-teal-50 transition-all shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Ir a Módulos
                </a>
                <a href="{{ route('socios-beneficiarios.index') }}" class="px-5 py-2.5 bg-white/15 text-white font-bold rounded-xl text-sm hover:bg-white/25 transition-all backdrop-blur-sm border border-white/20">
                    <i class="fas fa-file-alt mr-2"></i>Ver Reportes
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-charcoal font-bold text-lg">Socios por Mes</h3>
                <p class="text-earth text-sm font-medium">Evolución {{ date('Y') }}</p>
            </div>
            <div class="flex gap-2">
                <button class="px-3 py-1.5 text-xs font-bold bg-leaf text-white rounded-lg">{{ date('Y') }}</button>
            </div>
        </div>
        <div class="chart-wrap h-52">
            <canvas id="lineChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <h3 class="text-charcoal font-bold text-lg mb-1">Por Club</h3>
        <p class="text-earth text-sm font-medium mb-4">Distribución de socios</p>
        <div class="chart-wrap h-44">
            <canvas id="doughnutChart"></canvas>
        </div>
        <div class="mt-4 space-y-2 max-h-32 overflow-y-auto">
            @foreach($partnersByClub as $club)
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#4A7C59]"></span><span class="font-medium text-earth truncate">{{ $club->club }}</span></span>
                <span class="font-bold text-charcoal">{{ $club->total }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <h3 class="text-charcoal font-bold text-lg mb-1">Movimientos</h3>
        <p class="text-earth text-sm font-medium mb-4">Ingresos vs Salidas</p>
        <div class="chart-wrap h-44">
            <canvas id="barChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <h3 class="text-charcoal font-bold text-lg mb-5">Acciones Rápidas</h3>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('socios-beneficiarios.index') }}" class="quick-btn flex flex-col items-center gap-2 p-4 rounded-xl bg-leaf-light hover:bg-leaf/20 transition-all group">
                <div class="w-10 h-10 bg-leaf rounded-xl flex items-center justify-center text-white text-base group-hover:scale-110 transition-all">
                    <i class="fas fa-users"></i>
                </div>
                <span class="text-xs font-bold text-leaf text-center leading-tight">Socios y Beneficiarios</span>
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

    <div class="bg-white rounded-2xl p-6 border-2 border-wheat shadow-sm">
        <h3 class="text-charcoal font-bold text-lg mb-5">Metas del Mes</h3>
        <div class="space-y-5">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-earth">Cobertura Beneficiarios</span>
                    <span class="text-sm font-bold text-charcoal">{{ $totalPartners > 0 ? round(($totalBeneficiaries / $totalPartners) * 100) : 0 }}%</span>
                </div>
                <div class="h-2.5 bg-wheat rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-leaf to-leaf-dark rounded-full" style="width:{{ $totalPartners > 0 ? min(($totalBeneficiaries / $totalPartners) * 100, 100) : 0 }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-earth">Clubes Activos</span>
                    <span class="text-sm font-bold text-charcoal">{{ $totalAssociations }}</span>
                </div>
                <div class="h-2.5 bg-wheat rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-sky to-[#5ba3c5] rounded-full" style="width:{{ min($totalAssociations * 10, 100) }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-earth">Socias Activas</span>
                    <span class="text-sm font-bold text-charcoal">{{ $totalPartners > 0 ? round(($activePartners / $totalPartners) * 100) : 0 }}%</span>
                </div>
                <div class="h-2.5 bg-wheat rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-sun to-[#e69553] rounded-full" style="width:{{ $totalPartners > 0 ? ($activePartners / $totalPartners) * 100 : 0 }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-earth">Stock Disponible</span>
                    <span class="text-sm font-bold text-charcoal">{{ $totalStock > 0 ? 'OK' : 'Bajo' }}</span>
                </div>
                <div class="h-2.5 bg-wheat rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-clay to-[#d55d3f] rounded-full" style="width:{{ min(($totalStock / 500) * 100, 100) }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-bold text-charcoal text-lg flex items-center gap-2">
            <i class="fas fa-clock-rotate-left text-leaf"></i> Resumen por Módulo
        </h3>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6">
        <div class="text-center p-4 rounded-xl bg-leaf-light">
            <div class="text-3xl font-extrabold text-leaf">{{ $totalPartners }}</div>
            <div class="text-sm font-bold text-leaf-dark">Socios</div>
        </div>
        <div class="text-center p-4 rounded-xl bg-sky-light">
            <div class="text-3xl font-extrabold text-[#0284C7]">{{ $totalBeneficiaries }}</div>
            <div class="text-sm font-bold text-[#0369a1]">Beneficiarios</div>
        </div>
        <div class="text-center p-4 rounded-xl bg-sun-light">
            <div class="text-3xl font-extrabold text-[#D97706]">{{ $totalAssociations }}</div>
            <div class="text-sm font-bold text-[#b45309]">Clubes</div>
        </div>
        <div class="text-center p-4 rounded-xl bg-clay-light">
            <div class="text-3xl font-extrabold text-clay">{{ $totalProducts }}</div>
            <div class="text-sm font-bold text-[#b45309]">Productos</div>
        </div>
    </div>
</div>

<script>
    const chartFont = { family: 'Plus Jakarta Sans', size: 11 };
    const gridColor = '#F5E6D3';
    const tickColor = '#8B7355';

    // Line Chart - Socios por mes
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
            datasets: [{
                data: [{{ implode(',', $monthlyData) }}],
                borderColor: '#4A7C59', backgroundColor: 'rgba(74,124,89,0.08)',
                borderWidth: 2.5, pointBackgroundColor: '#4A7C59', pointRadius: 4,
                pointHoverRadius: 6, fill: true, tension: 0.4
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

    // Doughnut Chart - Socios por club
    new Chart(document.getElementById('doughnutChart'), {
        type: 'doughnut',
        data: {
            labels: [@foreach($partnersByClub as $club)'{{ $club->club }}'@if(!$loop->last),@endif @endforeach],
            datasets: [{ data: [@foreach($partnersByClub as $club){{ $club->total }}@if(!$loop->last),@endif @endforeach], backgroundColor: ['#4A7C59','#87CEEB','#F4A261','#E76F51','#9B59B6','#3498DB','#1ABC9C','#F39C12','#E74C3C','#2C3E50'], borderWidth: 0, hoverOffset: 8 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } }
    });

    // Bar Chart - Movimientos
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: ['Ingresos','Salidas'],
            datasets: [{
                data: [{{ $transactionsIn }}, {{ $transactionsOut }}],
                backgroundColor: ['#4A7C59','#E76F51'],
                borderRadius: 8, borderSkipped: false
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
</script>
@endsection
