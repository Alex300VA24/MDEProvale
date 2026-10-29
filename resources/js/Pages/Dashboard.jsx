import { Suspense, lazy, useCallback, useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import LoadingScreen from '../Components/LoadingScreen';
import http from '../http';
import AsistentePROVALE from '../Components/AsistentePROVALE';
import { useRuntimeState } from '../RuntimeState';

// Secciones cargadas bajo demanda (React.lazy => chunk separado por sección).
const Inicio = lazy(() => import('../Sections/Inicio'));
const SociosBeneficiarios = lazy(() => import('../Sections/SociosBeneficiarios'));
const ProductosProgramacion = lazy(() => import('../Sections/ProductosProgramacion'));
const PecosasReparticion = lazy(() => import('../Sections/PecosasReparticion'));
const ClubReconocimientos = lazy(() => import('../Sections/ClubReconocimientos'));
const SupervisionComites = lazy(() => import('../Sections/SupervisionComites'));
const EvaluacionNutricional = lazy(() => import('../Sections/EvaluacionNutricional'));
const ResponsablesRaciones = lazy(() => import('../Sections/ResponsablesRaciones'));
const ReportesPvl = lazy(() => import('../Sections/ReportesPvl'));
const BaseConocimiento = lazy(() => import('../Sections/BaseConocimiento'));
const Sistema = lazy(() => import('../Sections/Sistema'));
const Ayuda = lazy(() => import('../Sections/Ayuda'));

const SECTION_COMPONENTS = {
    inicio: Inicio,
    socios: SociosBeneficiarios,
    productos: ProductosProgramacion,
    pecosas: PecosasReparticion,
    comites: ClubReconocimientos,
    'supervision-comites': SupervisionComites,
    'evaluacion-nutricional': EvaluacionNutricional,
    'responsables-raciones': ResponsablesRaciones,
    reportes: ReportesPvl,
    'base-conocimiento': BaseConocimiento,
    sistema: Sistema,
    ayuda: Ayuda,
};

// Ítems del menú. `modules` = slugs permitidos que habilitan el ítem (vacío = siempre visible).
const NAV_ITEMS = [
    { key: 'inicio', label: 'Inicio', icon: 'fa-home', modules: [] },
    { key: 'comites', label: 'Comités y Resoluciones', icon: 'fa-users', modules: ['club-madres', 'reconocimientos'] },
    { key: 'socios', label: 'Socios y Beneficiarios', icon: 'fa-user-friends', modules: ['socios-beneficiarios'] },
    { key: 'productos', label: 'Productos y Programación de Entrega', icon: 'fa-boxes-stacked', modules: ['productos', 'movimientos'] },
    { key: 'pecosas', label: 'Pecosas y Repartición', icon: 'fa-file-signature', modules: ['pecosas', 'movimientos'] },
    { key: 'supervision-comites', label: 'Supervisión de Comités', icon: 'fa-clipboard-check', modules: ['club-madres'] },
    { key: 'evaluacion-nutricional', label: 'Evaluación Nutricional', icon: 'fa-heart-pulse', modules: ['evaluacion-nutricional'] },
    { key: 'responsables-raciones', label: 'Responsables y Raciones', icon: 'fa-sliders', modules: ['responsables-raciones'] },
    { key: 'reportes', label: 'Reportes PVL', icon: 'fa-file-shield', modules: ['reportes'] },
    { key: 'base-conocimiento', label: 'Base de Conocimiento IA', icon: 'fa-database', modules: ['base-conocimiento'] },
    { key: 'sistema', label: 'Sistema', icon: 'fa-gear', modules: ['sistema'] },
    { key: 'ayuda', label: 'Ayuda', icon: 'fa-circle-question', modules: [] },
];

const BUILT_IN_MODULES = new Set([
    ...NAV_ITEMS.flatMap((item) => item.modules),
]);

// Etiquetas legibles para el tipo de notificación (fallback: mostrar el valor crudo).
const NOTIF_TYPE_LABELS = {
    password_reset: 'Recuperación de contraseña',
    normativa_pvl: 'Normativa PVL',
    alerta_beneficiarios: 'Límite de edad',
    alerta_resolucion: 'Resolución por vencer',
};

// Etiquetas de urgencia (calculadas por el sistema) y prioridad (sugerida por IA, opcional).
const NOTIF_URGENCY = {
    urgente: { label: 'Urgente', icon: 'fa-triangle-exclamation', className: 'bg-red-600 text-white border-red-700' },
    proximo: { label: 'Próximo', icon: 'fa-clock', className: 'bg-amber-400 text-amber-950 border-amber-500' },
    aviso: { label: 'Aviso', icon: 'fa-circle-info', className: 'bg-sky-600 text-white border-sky-700' },
};
const NOTIF_TYPE_STYLES = {
    password_reset: 'bg-slate-700 text-white border-slate-800',
    normativa_pvl: 'bg-emerald-600 text-white border-emerald-700',
    alerta_beneficiarios: 'bg-violet-600 text-white border-violet-700',
    alerta_resolucion: 'bg-orange-600 text-white border-orange-700',
};
const NOTIF_PRIORITY_LABELS = { alta: 'Alta', media: 'Media', baja: 'Baja' };
const NOTIF_PRIORITY_STYLES = {
    alta: 'bg-fuchsia-100 text-fuchsia-800 border-fuchsia-400',
    media: 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-300',
    baja: 'bg-fuchsia-50 text-fuchsia-600 border-fuchsia-200',
};

// Avisos creados antes de guardar `urgencia`: se deduce de los días que traen en metadata.
function notifUrgencyKey(n) {
    const meta = n.metadata || {};
    if (meta.urgencia) return meta.urgencia;
    let days = null;
    if (typeof meta.dias_restantes === 'number') days = meta.dias_restantes;
    else if (Array.isArray(meta.beneficiarios) && meta.beneficiarios.length) {
        days = Math.min(...meta.beneficiarios.map((b) => Number(b.dias)).filter((d) => !Number.isNaN(d)));
    }
    if (days === null || !Number.isFinite(days)) return null;
    return days <= 7 ? 'urgente' : days <= 30 ? 'proximo' : 'aviso';
}

function NotifBadges({ n }) {
    const urgency = NOTIF_URGENCY[notifUrgencyKey(n)];
    const priority = n.metadata?.ia?.prioridad;
    const badge = 'px-2.5 py-1 text-xs font-bold rounded-full border inline-flex items-center gap-1.5';

    return (
        <div className="flex flex-wrap items-center gap-2 mb-2">
            {urgency && (
                <span className={`${badge} ${urgency.className}`}>
                    <i className={`fas ${urgency.icon}`} aria-hidden="true" /> {urgency.label}
                </span>
            )}
            <span className={`${badge} ${NOTIF_TYPE_STYLES[n.type] || 'bg-blue-light text-blue border-blue'}`}>{NOTIF_TYPE_LABELS[n.type] || n.type}</span>
            {priority && (
                <span className={`${badge} ${NOTIF_PRIORITY_STYLES[priority] || NOTIF_PRIORITY_STYLES.media}`}>
                    <i className="fas fa-wand-magic-sparkles" aria-hidden="true" /> Prioridad IA: {NOTIF_PRIORITY_LABELS[priority] || priority}
                </span>
            )}
            {n.status && n.status !== 'pending' && n.status !== 'informativo' && (
                <span className={`px-2 py-1 text-xs font-bold rounded ${
                    n.status === 'approved' ? 'bg-teal-light text-teal' : 'bg-coral-light text-coral'
                }`}>
                    {n.status === 'approved' ? 'Aprobado' : 'Rechazado'}
                </span>
            )}
        </div>
    );
}

function formatNotifDate(value) {
    if (!value) return '';
    const [y, m, d] = String(value).slice(0, 10).split('-');
    return y && m && d ? `${d}/${m}/${y}` : value;
}

// Fecha y hora local hasta segundos: dd/mm/aaaa HH:mm:ss.
function formatNotifDateTime(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'medium', hour12: false });
}

function NotifDetailBody({ n }) {
    const meta = n.metadata || {};
    const structured = (n.type === 'alerta_resolucion' && meta.documento) || (n.type === 'alerta_beneficiarios' && Array.isArray(meta.beneficiarios));
    const Section =({ title, children }) => (
        <div>
            <h4 className="text-xs font-bold uppercase tracking-wide text-slate mb-2">{title}</h4>
            {children}
        </div>
    );

    return (
        <div className="space-y-5">
            <div>
                <NotifBadges n={n} />
                <p className="font-extrabold text-navy text-lg">{n.title}</p>
                {n.requested_at && (
                    <p className="text-xs text-slate mt-1">
                        <i className="far fa-clock mr-1" aria-hidden="true" />Generado el {formatNotifDateTime(n.requested_at)}
                    </p>
                )}
            </div>

            {/* En avisos con datos estructurados el detalle ya está abajo; el texto largo sería repetir. */}
            {n.description && !structured && <p className="text-sm text-navy whitespace-pre-line">{n.description}</p>}

            {meta.ia && (
                <Section title="Análisis de IA (complementario)">
                    <div className="rounded-xl border-2 border-mist bg-base p-3 text-sm space-y-2">
                        <p className="text-navy">{meta.ia.resumen}</p>
                        <p className="text-navy"><span className="font-bold">Acción sugerida:</span> {meta.ia.accion_sugerida}</p>
                    </div>
                </Section>
            )}

            {n.type === 'alerta_resolucion' && (
                <Section title="Resolución">
                    <dl className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm mb-4">
                        {[
                            ['Documento', meta.documento],
                            ['Fin de vigencia', formatNotifDate(meta.fecha_fin)],
                            ['Días restantes', meta.dias_restantes < 0 ? `Venció hace ${Math.abs(meta.dias_restantes)}` : meta.dias_restantes],
                            ['Comités alcanzados', meta.total_comites],
                        ].map(([label, value]) => (
                            <div key={label} className="rounded-xl border-2 border-mist bg-base px-3 py-2">
                                <dt className="text-slate text-xs">{label}</dt>
                                <dd className="font-bold text-navy mt-0.5">{value}</dd>
                            </div>
                        ))}
                    </dl>
                    <p className="text-xs font-bold text-slate mb-2">Comités</p>
                    <ul className="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                        {(meta.comites || []).map((c) => (
                            <li key={c.id} className="px-3 py-1.5 rounded-lg bg-sky-light text-navy text-sm font-medium">{c.name}</li>
                        ))}
                    </ul>
                    {meta.total_comites > (meta.comites || []).length && (
                        <p className="text-xs text-slate mt-1">… y {meta.total_comites - meta.comites.length} más.</p>
                    )}
                </Section>
            )}

            {n.type === 'alerta_beneficiarios' && (
                <Section title={`Beneficiarios (${meta.total ?? (meta.beneficiarios || []).length})`}>
                    <p className="text-sm text-navy mb-3">
                        Comité <span className="font-semibold">{meta.comite}</span>
                        {meta.ya_superaron > 0 && <> · {meta.ya_superaron} ya superaron el límite</>}
                    </p>
                    <div className="max-h-64 overflow-y-auto rounded-xl border-2 border-mist">
                        <table className="w-full text-sm">
                            <thead className="bg-base text-slate text-xs text-left">
                                <tr>
                                    <th className="px-3 py-2">Beneficiario</th>
                                    <th className="px-3 py-2">Tipo</th>
                                    <th className="px-3 py-2">Límite</th>
                                    <th className="px-3 py-2">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(meta.beneficiarios || []).map((b) => (
                                    <tr key={b.beneficiary_id} className="border-t border-mist">
                                        <td className="px-3 py-2 text-navy font-medium">{b.nombre}</td>
                                        <td className="px-3 py-2 text-slate">{b.tipo_beneficio}</td>
                                        <td className="px-3 py-2 text-slate whitespace-nowrap">{formatNotifDate(b.fecha_limite)}</td>
                                        <td className="px-3 py-2 whitespace-nowrap">
                                            {b.dias < 0
                                                ? <span className="text-coral font-bold">Superó hace {Math.abs(b.dias)} {Math.abs(b.dias) === 1 ? 'día' : 'días'}</span>
                                                : <span className="text-navy">En {b.dias} {b.dias === 1 ? 'día' : 'días'}</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {meta.total > (meta.beneficiarios || []).length && (
                        <p className="text-xs text-slate mt-1">… y {meta.total - meta.beneficiarios.length} más.</p>
                    )}
                </Section>
            )}

            {meta.pdf_url && (
                <a
                    href={meta.pdf_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-blue hover:underline"
                >
                    <i className="fas fa-file-pdf" aria-hidden="true" /> Ver documento
                </a>
            )}
        </div>
    );
}

function getSectionFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const requested = params.get('section');
    const section = requested === 'movimientos' ? 'productos' : requested;
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
    const { modules, currentUser: user } = useRuntimeState();
    const isAdmin = Boolean(user?.is_admin);

    const allowedSlugs = new Set(
        (modules ?? []).filter((m) => m.can_view).map((m) => m.slug)
    );
    const adminOnlySections = new Set(['reportes', 'base-conocimiento']);
    const builtInItems = NAV_ITEMS.filter(
        (item) => (!adminOnlySections.has(item.key) || isAdmin)
            && (item.modules.length === 0 || item.modules.some((slug) => allowedSlugs.has(slug)))
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
    const allowedSectionKeys = new Set(navItems.map((item) => item.key));

    const [activeSection, setActiveSection] = useState(() => {
        const requestedSection = getSectionFromUrl();
        return allowedSectionKeys.has(requestedSection) ? requestedSection : 'inicio';
    });
    const [assistantOpen, setAssistantOpen] = useState(false);
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
    const [detailNotif, setDetailNotif] = useState(null);
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
        if (!allowedSectionKeys.has(key)) return;
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

    const selectNavigationItem = (item) => navigate(item.key);

    const hidePanelLoading = useCallback(() => setPanelLoading(false), []);

    // Botón "atrás" del navegador también cambia de sección.
    useEffect(() => {
        const onPop = () => {
            setNavigationIntent(null);
            const requestedSection = getSectionFromUrl();
            setActiveSection(allowedSectionKeys.has(requestedSection) ? requestedSection : 'inicio');
            setPanelLoading(true);
        };
        window.addEventListener('popstate', onPop);
        return () => window.removeEventListener('popstate', onPop);
    }, []);

    useEffect(() => {
        if (!allowedSectionKeys.has(activeSection)) {
            navigate('inicio');
        }
    }, [activeSection, modules]);

    const loadNotificationCount = useCallback(async () => {
        if (document.hidden) return;
        const res = await http.get('/api/dashboard/sistema/notifications/unread-count', { silentSession: true });
        setNotifCount(res.data?.count ?? 0);
        setNotifLabel(res.data?.label ?? '');
    }, []);

    // Poll de respaldo y actualización inmediata cuando otra vista procesa avisos.
    useEffect(() => {
        const loadCount = () => loadNotificationCount().catch(() => {});
        loadCount();
        const id = setInterval(loadCount, 30000);
        const onVisible = () => { if (!document.hidden) loadCount(); };
        const onNotificationsChanged = () => loadCount();
        document.addEventListener('visibilitychange', onVisible);
        window.addEventListener('notifications:changed', onNotificationsChanged);
        return () => {
            clearInterval(id);
            document.removeEventListener('visibilitychange', onVisible);
            window.removeEventListener('notifications:changed', onNotificationsChanged);
        };
    }, [loadNotificationCount]);

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
        http.post('/api/dashboard/sistema/notifications/mark-seen')
            .then(() => window.dispatchEvent(new CustomEvent('notifications:changed')))
            .catch(() => {});
    };

    const closeNotifications = () => {
        setNotifOpen(false);
        document.body.style.overflow = '';
    };

    const handleLogout = (e) => {
        e.preventDefault();
        router.post((window.APP_URL || '') + '/logout');
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
                            <img src={`${window.APP_URL || ''}/img/muni2.png`} alt="PROVALE" className="w-9 h-9 object-contain" />
                        </div>
                        <div className="logo-text">
                            <div className="text-white font-extrabold text-xl tracking-tight">MDE</div>
                            <div className="text-blue-light text-[11px] font-semibold uppercase tracking-widest">SGEPVL</div>
                        </div>
                    </div>

                    <nav id="sidebar-navigation" className="px-3 py-4 overflow-y-auto overflow-x-hidden flex flex-col scrollbar-thin" style={{ height: 'calc(100% - 88px)' }}>
                        <div className="flex-1">
                            {navItems.map((item) => {
                                const selected = activeSection === item.key;

                                return (
                                <button
                                    key={item.key}
                                    type="button"
                                    onClick={() => selectNavigationItem(item)}
                                    title={!sidebarExpanded ? item.label : undefined}
                                    aria-label={item.label}
                                    aria-current={selected ? 'page' : undefined}
                                    className={`nav-item flex items-center gap-4 px-4 py-3 mb-1 rounded-xl font-semibold transition-all w-full text-left ${
                                        selected
                                            ? 'active bg-white/10 text-white'
                                            : 'text-white/70 hover:text-white hover:bg-white/10'
                                    }`}
                                >
                                    <i className={`fas ${item.icon} w-5 text-center text-lg flex-shrink-0`} />
                                    <span className="nav-text text-[14px]">{item.label}</span>
                                </button>
                                );
                            })}
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
                            src={`${window.APP_URL || ''}/img/logo-provale-sin-fondo.png`}
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

                            <div className="hidden sm:flex items-center gap-3 pl-1.5 pr-4 py-1.5 rounded-md border border-mist bg-white shadow-sm hover:shadow-md hover:border-sky/50 transition-all">
                                <div className="w-9 h-9 rounded-md bg-gradient-to-br from-sky to-blue-mid flex items-center justify-center text-white font-bold text-sm ring-2 ring-white shadow-sm flex-shrink-0">
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
                                title="Cerrar sesión"
                                className="group flex items-center gap-2 px-3 sm:px-4 py-2.5 rounded-md bg-coral-light text-coral font-bold text-xs sm:text-sm border border-coral/30 hover:bg-coral/15 hover:border-coral/60 transition-colors"
                            >
                                <i className="fas fa-power-off text-coral/80 group-hover:text-coral transition-colors" />
                                <span className="hidden md:inline">Cerrar sesión</span>
                            </button>
                        </div>
                    </header>

                    <main className="flex-1 p-4 sm:p-6 lg:p-8 bg-gray-100">
                        <Suspense fallback={<LoadingScreen subtitle="Cargando panel..." />}>
                            <PanelContainer key={activeSection} onReady={hidePanelLoading}>
                                <div key={activeSection} className="section-enter">
                                    <ActiveComponent
                                        module={activeDynamicModule}
                                        onNavigate={navigate}
                                        allowedSections={allowedSectionKeys}
                                        initialAction={navigationIntent?.section === activeSection ? navigationIntent.action : null}
                                    />
                                </div>
                            </PanelContainer>
                        </Suspense>
                    </main>
                </div>
            </div>

            {panelLoading && <LoadingScreen subtitle="Cargando panel..." />}

            <AsistentePROVALE open={assistantOpen} onOpenChange={setAssistantOpen} />

            {notifOpen && (
                <div
                    className="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-50 animate-fade-in"
                    onClick={closeNotifications}
                >
                    <div
                        className="relative mx-auto w-full max-w-5xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="modal-enter bg-white rounded-2xl shadow-2xl border-2 border-mist overflow-hidden">
                            <div className="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-mist">
                                <h3 className="font-extrabold text-navy text-base sm:text-lg flex items-center gap-2">
                                    <i className="fas fa-bell text-blue" /> <span className="hidden sm:inline">Bandeja de </span>Notificaciones
                                    {notifs.length > 0 && (
                                        <span className="ml-1 px-2.5 py-0.5 rounded-full bg-blue text-white text-xs font-bold">{notifs.length}</span>
                                    )}
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
                                                        <NotifBadges n={n} />
                                                        <p className="font-bold text-navy">{n.title}</p>
                                                        <p className="text-sm text-slate mt-1 whitespace-pre-line line-clamp-2">{n.description}</p>
                                                        <div className="flex items-center justify-between gap-3 mt-3">
                                                            <span className="text-xs text-slate"><i className="far fa-clock mr-1" aria-hidden="true" />{formatNotifDateTime(n.requested_at)}</span>
                                                            <button
                                                                type="button"
                                                                onClick={() => setDetailNotif(n)}
                                                                className="inline-flex items-center gap-1.5 text-xs font-bold text-blue hover:underline"
                                                            >
                                                                Ver detalle <i className="fas fa-arrow-right" aria-hidden="true" />
                                                            </button>
                                                        </div>
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

            {detailNotif && (
                <div
                    className="fixed inset-0 bg-black/50 backdrop-blur-sm overflow-y-auto h-full w-full z-[60] animate-fade-in"
                    onClick={() => setDetailNotif(null)}
                >
                    <div
                        className="relative mx-auto w-full max-w-4xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="modal-enter bg-white rounded-2xl shadow-2xl border-2 border-mist overflow-hidden">
                            <div className="flex items-center justify-between px-4 sm:px-6 py-4 border-b-2 border-mist">
                                <h3 className="font-extrabold text-navy text-base sm:text-lg flex items-center gap-2">
                                    <i className="fas fa-file-lines text-blue" /> Detalle del aviso
                                </h3>
                                <button type="button" onClick={() => setDetailNotif(null)} className="modal-close-btn" aria-label="Cerrar detalle">
                                    <i className="fas fa-times" />
                                </button>
                            </div>
                            <div className="p-4 sm:p-6 max-h-[70vh] overflow-y-auto">
                                <NotifDetailBody n={detailNotif} />
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
