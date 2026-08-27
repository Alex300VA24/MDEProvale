import { Suspense, lazy, useCallback, useEffect, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import LoadingScreen from '../Components/LoadingScreen';
import http from '../http';

// Secciones cargadas bajo demanda (React.lazy => chunk separado por sección).
const Inicio = lazy(() => import('../Sections/Inicio'));
const SociosBeneficiarios = lazy(() => import('../Sections/SociosBeneficiarios'));
const ProductosPecosas = lazy(() => import('../Sections/ProductosPecosas'));
const ClubReconocimientos = lazy(() => import('../Sections/ClubReconocimientos'));
const Movimientos = lazy(() => import('../Sections/Movimientos'));
const ResponsablesRaciones = lazy(() => import('../Sections/ResponsablesRaciones'));
const Reportes = lazy(() => import('../Sections/Reportes'));
const Sistema = lazy(() => import('../Sections/Sistema'));
const Ayuda = lazy(() => import('../Sections/Ayuda'));

const SECTION_COMPONENTS = {
    inicio: Inicio,
    socios: SociosBeneficiarios,
    productos: ProductosPecosas,
    comites: ClubReconocimientos,
    movimientos: Movimientos,
    'responsables-raciones': ResponsablesRaciones,
    reportes: Reportes,
    sistema: Sistema,
    ayuda: Ayuda,
};

// Ítems del menú. `modules` = slugs permitidos que habilitan el ítem (vacío = siempre visible).
const NAV_ITEMS = [
    { key: 'inicio', label: 'Inicio', icon: 'fa-home', modules: [] },
    { key: 'socios', label: 'Socios y Beneficiarios', icon: 'fa-user-friends', modules: ['socios-beneficiarios'] },
    { key: 'productos', label: 'Productos y Pecosas', icon: 'fa-box', modules: ['productos', 'pecosas'] },
    { key: 'comites', label: 'Comités y Reconocimientos', icon: 'fa-users', modules: ['club-madres', 'reconocimientos'] },
    { key: 'movimientos', label: 'Movimientos y Repartición', icon: 'fa-exchange-alt', modules: ['movimientos'] },
    { key: 'responsables-raciones', label: 'Responsables y Raciones', icon: 'fa-sliders', modules: ['responsables-raciones'] },
    { key: 'reportes', label: 'Reportes', icon: 'fa-chart-bar', modules: ['reportes'] },
    { key: 'sistema', label: 'Sistema', icon: 'fa-gear', modules: ['sistema'] },
    { key: 'ayuda', label: 'Ayuda', icon: 'fa-circle-question', modules: [] },
];

const BUILT_IN_MODULES = new Set(NAV_ITEMS.flatMap((item) => item.modules));

function getSectionFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const section = params.get('section');
    return SECTION_COMPONENTS[section] || section?.startsWith('module:') ? section : 'inicio';
}

function DynamicModule({ module }) {
    return (
        <section className="bg-white rounded-2xl border-2 border-mist p-6 sm:p-8 shadow-sm">
            <div className="flex items-start gap-4">
                <div className="w-12 h-12 rounded-xl bg-sky-light text-blue flex items-center justify-center flex-shrink-0">
                    <i className={`fas ${module?.icon || 'fa-puzzle-piece'} text-xl`} />
                </div>
                <div>
                    <h1 className="text-xl font-extrabold text-navy">{module?.name}</h1>
                    <p className="text-earth mt-1">{module?.description || 'Módulo disponible en navegación.'}</p>
                    {module?.route?.startsWith('/') && (
                        <a href={module.route} className="btn-primary inline-flex mt-5">Abrir módulo</a>
                    )}
                </div>
            </div>
        </section>
    );
}

// Envuelve la sección activa para ocultar la pantalla de carga una vez montada.
// Muestra la misma pantalla de carga del login, con una duración mínima para
// que el cambio de panel no parpadee.
function PanelContainer({ onReady, children }) {
    const started = useRef(Date.now());
    useEffect(() => {
        const delay = Math.max(0, 450 - (Date.now() - started.current));
        const t = setTimeout(onReady, delay);
        return () => clearTimeout(t);
    }, [onReady]);
    return children;
}

export default function Dashboard() {
    const { auth, modules } = usePage().props;
    const user = auth?.user ?? null;

    const allowedSlugs = new Set(
        (modules ?? []).filter((m) => m.can_view).map((m) => m.slug)
    );
    const builtInItems = NAV_ITEMS.filter(
        (item) => item.modules.length === 0 || item.modules.some((slug) => allowedSlugs.has(slug))
    );
    const dynamicItems = (modules ?? [])
        .filter((module) => module.can_view && !BUILT_IN_MODULES.has(module.slug))
        .map((module) => ({
            key: `module:${module.slug}`,
            label: module.name,
            icon: module.icon || 'fa-puzzle-piece',
            modules: [module.slug],
            module,
        }));
    const helpItem = builtInItems.find((item) => item.key === 'ayuda');
    const navItems = [
        ...builtInItems.filter((item) => item.key !== 'ayuda'),
        ...dynamicItems,
        ...(helpItem ? [helpItem] : []),
    ];

    const [activeSection, setActiveSection] = useState(getSectionFromUrl);
    const [panelLoading, setPanelLoading] = useState(true);
    const [navigationIntent, setNavigationIntent] = useState(null);
    const [sidebarExpanded, setSidebarExpanded] = useState(
        () => typeof window !== 'undefined' && localStorage.getItem('provale-sidebar-expanded') === 'true'
    );
    const [mobileOpen, setMobileOpen] = useState(false);
    const [notifOpen, setNotifOpen] = useState(false);
    const [notifCount, setNotifCount] = useState(0);
    const [notifLabel, setNotifLabel] = useState('');
    const [notifs, setNotifs] = useState([]);
    const [notifLoading, setNotifLoading] = useState(false);
    const notifLoaded = useRef(false);

    const toggleSidebar = () => {
        setSidebarExpanded((expanded) => {
            const next = !expanded;
            localStorage.setItem('provale-sidebar-expanded', String(next));
            return next;
        });
    };

    const navigate = (key, action = null) => {
        if (window.innerWidth <= 768) setMobileOpen(false);
        if (key === activeSection) return;
        setNavigationIntent(action ? { section: key, action } : null);
        setActiveSection(key);
        setPanelLoading(true);
        const url = new URL(window.location.href);
        if (key === 'inicio') {
            url.searchParams.delete('section');
        } else {
            url.searchParams.set('section', key);
        }
        // Solo query param + history.pushState; sin recargar props del servidor.
        window.history.pushState({ section: key }, '', url.pathname + url.search);
    };

    const hidePanelLoading = useCallback(() => setPanelLoading(false), []);

    // Botón "atrás" del navegador también cambia de sección.
    useEffect(() => {
        const onPop = () => {
            setNavigationIntent(null);
            setActiveSection(getSectionFromUrl());
            setPanelLoading(true);
        };
        window.addEventListener('popstate', onPop);
        return () => window.removeEventListener('popstate', onPop);
    }, []);

    useEffect(() => {
        if (activeSection.startsWith('module:') && !navItems.some((item) => item.key === activeSection)) {
            navigate('inicio');
        }
    }, [activeSection, modules]);

    // Contador de notificaciones no leídas (poll cada 30s, como el original).
    useEffect(() => {
        const loadCount = async () => {
            try {
                const res = await http.get('/api/dashboard/sistema/notifications/unread-count');
                setNotifCount(res.data?.count ?? 0);
                setNotifLabel(res.data?.label ?? '');
            } catch {
                /* silencioso */
            }
        };
        loadCount();
        const id = setInterval(loadCount, 30000);
        return () => clearInterval(id);
    }, []);

    const loadNotifications = useCallback(async () => {
        setNotifLoading(true);
        try {
            const res = await http.get('/api/dashboard/sistema/notifications');
            setNotifs(res.data?.data ?? []);
            notifLoaded.current = true;
        } catch {
            notifLoaded.current = true;
        } finally {
            setNotifLoading(false);
        }
    }, []);

    const openNotifications = () => {
        setNotifOpen(true);
        document.body.style.overflow = 'hidden';
        if (!notifLoaded.current) loadNotifications();
    };

    const closeNotifications = () => {
        setNotifOpen(false);
        document.body.style.overflow = '';
    };

    const handleLogout = (e) => {
        e.preventDefault();
        router.post('/logout');
    };

    const activeDynamicModule = dynamicItems.find((item) => item.key === activeSection)?.module;
    const ActiveComponent = SECTION_COMPONENTS[activeSection] || DynamicModule;

    return (
        <>
            <Head title="Dashboard" />
            <div id="app-shell">
                <aside
                    id="sidebar"
                    className={`${sidebarExpanded ? 'expanded' : ''} ${mobileOpen ? 'mobile-open' : ''}`}
                >
                    <button
                        type="button"
                        className="sidebar-edge-toggle"
                        onClick={toggleSidebar}
                        aria-label={sidebarExpanded ? 'Contraer menú lateral' : 'Expandir menú lateral'}
                        aria-controls="sidebar-navigation"
                        aria-expanded={sidebarExpanded}
                        title={sidebarExpanded ? 'Contraer menú' : 'Expandir menú'}
                    >
                        <i className={`fas ${sidebarExpanded ? 'fa-chevron-left' : 'fa-chevron-right'}`} aria-hidden="true" />
                    </button>

                    <div className="flex items-center gap-4 px-5 py-6 border-b border-white/10 min-h-[88px]">
                        <div className="w-12 h-12 bg-blue rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg overflow-hidden">
                            <img src="/img/muni2.png" alt="PROVALE" className="w-9 h-9 object-contain" />
                        </div>
                        <div className="logo-text">
                            <div className="text-white font-extrabold text-xl tracking-tight">MDE</div>
                            <div className="text-blue-light text-[11px] font-semibold uppercase tracking-widest">Vaso de Leche</div>
                        </div>
                    </div>

                    <nav id="sidebar-navigation" className="px-3 py-4 overflow-y-auto overflow-x-hidden flex flex-col scrollbar-thin" style={{ height: 'calc(100% - 88px)' }}>
                        <div className="flex-1">
                            {navItems.map((item) => (
                                <button
                                    key={item.key}
                                    type="button"
                                    onClick={() => navigate(item.key)}
                                    title={!sidebarExpanded ? item.label : undefined}
                                    aria-label={item.label}
                                    aria-current={activeSection === item.key ? 'page' : undefined}
                                    className={`nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all w-full text-left ${
                                        activeSection === item.key
                                            ? 'active bg-white/10 text-white'
                                            : 'text-white/70 hover:text-white hover:bg-white/10'
                                    }`}
                                >
                                    <i className={`fas ${item.icon} w-5 text-center text-lg flex-shrink-0`} />
                                    <span className="nav-text text-[14px]">{item.label}</span>
                                </button>
                            ))}
                        </div>

                        <div className="pt-4 border-t border-white/10">
                            <div className="flex items-center gap-3 px-4 py-3">
                                <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-sky to-blue-mid flex items-center justify-center text-white font-bold flex-shrink-0">
                                    {(user?.name || 'A').charAt(0).toUpperCase()}
                                </div>
                                <div className="logo-text">
                                    <div className="text-white font-bold text-sm leading-none mb-1">{user?.name ?? 'Usuario'}</div>
                                    <div className="text-white/50 text-[11px]">{user?.rol ?? ''}</div>
                                </div>
                            </div>
                        </div>
                    </nav>
                </aside>

                <div id="sidebar-spacer" className={sidebarExpanded ? 'expanded' : ''} />

                <div id="content-wrapper">
                    <div
                        id="mobile-overlay"
                        className={mobileOpen ? 'visible' : ''}
                        onClick={() => {
                            setMobileOpen(false);
                        }}
                    />

                    <button
                        id="mobile-toggle"
                        type="button"
                        onClick={() => {
                            setMobileOpen(true);
                        }}
                        aria-label="Abrir menú lateral"
                        aria-controls="sidebar-navigation"
                        aria-expanded={mobileOpen}
                        className="fixed top-3 left-3 sm:top-4 sm:left-4 z-50 w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-white border-2 border-mist shadow-md flex items-center justify-center text-slate hover:bg-mist transition-all"
                    >
                        <i className="fas fa-bars text-sm sm:text-base" />
                    </button>

                    <header id="top-header" className="relative flex items-center justify-between px-4 sm:px-8 h-16 sm:h-20">
                        <div className="flex items-center gap-3 sm:gap-4 min-w-0">
                            <img 
                            src="/img/logo-provale-sin-fondo.png" 
                            alt="PROVALE" 
                            className="w-10 h-10 sm:w-12 sm:h-12 object-contain flex-shrink-0" 
                            />
                            <div className="min-w-0">
                                <div className="hidden sm:flex items-center gap-1.5 mb-0.5">
                                    <span className="w-1.5 h-1.5 rounded-full bg-leaf pulse-dot flex-shrink-0" />
                                    <span className="text-leaf text-[10px] font-bold uppercase tracking-widest">Sistema activo</span>
                                </div>
                                <h2 className="text-navy font-extrabold text-[14px] sm:text-[17px] tracking-tight truncate leading-tight">
                                    Sistema de Gestión Integral <span className="text-blue">PROVALE</span>
                                </h2>
                            </div>
                        </div>

                        <div className="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                            <button
                                type="button"
                                onClick={openNotifications}
                                className="relative w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-sun-light flex items-center justify-center text-sun hover:bg-sun hover:text-white hover:shadow-lg hover:shadow-sun/30 hover:-translate-y-0.5 transition-all"
                                title="Notificaciones"
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor" className="w-5 h-5 sm:w-[22px] sm:h-[22px]" aria-hidden="true">
                                    <path d="M12 2.25c-.966 0-1.75.784-1.75 1.75v.61C7.36 5.24 5.25 7.9 5.25 11v3.19c0 .82-.32 1.6-.9 2.19l-.99 1a1.417 1.417 0 0 0 1 2.42h15.28a1.417 1.417 0 0 0 1-2.42l-.99-1a3.12 3.12 0 0 1-.9-2.19V11c0-3.1-2.11-5.76-5-6.39V4c0-.966-.784-1.75-1.75-1.75Z" />
                                    <path d="M9.5 19.5a2.5 2.5 0 0 0 5 0h-5Z" />
                                </svg>
                                {notifCount > 0 && (
                                    <span className="notification-badge absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] sm:min-w-[20px] sm:h-5 bg-coral text-white text-[9px] sm:text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white px-1 shadow-sm">
                                        {notifLabel}
                                    </span>
                                )}
                            </button>

                            <div className="hidden sm:block w-px h-9 bg-gradient-to-b from-transparent via-mist to-transparent" />

                            <div className="hidden sm:flex items-center gap-3 pl-1.5 pr-4 py-1.5 rounded-full border border-mist bg-white shadow-sm hover:shadow-md hover:border-sky/50 transition-all">
                                <div className="w-9 h-9 rounded-full bg-gradient-to-br from-sky to-blue-mid flex items-center justify-center text-white font-bold text-sm ring-2 ring-white shadow-sm flex-shrink-0">
                                    {(user?.name || 'A').charAt(0).toUpperCase()}
                                </div>
                                <div className="leading-tight">
                                    <div className="text-navy font-bold text-sm">{user?.name ?? 'Usuario'}</div>
                                    <div className="text-slate text-[11px] font-semibold">{user?.rol ?? ''}</div>
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={handleLogout}
                                className="flex items-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl bg-coral-light text-coral font-bold text-xs sm:text-sm border border-transparent hover:bg-coral hover:text-white hover:shadow-lg hover:shadow-coral/30 hover:-translate-y-0.5 transition-all"
                            >
                                <i className="fas fa-power-off" />
                                <span className="hidden md:inline">Salir</span>
                            </button>
                        </div>
                    </header>

                    <main className="flex-1 p-4 sm:p-6 lg:p-8 bg-gray-100">
                        <Suspense fallback={<LoadingScreen subtitle="Cargando panel..." />}>
                            <PanelContainer key={activeSection} onReady={hidePanelLoading}>
                                <div key={activeSection} className="section-enter">
                                    <ActiveComponent
                                        module={activeDynamicModule}
                                        onNavigate={activeSection === 'inicio' ? navigate : undefined}
                                        initialAction={navigationIntent?.section === activeSection ? navigationIntent.action : null}
                                    />
                                </div>
                            </PanelContainer>
                        </Suspense>
                    </main>
                </div>
            </div>

            {panelLoading && <LoadingScreen subtitle="Cargando panel..." />}

            {notifOpen && (
                <div
                    className="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-50 animate-fade-in"
                    onClick={closeNotifications}
                >
                    <div
                        className="relative mx-auto w-full max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="modal-enter bg-white rounded-2xl shadow-2xl border-2 border-mist overflow-hidden">
                            <div className="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-mist">
                                <h3 className="font-extrabold text-navy text-base sm:text-lg flex items-center gap-2">
                                    <i className="fas fa-bell text-blue" /> <span className="hidden sm:inline">Bandeja de </span>Notificaciones
                                </h3>
                                <button
                                    type="button"
                                    onClick={closeNotifications}
                                    className="modal-close-btn"
                                >
                                    <i className="fas fa-times" />
                                </button>
                            </div>
                            <div className="p-4 sm:p-6 max-h-96 overflow-y-auto">
                                {notifLoading ? (
                                    <div className="text-center py-8">
                                        <i className="fas fa-spinner fa-spin text-3xl mb-3" />
                                        <p>Cargando...</p>
                                    </div>
                                ) : notifs.length === 0 ? (
                                    <div className="text-center py-8 text-slate">
                                        <i className="fas fa-inbox text-4xl mb-3" />
                                        <p>No hay notificaciones</p>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {notifs.map((n) => (
                                            <div key={n.id} className="p-4 rounded-xl border-2 border-mist bg-base">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2 mb-1">
                                                            <span className="px-2 py-1 bg-blue-light text-blue text-xs font-bold rounded">{n.type}</span>
                                                            {n.status && n.status !== 'pending' && (
                                                                <span className={`px-2 py-1 text-xs font-bold rounded ${
                                                                    n.status === 'approved'
                                                                        ? 'bg-teal-light text-teal'
                                                                        : 'bg-coral-light text-coral'
                                                                }`}>
                                                                    {n.status === 'approved' ? 'Aprobado' : 'Rechazado'}
                                                                </span>
                                                            )}
                                                        </div>
                                                        <p className="font-bold text-navy">{n.title}</p>
                                                        <p className="text-sm text-slate mt-1">{n.description}</p>
                                                        {n.requested_at && (
                                                            <p className="text-xs text-slate mt-2">{n.requested_at}</p>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
