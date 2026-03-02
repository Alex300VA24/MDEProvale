<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PROVALE - Sistema de Gestión Social')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'jakarta': ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        cream: '#FDF8F3',
                        wheat: '#F5E6D3',
                        earth: '#8B7355',
                        charcoal: '#2C2420',
                        leaf: { DEFAULT: '#4A7C59', light: '#E8F5E9', dark: '#3d6647' },
                        sun: { DEFAULT: '#F4A261', light: '#FEF3E2' },
                        clay: { DEFAULT: '#E76F51', light: '#FCE8E4' },
                        sky: { DEFAULT: '#87CEEB', light: '#E0F2FE' },
                    }
                }
            }
        }
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #FDF8F3; color: #2C2420; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #F5E6D3; }
        ::-webkit-scrollbar-thumb { background: #8B7355; border-radius: 3px; }

        #sidebar {
            position: fixed; left: 0; top: 0; height: 100vh;
            width: 80px; background: linear-gradient(180deg, #8B7355 0%, #2C2420 100%);
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 50; overflow: hidden;
            box-shadow: 4px 0 20px rgba(44, 36, 32, 0.15);
        }
        #sidebar.expanded { width: 280px; }

        #app-shell {
            display: flex; min-height: 100vh;
        }
        #sidebar-spacer {
            width: 80px; flex-shrink: 0;
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #sidebar-spacer.expanded { width: 280px; }
        #content-wrapper {
            flex: 1; min-width: 0; display: flex; flex-direction: column;
        }

        #top-header {
            position: sticky; top: 0; z-index: 40;
            background: #fff; border-bottom: 2px solid #F5E6D3;
            box-shadow: 0 2px 12px rgba(139,115,85,0.08);
        }

        .logo-text, .nav-text, .section-title { opacity: 0; visibility: hidden; transition: all 0.25s ease; white-space: nowrap; }
        #sidebar.expanded .logo-text, #sidebar.expanded .nav-text, #sidebar.expanded .section-title { opacity: 1; visibility: visible; }
        .nav-text { transform: translateX(-8px); }
        #sidebar.expanded .nav-text { transform: translateX(0); }

        .nav-item { position: relative; }
        .nav-item::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 0; background: #F4A261; border-radius: 0 3px 3px 0;
            transition: height 0.3s ease;
        }
        .nav-item:hover::before { height: 60%; }
        .nav-item.active::before { height: 100%; }

        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-card .accent-bar { transform: scaleX(0); transform-origin: left; transition: transform 0.5s ease; }
        .stat-card:hover .accent-bar { transform: scaleX(1); }

        .chart-wrap { position: relative; }

        .quick-btn { transition: all 0.25s ease; }
        .quick-btn:hover { transform: translateY(-3px); }

        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .float-anim { animation: float 5s ease-in-out infinite; }

        @keyframes pulse-dot { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.3); } }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

        @keyframes fade-in { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fade-in 0.3s ease-out; }

        .data-table th { background: #FDF8F3; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #8B7355; }
        .data-table tr { border-bottom: 1px solid #F5E6D3; transition: background 0.2s; }
        .data-table tr:hover { background: #FDF8F3; }
        .data-table td { font-size: 14px; color: #2C2420; padding: 12px 16px; }

        .badge-active { background: #E8F5E9; color: #4A7C59; }
        .badge-pending { background: #FEF3E2; color: #D97706; }
        .badge-inactive { background: #FCE8E4; color: #E76F51; }

        @media (max-width: 768px) {
            #sidebar { position: fixed; z-index: 60; }
            #sidebar-spacer { display: none !important; }
            #mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(44,36,32,0.4); z-index: 55; backdrop-filter: blur(2px); }
            #mobile-overlay.visible { display: block; }
        }
        @media (min-width: 769px) {
            #mobile-toggle { display: none !important; }
        }

        .submenu { overflow: hidden; max-height: 0; transition: max-height 0.35s cubic-bezier(0.4,0,0.2,1); }
        .submenu.open { max-height: 300px; }
        .submenu-arrow { transition: transform 0.3s ease; }
        .group-item.open .submenu-arrow { transform: rotate(180deg); }
        .sub-item { position: relative; }
        .sub-item::before {
            content: ''; position: absolute; left: 22px; top: 50%; transform: translateY(-50%);
            width: 6px; height: 6px; border-radius: 50%; background: rgba(244,162,97,0); 
            transition: background 0.2s;
        }
        .sub-item:hover::before, .sub-item.active::before { background: #F4A261; }

        .btn-primary {
            background: linear-gradient(to right, #4A7C59, #3d6647);
            color: white;
            font-weight: 700;
            padding: 0.625rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px -1px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            background: #F5E6D3;
            color: #8B7355;
            font-weight: 700;
            padding: 0.625rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: #8B7355;
            color: white;
        }

        .btn-danger {
            background: #FCE8E4;
            color: #E76F51;
            font-weight: 700;
            padding: 0.625rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .btn-danger:hover {
            background: #E76F51;
            color: white;
        }

        .btn-action {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
    </style>
</head>
<body class="font-jakarta">

<div id="app-shell">
    <aside id="sidebar">
        <div class="flex items-center gap-4 px-5 py-6 border-b border-white/10 min-h-[88px]">
            <div class="w-12 h-12 bg-sun rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg overflow-hidden">
                <img src="{{ asset('img/logo.png') }}" alt="PROVALE" class="w-9 h-9 object-contain">
            </div>
            <div class="logo-text">
                <div class="text-white font-extrabold text-xl tracking-tight">MDE</div>
                <div class="text-sun text-[11px] font-semibold uppercase tracking-widest">Vaso de Leche</div>
            </div>
        </div>

        <nav class="px-3 py-4 overflow-y-auto flex flex-col" style="height: calc(100% - 88px);">
            <div class="flex-1">
                <a href="{{ route('dashboard') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl text-white/70 hover:text-white hover:bg-white/10 font-semibold transition-all" data-section="dashboard">
                    <i class="fas fa-home w-5 text-center text-lg flex-shrink-0"></i>
                    <span class="nav-text text-[14px]">Inicio</span>
                </a>

                <div class="group-item mb-1">
                    <button onclick="toggleGroup(this)" class="nav-item w-full flex items-center gap-4 px-4 py-3 rounded-xl text-white/70 hover:text-white hover:bg-white/10 font-semibold transition-all">
                        <i class="fas fa-people-group w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px] flex-1 text-left">Gestión Social</span>
                        <i class="fas fa-chevron-down submenu-arrow nav-text text-[10px] text-white/40"></i>
                    </button>
                    <div class="submenu pl-4">
                        <a href="{{ route('socios.index') }}" class="sub-item flex items-center gap-3 px-4 py-2.5 mb-0.5 rounded-xl text-white/60 hover:text-white hover:bg-white/10 font-medium text-[13px] transition-all" data-section="socios">
                            <span class="w-2 h-2 rounded-full bg-white/20 flex-shrink-0 ml-2"></span>
                            <span class="nav-text">Socios</span>
                        </a>
                        <a href="{{ route('beneficiarios.index') }}" class="sub-item flex items-center gap-3 px-4 py-2.5 mb-0.5 rounded-xl text-white/60 hover:text-white hover:bg-white/10 font-medium text-[13px] transition-all" data-section="beneficiarios">
                            <span class="w-2 h-2 rounded-full bg-white/20 flex-shrink-0 ml-2"></span>
                            <span class="nav-text">Beneficiarios</span>
                        </a>
                        <a href="{{ route('club-de-madres.index') }}" class="sub-item flex items-center gap-3 px-4 py-2.5 mb-0.5 rounded-xl text-white/60 hover:text-white hover:bg-white/10 font-medium text-[13px] transition-all" data-section="clubmadres">
                            <span class="w-2 h-2 rounded-full bg-white/20 flex-shrink-0 ml-2"></span>
                            <span class="nav-text">Club de Madres</span>
                        </a>
                        <a href="{{ route('premios.index') }}" class="sub-item flex items-center gap-3 px-4 py-2.5 mb-0.5 rounded-xl text-white/60 hover:text-white hover:bg-white/10 font-medium text-[13px] transition-all" data-section="reconocimientos">
                            <span class="w-2 h-2 rounded-full bg-white/20 flex-shrink-0 ml-2"></span>
                            <span class="nav-text">Reconocimientos</span>
                        </a>
                    </div>
                </div>

                <div class="group-item mb-1">
                    <button onclick="toggleGroup(this)" class="nav-item w-full flex items-center gap-4 px-4 py-3 rounded-xl text-white/70 hover:text-white hover:bg-white/10 font-semibold transition-all">
                        <i class="fas fa-truck w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px] flex-1 text-left">Logística</span>
                        <i class="fas fa-chevron-down submenu-arrow nav-text text-[10px] text-white/40"></i>
                    </button>
                    <div class="submenu pl-4">
                        <a href="{{ route('productos.index') }}" class="sub-item flex items-center gap-3 px-4 py-2.5 mb-0.5 rounded-xl text-white/60 hover:text-white hover:bg-white/10 font-medium text-[13px] transition-all" data-section="productos">
                            <span class="w-2 h-2 rounded-full bg-white/20 flex-shrink-0 ml-2"></span>
                            <span class="nav-text">Productos</span>
                        </a>
                        <a href="{{ route('movimientos.index') }}" class="sub-item flex items-center gap-3 px-4 py-2.5 mb-0.5 rounded-xl text-white/60 hover:text-white hover:bg-white/10 font-medium text-[13px] transition-all" data-section="movimientos">
                            <span class="w-2 h-2 rounded-full bg-white/20 flex-shrink-0 ml-2"></span>
                            <span class="nav-text">Movimientos</span>
                        </a>
                        <a href="{{ route('pecosas.index') }}" class="sub-item flex items-center gap-3 px-4 py-2.5 mb-0.5 rounded-xl text-white/60 hover:text-white hover:bg-white/10 font-medium text-[13px] transition-all" data-section="pecosas">
                            <span class="w-2 h-2 rounded-full bg-white/20 flex-shrink-0 ml-2"></span>
                            <span class="nav-text">Pecosas</span>
                        </a>
                    </div>
                </div>

                <a href="{{ route('mantenimiento.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl text-white/70 hover:text-white hover:bg-white/10 font-semibold transition-all" data-section="mantenimiento">
                    <i class="fas fa-screwdriver-wrench w-5 text-center text-lg flex-shrink-0"></i>
                    <span class="nav-text text-[14px]">Mantenimiento</span>
                </a>

                <a href="{{ route('sistema.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl text-white/70 hover:text-white hover:bg-white/10 font-semibold transition-all" data-section="sistema">
                    <i class="fas fa-gear w-5 text-center text-lg flex-shrink-0"></i>
                    <span class="nav-text text-[14px]">Sistema</span>
                </a>
            </div>

            <div class="pt-4 border-t border-white/10">
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sun to-clay flex items-center justify-center text-white font-bold flex-shrink-0">
                        {{ substr(Auth::user()->username ?? 'A', 0, 1) }}
                    </div>
                    <div class="logo-text">
                        <div class="text-white font-bold text-sm leading-none mb-1">{{ Auth::user()->username ?? 'Usuario' }}</div>
                        <div class="text-white/50 text-[11px]">{{ Auth::user()->rol->title }}</div>
                    </div>
                </div>
            </div>
        </nav>
    </aside>

    <div id="sidebar-spacer"></div>

    <div id="content-wrapper">
        <div id="mobile-overlay" onclick="closeMobile()"></div>

        <button id="mobile-toggle" onclick="openMobile()" class="fixed top-4 left-4 z-50 w-11 h-11 rounded-xl bg-white border-2 border-wheat shadow-md flex items-center justify-center text-earth hover:bg-wheat transition-all">
            <i class="fas fa-bars text-base"></i>
        </button>

        <header id="top-header" class="flex items-center justify-between px-8 h-[72px]">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-leaf to-leaf-dark flex items-center justify-center text-white text-base">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2 class="text-charcoal font-bold text-[15px] uppercase tracking-wider">Sistema de Gestión PROVALE</h2>
            </div>

            <div class="flex items-center gap-3">
                <button class="relative w-10 h-10 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-bell text-base"></i>
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-clay text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white">3</span>
                </button>

                <div class="flex items-center gap-3 px-3 py-2 rounded-xl border-2 border-wheat bg-cream hover:bg-wheat transition-all cursor-pointer">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sun to-clay flex items-center justify-center text-white font-bold text-base">
                        {{ substr(Auth::user()->username ?? 'A', 0, 1) }}
                    </div>
                    <div class="hidden sm:block">
                        <div class="text-charcoal font-bold text-sm leading-none mb-1">{{ Auth::user()->username ?? 'Usuario' }}</div>
                        <div class="text-earth text-[11px] font-semibold">{{ Auth::user()->rol->title }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-clay-light text-clay font-bold text-sm border-2 border-transparent hover:bg-clay hover:text-white transition-all">
                        <i class="fas fa-power-off"></i>
                        <span class="hidden sm:inline">Salir</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-8">
            @if(session('success'))
                <div class="mb-6 px-6 py-4 bg-leaf-light border-2 border-leaf rounded-2xl flex items-center gap-3 animate-fade-in">
                    <div class="w-10 h-10 bg-leaf rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-leaf text-sm">¡Éxito!</div>
                        <div class="text-leaf-dark text-sm">{{ session('success') }}</div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-leaf hover:text-leaf-dark transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 px-6 py-4 bg-clay-light border-2 border-clay rounded-2xl flex items-center gap-3 animate-fade-in">
                    <div class="w-10 h-10 bg-clay rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-clay text-sm">¡Error!</div>
                        <div class="text-clay text-sm">{{ session('error') }}</div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-clay hover:text-clay-dark transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 px-6 py-4 bg-clay-light border-2 border-clay rounded-2xl animate-fade-in">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-clay rounded-xl flex items-center justify-center text-white">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="font-bold text-clay text-sm">Por favor corrige los siguientes errores:</div>
                    </div>
                    <ul class="list-disc list-inside text-clay text-sm space-y-1 ml-13">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const spacer = document.getElementById('sidebar-spacer');
    const overlay = document.getElementById('mobile-overlay');
    let hoverTimeout;
    const isMobile = () => window.innerWidth <= 768;

    sidebar.addEventListener('mouseenter', () => {
        if (isMobile()) return;
        clearTimeout(hoverTimeout);
        sidebar.classList.add('expanded');
        spacer.classList.add('expanded');
    });
    sidebar.addEventListener('mouseleave', () => {
        if (isMobile()) return;
        hoverTimeout = setTimeout(() => {
            sidebar.classList.remove('expanded');
            spacer.classList.remove('expanded');
        }, 200);
    });

    function openMobile() {
        sidebar.classList.add('expanded');
        overlay.classList.add('visible');
    }
    function closeMobile() {
        sidebar.classList.remove('expanded');
        overlay.classList.remove('visible');
    }

    function toggleGroup(btn) {
        if (!sidebar.classList.contains('expanded')) {
            sidebar.classList.add('expanded');
            spacer.classList.add('expanded');
        }
        const groupItem = btn.closest('.group-item');
        const submenu = groupItem.querySelector('.submenu');
        const isOpen = groupItem.classList.contains('open');
        document.querySelectorAll('.group-item').forEach(g => {
            g.classList.remove('open');
            g.querySelector('.submenu').classList.remove('open');
        });
        if (!isOpen) {
            groupItem.classList.add('open');
            submenu.classList.add('open');
        }
    }
</script>

</body>
</html>
