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
                    fontFamily: {
                        'jakarta': ['"Plus Jakarta Sans"', 'sans-serif']
                    },
                    colors: {
                        cream: '#F8FAFC',
                        wheat: '#E2E8F0',
                        earth: '#64748B',
                        charcoal: '#1E293B',
                        primary: {
                            DEFAULT: '#0F766E',
                            light: '#14B8A6',
                            dark: '#0D5D56'
                        },
                        sun: {
                            DEFAULT: '#F59E0B',
                            light: '#FEF3C7'
                        },
                        clay: {
                            DEFAULT: '#DC2626',
                            light: '#FEE2E2'
                        },
                        sky: {
                            DEFAULT: '#0EA5E9',
                            light: '#E0F2FE'
                        },
                    }
                }
            }
        }
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F1F5F9;
            color: #1E293B;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #E2E8F0;
        }

        ::-webkit-scrollbar-thumb {
            background: #64748B;
            border-radius: 3px;
        }

        #sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 80px;
            background: linear-gradient(180deg, #0F766E 0%, #0D5D56 100%);
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 50;
            overflow: hidden;
            box-shadow: 4px 0 20px rgba(44, 36, 32, 0.15);
        }

        #sidebar.expanded {
            width: 280px;
        }

        #app-shell {
            display: flex;
            min-height: 100vh;
        }

        #sidebar-spacer {
            width: 80px;
            flex-shrink: 0;
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #sidebar-spacer.expanded {
            width: 280px;
        }

        #content-wrapper {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        #top-header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: #fff;
            border-bottom: 2px solid #E2E8F0;
            box-shadow: 0 2px 12px rgba(15, 118, 110, 0.08);
        }

        .logo-text,
        .nav-text,
        .section-title {
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        #sidebar.expanded .logo-text,
        #sidebar.expanded .nav-text,
        #sidebar.expanded .section-title {
            opacity: 1;
            visibility: visible;
        }

        .nav-text {
            transform: translateX(-8px);
        }

        #sidebar.expanded .nav-text {
            transform: translateX(0);
        }

        .nav-item {
            position: relative;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 0;
            background: #F4A261;
            border-radius: 0 3px 3px 0;
            transition: height 0.3s ease;
        }

        .nav-item:hover::before {
            height: 60%;
        }

        .nav-item.active::before {
            height: 100%;
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-card .accent-bar {
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }

        .stat-card:hover .accent-bar {
            transform: scaleX(1);
        }

        .chart-wrap {
            position: relative;
        }

        .quick-btn {
            transition: all 0.25s ease;
        }

        .quick-btn:hover {
            transform: translateY(-3px);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .float-anim {
            animation: float 5s ease-in-out infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.3);
            }
        }

        .pulse-dot {
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }

        .data-table th {
            background: #FDF8F3;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #8B7355;
        }

        .data-table tr {
            border-bottom: 1px solid #F5E6D3;
            transition: background 0.2s;
        }

        .data-table tr:hover {
            background: #FDF8F3;
        }

        .data-table td {
            font-size: 14px;
            color: #2C2420;
            padding: 12px 16px;
        }

        .badge-active {
            background: #E8F5E9;
            color: #4A7C59;
        }

        .badge-pending {
            background: #FEF3E2;
            color: #D97706;
        }

        .badge-inactive {
            background: #FCE8E4;
            color: #E76F51;
        }

        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                z-index: 60;
            }

            #sidebar-spacer {
                display: none !important;
            }

            #mobile-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(44, 36, 32, 0.4);
                z-index: 55;
                backdrop-filter: blur(2px);
            }

            #mobile-overlay.visible {
                display: block;
            }
        }

        @media (min-width: 769px) {
            #mobile-toggle {
                display: none !important;
            }
        }

        .submenu {
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .submenu.open {
            max-height: 300px;
        }

        .submenu-arrow {
            transition: transform 0.3s ease;
        }

        .group-item.open .submenu-arrow {
            transform: rotate(180deg);
        }

        .sub-item {
            position: relative;
        }

        .sub-item::before {
            content: '';
            position: absolute;
            left: 22px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(244, 162, 97, 0);
            transition: background 0.2s;
        }

        .sub-item:hover::before,
        .sub-item.active::before {
            background: #F4A261;
        }

        .btn-primary {
            background: linear-gradient(to right, #0F766E, #0D5D56);
            color: white;
            font-weight: 700;
            padding: 0.625rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            box-shadow: 0 4px 6px -1px rgba(15, 118, 110, 0.3);
            transition: all 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px -1px rgba(15, 118, 110, 0.4);
        }

        .btn-secondary {
            background: #E2E8F0;
            color: #64748B;
            font-weight: 700;
            padding: 0.625rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: #64748B;
            color: white;
        }

        .btn-danger {
            background: #FEE2E2;
            color: #DC2626;
            font-weight: 700;
            padding: 0.625rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-danger:hover {
            background: #DC2626;
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
                <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg overflow-hidden">
                    <img src="{{ asset('img/muni2.png') }}" alt="PROVALE" class="w-9 h-9 object-contain">
                </div>
                <div class="logo-text">
                    <div class="text-white font-extrabold text-xl tracking-tight">MDE</div>
                    <div class="text-primary-light text-[11px] font-semibold uppercase tracking-widest">Vaso de Leche</div>
                </div>
            </div>

            <nav class="px-3 py-4 overflow-y-auto flex flex-col" style="height: calc(100% - 88px);">
                <div class="flex-1">
                    <a href="{{ route('dashboard') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('dashboard') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="dashboard">
                        <i class="fas fa-home w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Inicio</span>
                    </a>

                    <a href="{{ route('socios-beneficiarios.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('socios-beneficiarios.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="socios-beneficiarios">
                        <i class="fas fa-user-friends w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Socios y Beneficiarios</span>
                    </a>

                    <a href="{{ route('productos-pecosas.pecosas.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('productos-pecosas.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="productos-pecosas">
                        <i class="fas fa-box w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Productos y Pecosas</span>
                    </a>

                    <a href="{{ route('club-reconocimientos.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('club-reconocimientos.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="comite-resolucion">
                        <i class="fas fa-users w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Comite y Resolucion</span>
                    </a>

                    <a href="{{ route('movimientos.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('movimientos.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="movimientos">
                        <i class="fas fa-exchange-alt w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Movimientos</span>
                    </a>

                    <a href="{{ route('mantenimiento.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('mantenimiento.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="mantenimiento">
                        <i class="fas fa-screwdriver-wrench w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Mantenimiento</span>
                    </a>

                    <a href="{{ route('sistema.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('sistema.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="sistema">
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
                            <div class="text-white/50 text-[11px]">{{ Auth::user()->rol->title ?? '' }}</div>
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
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white text-base overflow-hidden">
                        <img src="{{ asset('img/vasoLecheSin.png') }}" alt="PROVALE" class="w-6 h-6 object-contain">
                    </div>
                    <h2 class="text-charcoal font-bold text-[15px] uppercase tracking-wider">Sistema de Gestión PROVALE</h2>
                </div>

                <div class="flex items-center gap-3">
                    <button class="relative w-10 h-10 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                        <i class="fas fa-bell text-base"></i>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white">3</span>
                    </button>

                    <div class="flex items-center gap-3 px-3 py-2 rounded-xl border-2 border-wheat bg-cream hover:bg-wheat transition-all cursor-pointer">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sun to-clay flex items-center justify-center text-white font-bold text-base">
                            {{ substr(Auth::user()->username ?? 'A', 0, 1) }}
                        </div>
                        <div class="hidden sm:block">
                            <div class="text-charcoal font-bold text-sm leading-none mb-1">{{ Auth::user()->username ?? 'Usuario' }}</div>
                            <div class="text-earth text-[11px] font-semibold">{{ Auth::user()->rol->title ?? '' }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-red-500-light text-red-500 font-bold text-sm border-2 border-transparent hover:bg-red-500 hover:text-white transition-all">
                            <i class="fas fa-power-off"></i>
                            <span class="hidden sm:inline">Salir</span>
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-8">
                @if(session('success'))
                <div class="mb-6 px-6 py-4 bg-primary-light border-2 border-primary rounded-2xl flex items-center gap-3 animate-fade-in">
                    <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-primary text-sm">¡Éxito!</div>
                        <div class="text-primary-dark text-sm">{{ session('success') }}</div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-primary hover:text-primary-dark transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 px-6 py-4 bg-red-500-light border-2 border-red-500 rounded-2xl flex items-center gap-3 animate-fade-in">
                    <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-red-500 text-sm">¡Error!</div>
                        <div class="text-red-500 text-sm">{{ session('error') }}</div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-500-dark transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 px-6 py-4 bg-red-500-light border-2 border-red-500 rounded-2xl animate-fade-in">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center text-white">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="font-bold text-red-500 text-sm">Por favor corrige los siguientes errores:</div>
                    </div>
                    <ul class="list-disc list-inside text-red-500 text-sm space-y-1 ml-13">
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
        const loginUrl = '{{ route("login") }}';
        let sessionExpiredHandled = false;

        function handleSessionExpired() {
            if (sessionExpiredHandled) return;
            sessionExpiredHandled = true;
            alert('Tu sesión ha expirado. Serás redirigido al login.');
            window.location.href = loginUrl;
        }

        @if(session('session_expired'))
            document.addEventListener('DOMContentLoaded', handleSessionExpired);
        @endif

        if (typeof window.fetch === 'function') {
            const originalFetch = window.fetch.bind(window);
            window.fetch = async function(...args) {
                const response = await originalFetch(...args);
                const isAuthError = response.status === 401 || response.status === 419;
                const redirectedToLogin = response.redirected && response.url.includes('/login');

                if (isAuthError || redirectedToLogin) {
                    handleSessionExpired();
                }

                return response;
            };
        }

        if (window.axios && window.axios.interceptors) {
            window.axios.interceptors.response.use(
                response => response,
                error => {
                    const status = error?.response?.status;
                    if (status === 401 || status === 419) {
                        handleSessionExpired();
                    }

                    return Promise.reject(error);
                }
            );
        }

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
