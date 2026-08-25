<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PROVALE - Sistema de Gestión Social')</title>
    <link rel="icon" href="{{ asset('img/logo-provale-sin-fondo.png') }}" type="image/x-icon">
    {{-- Assets locales compilados (sin CDN) --}}
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/400.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/500.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/700.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/800.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #EEF4FC;
            color: #1A2E4A;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #D4E4F7;
        }

        ::-webkit-scrollbar-thumb {
            background: #5A7FA8;
            border-radius: 3px;
        }

        #sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 70px;
            background: linear-gradient(180deg, #1E5799 0%, #0F1E30 100%);
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 50;
            overflow: hidden;
            box-shadow: 4px 0 20px rgba(44, 36, 32, 0.15);
        }

        #sidebar.expanded {
            width: 240px;
        }

        #app-shell {
            display: flex;
            min-height: 100vh;
        }

        #sidebar-spacer {
            width: 70px;
            flex-shrink: 0;
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #sidebar-spacer.expanded {
            width: 240px;
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
            border-bottom: 2px solid #D4E4F7;
            box-shadow: 0 2px 16px rgba(30,87,153,0.08);
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
            background: #4A90D9;
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

        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                z-index: 60;
                width: 0;
            }

            #sidebar.expanded {
                width: 260px;
            }

            #sidebar-spacer {
                display: none !important;
            }

            #top-header {
                padding-left: 56px !important;
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
            background: rgba(74, 144, 217, 0);
            transition: background 0.2s;
        }

        .sub-item:hover::before,
        .sub-item.active::before {
            background: #4A90D9;
        }

    </style>
</head>

<body class="font-jakarta" x-data="appShell()" x-init="init()">

    <x-loading-screen x-bind:class="{ 'active': loading }" />

    <div id="app-shell">
        <aside id="sidebar" :class="{ 'expanded': sidebarExpanded }">
            <div class="flex items-center gap-4 px-5 py-6 border-b border-white/10 min-h-[88px]">
                <div class="w-12 h-12 bg-blue rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg overflow-hidden">
                    <img src="{{ asset('img/muni2.png') }}" alt="PROVALE" class="w-9 h-9 object-contain">
                </div>
                <div class="logo-text">
                    <div class="text-white font-extrabold text-xl tracking-tight">MDE</div>
                    <div class="text-blue-light text-[11px] font-semibold uppercase tracking-widest">Vaso de Leche</div>
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
                        <span class="nav-text text-[14px]">Socios</span>
                    </a>

                    @if(Auth::user()->canAccessModule('productos') || Auth::user()->canAccessModule('pecosas'))
                    <a href="{{ route('productos-pecosas.pecosas.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('productos-pecosas.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="productos-pecosas">
                        <i class="fas fa-box w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Pecosas</span>
                    </a>
                    @endif

                    @if(Auth::user()->canAccessModule('club-madres') || Auth::user()->canAccessModule('reconocimientos'))
                    <a href="{{ route('club-reconocimientos.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('club-reconocimientos.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="comite-resolucion">
                        <i class="fas fa-users w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Comites</span>
                    </a>
                    @endif

                    @if(Auth::user()->canAccessModule('movimientos'))
                    <a href="{{ route('movimientos.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('movimientos.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="movimientos">
                        <i class="fas fa-exchange-alt w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Movimientos</span>
                    </a>
                    @endif

                    @if(Auth::user()->canAccessModule('responsables-raciones'))
                    <a href="{{ route('responsables-raciones.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('responsables-raciones.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="responsables-raciones">
                        <i class="fas fa-sliders w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Responsables y Raciones</span>
                    </a>
                    @endif

                    @if(Auth::user()->canAccessModule('sistema'))
                    <a href="{{ route('sistema.index') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all {{ request()->routeIs('sistema.*') ? 'active bg-white/10 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}" data-section="sistema">
                        <i class="fas fa-gear w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Sistema</span>
                    </a>
                    @endif

                    <a href="{{ url('/dashboard?section=ayuda') }}" class="nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all text-white/70 hover:text-white hover:bg-white/10" data-section="ayuda">
                        <i class="fas fa-circle-question w-5 text-center text-lg flex-shrink-0"></i>
                        <span class="nav-text text-[14px]">Ayuda</span>
                    </a>
                </div>

                <div class="pt-4 border-t border-white/10">
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky to-blue-mid flex items-center justify-center text-white font-bold flex-shrink-0">
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

        <div id="sidebar-spacer" :class="{ 'expanded': sidebarExpanded }"></div>

        <div id="content-wrapper">
            <div id="mobile-overlay" :class="{ 'visible': mobileOpen }" @click="closeMobile()"></div>

            <button id="mobile-toggle" @click="openMobile()" class="fixed top-3 left-3 sm:top-4 sm:left-4 z-50 w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-white border-2 border-mist shadow-md flex items-center justify-center text-slate hover:bg-mist transition-all">
                <i class="fas fa-bars text-sm sm:text-base"></i>
            </button>

            <header id="top-header" class="flex items-center justify-between px-4 sm:px-8 h-14 sm:h-[72px]">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-mid to-navy-dark flex items-center justify-center text-white text-base overflow-hidden flex-shrink-0">
                        <img src="{{ asset('img/logo-provale-sin-fondo.png') }}" alt="PROVALE" class="w-6 h-6 object-contain">
                    </div>
                    <h2 class="text-navy font-bold text-[13px] sm:text-[15px] uppercase tracking-wider truncate">Sistema de Gestión PROVALE</h2>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                    <button class="relative w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-base border-2 border-mist flex items-center justify-center text-slate hover:bg-mist transition-all" @click="openNotifications()">
                        <i class="fas fa-bell text-sm sm:text-base" style="color:#1A2E4A"></i>
                        <span class="notification-badge absolute -top-1 -right-1 min-w-[18px] h-[18px] sm:min-w-[20px] sm:h-5 bg-coral text-white text-[9px] sm:text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white px-1" :style="notifCount > 0 ? 'display:flex' : 'display:none'" x-text="notifCount > 0 ? notifCount : ''">{{ $unreadNotificationsLabel }}</span>
                    </button>

                    <div class="hidden sm:flex items-center gap-3 px-3 py-2 rounded-xl border-2 border-mist bg-base hover:bg-mist transition-all cursor-pointer">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky to-blue-mid flex items-center justify-center text-white font-bold text-base">
                            {{ substr(Auth::user()->username ?? 'A', 0, 1) }}
                        </div>
                        <div>
                            <div class="text-navy font-bold text-sm leading-none mb-1">{{ Auth::user()->username ?? 'Usuario' }}</div>
                            <div class="text-slate text-[11px] font-semibold">{{ Auth::user()->rol->title ?? '' }}</div>
                        </div>
                    </div>

                    <button type="button" onclick="confirmLogout()" class="flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-coral-light text-coral font-bold text-sm border-2 border-transparent hover:bg-coral hover:text-white transition-all">
                            <i class="fas fa-power-off"></i>
                            <span class="hidden md:inline">Salir</span>
                        </button>
                        <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display: none;">
                            @csrf
                        </form>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 bg-gray-100 animate-fade-in">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="modal-notifications" x-show="notifOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-50" style="display: none;" @keydown.escape.window="if(notifOpen) closeNotifications()">
        <div class="relative mx-auto w-full max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4" @click.outside="closeNotifications()">
            <div class="bg-white rounded-2xl shadow-2xl border-2 border-mist overflow-hidden">
                <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-mist">
                    <h3 class="font-extrabold text-navy text-base sm:text-lg flex items-center gap-2">
                        <i class="fas fa-bell text-blue"></i> <span class="hidden sm:inline">Bandeja de </span>Notificaciones
                    </h3>
                    <button @click="closeNotifications()" class="w-8 h-8 rounded-xl bg-base border-2 border-mist flex items-center justify-center text-slate hover:bg-mist transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4 sm:p-6 max-h-96 overflow-y-auto" id="notifications-container">
                    <template x-if="notifLoading">
                        <div class="text-center py-8">
                            <i class="fas fa-spinner fa-spin text-3xl mb-3"></i>
                            <p>Cargando...</p>
                        </div>
                    </template>
                    <template x-if="!notifLoading && notifHtml">
                        <div x-html="notifHtml"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <script>
        function appShell() {
            return {
                sidebarExpanded: false,
                mobileOpen: false,
                loading: false,
                notifOpen: false,
                notifLoading: false,
                notifHtml: '',
                notifLoaded: false,
                notifCount: {{ $unreadNotifications }},
                loadingTimer: null,

                init() {
                    this.isMobile = () => window.innerWidth <= 768;

                    const sidebar = document.getElementById('sidebar');
                    const spacer = document.getElementById('sidebar-spacer');
                    let hoverTimeout;

                    sidebar.addEventListener('mouseenter', () => {
                        if (this.isMobile()) return;
                        clearTimeout(hoverTimeout);
                        this.sidebarExpanded = true;
                    });
                    sidebar.addEventListener('mouseleave', () => {
                        if (this.isMobile()) return;
                        hoverTimeout = setTimeout(() => {
                            this.sidebarExpanded = false;
                        }, 200);
                    });

                    setInterval(() => this.updateNotifBadge(), 30000);

                    window.addEventListener('pageshow', () => this.hideLoading());
                },

                openMobile() {
                    this.sidebarExpanded = true;
                    this.mobileOpen = true;
                },

                closeMobile() {
                    this.sidebarExpanded = false;
                    this.mobileOpen = false;
                },

                openNotifications() {
                    this.notifOpen = true;
                    document.body.style.overflow = 'hidden';
                    if (!this.notifLoaded) {
                        this.loadNotifications();
                    }
                },

                closeNotifications() {
                    this.notifOpen = false;
                    document.body.style.overflow = '';
                },

                loadNotifications() {
                    this.notifLoading = true;
                    fetch('{{ route("sistema.notifications.partial") }}')
                        .then(r => { if (!r.ok) throw new Error(r.status); return r.text(); })
                        .then(html => { this.notifHtml = html; this.notifLoaded = true; this.notifLoading = false; })
                        .catch(() => {
                            this.notifHtml = '<div class="text-center py-8 text-slate"><i class="fas fa-exclamation-circle text-3xl mb-3"></i><p>Error al cargar notificaciones</p></div>';
                            this.notifLoading = false;
                        });
                },

                updateNotifBadge() {
                    fetch('{{ route("sistema.notifications.count") }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.count !== this.notifCount) {
                            this.notifCount = data.count;
                            const badge = document.querySelector('.notification-badge');
                            if (badge) {
                                badge.style.display = data.count > 0 ? 'flex' : 'none';
                                badge.textContent = data.label;
                            }
                        }
                    })
                    .catch(() => {});
                },

                showLoadingDelayed(delay) {
                    clearTimeout(this.loadingTimer);
                    this.loadingTimer = setTimeout(() => { this.loading = true; }, delay);
                },

                hideLoading() {
                    clearTimeout(this.loadingTimer);
                    this.loading = false;
                }
            };
        }

        let lastNotificationCount = {{ $unreadNotifications }};
    </script>

    <script>
        const loginUrl = '{{ route("login") }}';
        let sessionExpiredHandled = false;

        function handleSessionExpired() {
            if (sessionExpiredHandled) return;
            sessionExpiredHandled = true;
            const modal = document.getElementById('session-expired-modal');
            if (modal) { modal.style.display = 'flex'; }
            else { alert('Tu sesion ha expirado. Seras redirigido al login.'); window.location.href = loginUrl; }
        }

        @if(session('session_expired'))
            var sessionExpired = true;
        @else
            var sessionExpired = false;
        @endif

        if (sessionExpired) {
            document.addEventListener('DOMContentLoaded', function() { setTimeout(handleSessionExpired, 100); });
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                window.Toast.fire({ icon: 'success', title: @json(session('success')) });
            @elseif(session('error'))
                window.Toast.fire({ icon: 'error', title: @json(session('error')) });
            @elseif($errors->any())
                window.Toast.fire({ icon: 'warning', title: @json($errors->first()) });
            @endif
        });

        if (typeof window.fetch === 'function') {
            const originalFetch = window.fetch.bind(window);
            window.fetch = async function(...args) {
                const response = await originalFetch(...args);
                if (response.status === 401 || response.status === 419 || (response.redirected && response.url.includes('/login'))) handleSessionExpired();
                return response;
            };
        }

        if (window.axios && window.axios.interceptors) {
            window.axios.interceptors.response.use(
                response => response,
                error => { if (error?.response?.status === 401 || error?.response?.status === 419) handleSessionExpired(); return Promise.reject(error); }
            );
        }

        function openModal(id) {
            document.querySelectorAll('.fixed.z-40, .fixed.z-50, .fixed.z-60, .fixed.z-\\[70\\]').forEach(el => {
                el.classList.add('hidden');
                el.style.display = 'none';
            });
            var el = document.getElementById(id);
            if (el) {
                el.classList.remove('hidden');
                el.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            if (id === 'modal-notifications') {
                const shell = document.querySelector('[x-data]').__x;
                if (shell) { shell.$data.notifOpen = true; shell.$data.openNotifications(); }
            }
        }

        function closeModal(id) {
            var el = document.getElementById(id);
            if (el) {
                el.classList.add('hidden');
                el.style.display = 'none';
                document.body.style.overflow = '';
            }
            if (id === 'modal-notifications') {
                try {
                    const shell = document.querySelector('[x-data]').__x;
                    if (shell) shell.$data.notifOpen = false;
                } catch(e) {}
            }
        }

        function confirmDelete(formId, message) {
            message = message || '¿Estás seguro de que deseas eliminar este registro?';
            window.ConfirmDialog.fire({
                title: '<span class="font-extrabold text-navy text-xl">¿Estás seguro?</span>',
                text: message, icon: 'warning',
                confirmButtonText: '<i class="fas fa-trash mr-1"></i> Sí, eliminar',
            }).then((result) => { if (result.isConfirmed) document.getElementById(formId).submit(); });
        }

        function confirmLogout() {
            window.ConfirmDialog.fire({
                title: '<span class="font-extrabold text-navy text-xl">¿Cerrar sesión?</span>',
                text: '¿Estás seguro de que deseas cerrar sesión?', icon: 'question',
                confirmButtonText: '<i class="fas fa-power-off mr-1"></i> Sí, cerrar sesión',
                customClass: { confirmButton: 'btn-primary', cancelButton: 'btn-secondary', actions: 'gap-3' },
            }).then((result) => { if (result.isConfirmed) document.getElementById('logout-form').submit(); });
        }

        function confirmResetPassword(userId, userName, dni) {
            window.ConfirmDialog.fire({
                title: 'Restablecer Contraseña',
                html: '¿Estás seguro de que deseas restablecer la contraseña del usuario <strong>' + userName + '</strong>?<br><br><span class="text-red-500 font-bold">La contraseña será su DNI: ' + dni + '</span>',
                icon: 'warning',
                confirmButtonText: '<i class="fas fa-key mr-1"></i> Sí, restablecer',
                customClass: { confirmButton: 'btn-primary', cancelButton: 'btn-secondary', actions: 'gap-3' },
            }).then((result) => { if (result.isConfirmed) document.getElementById('form-reset-password-' + userId).submit(); });
        }

        function toggleGroup(btn) {
            const shell = document.querySelector('[x-data]').__x;
            if (shell && !shell.$data.sidebarExpanded) shell.$data.sidebarExpanded = true;
            const groupItem = btn.closest('.group-item');
            const submenu = groupItem.querySelector('.submenu');
            const isOpen = groupItem.classList.contains('open');
            document.querySelectorAll('.group-item').forEach(g => { g.classList.remove('open'); g.querySelector('.submenu').classList.remove('open'); });
            if (!isOpen) { groupItem.classList.add('open'); submenu.classList.add('open'); }
        }

        const loadingScreen = document.getElementById('loading-screen');
        let loadingTimer = null;
        function showLoadingDelayed(delay) {
            clearTimeout(loadingTimer);
            loadingTimer = setTimeout(() => loadingScreen.classList.add('active'), delay);
        }
        function hideLoading() {
            clearTimeout(loadingTimer);
            loadingScreen.classList.remove('active');
        }

        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (!link) return;
            const href = link.getAttribute('href');
            if (!href || href === '#' || href.startsWith('javascript') || href.startsWith('mailto')) return;
            if (link.target === '_blank' || link.hasAttribute('data-no-loading')) return;
            showLoadingDelayed(300);
        });

        document.addEventListener('submit', function(e) {
            const form = e.target;
            const action = form.getAttribute('action') || '';
            if (action.includes('logout')) { loadingScreen.classList.add('active'); return; }
            if (form.hasAttribute('data-no-loading') || form.closest('[id$="-modal"]') || form.closest('.modal')) return;
        });

        window.addEventListener('pageshow', function() { hideLoading(); });
    </script>

    <script src="{{ asset('js/select2.min.js') }}"></script>

    @stack('scripts')

</body>

</html>
