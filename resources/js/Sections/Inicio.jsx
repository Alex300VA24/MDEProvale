import { useEffect, useRef, useState } from 'react';
import Chart from 'chart.js/auto';
import http from '../http';
import DetailModal, { DetailGroup } from '../Components/DetailModal';

const BASE = '/api/dashboard/inicio';
const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
const MESES_LARGOS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

// Año mínimo seleccionable en las gráficas (coincide con InicioController::MIN_YEAR).
const ANIO_MIN = 2019;
const ANIO_ACTUAL = new Date().getFullYear();
// Años disponibles, del actual hacia atrás hasta ANIO_MIN.
const ANIOS = Array.from({ length: Math.max(1, ANIO_ACTUAL - ANIO_MIN + 1) }, (_, i) => ANIO_ACTUAL - i);

// Combo compacto reutilizado en las cabeceras de las gráficas.
// Se estiliza como control interactivo (borde marcado, fondo blanco, sombra y
// chevron visible) para que se lea a simple vista como un desplegable y no como
// una etiqueta más de la cabecera.
function FiltroSelect({ value, onChange, children, label }) {
    return (
        <div className="relative inline-flex items-center">
            <select
                aria-label={label}
                value={value}
                onChange={(e) => onChange(Number(e.target.value))}
                className="appearance-none text-[10px] sm:text-xs font-bold text-blue bg-white rounded-lg pl-2.5 pr-7 py-1.5 border border-blue/30 shadow-sm cursor-pointer transition-colors hover:border-blue hover:bg-blue-light/50 focus:outline-none focus:ring-2 focus:ring-blue/40 focus:border-blue"
            >
                {children}
            </select>
            <i className="fas fa-chevron-down pointer-events-none absolute right-2.5 text-[8px] sm:text-[9px] text-blue/70" aria-hidden="true" />
        </div>
    );
}
const CHART_FONT = { family: "'Source Sans 3', system-ui, sans-serif", size: 11 };

// Colores institucionales del lienzo claro para las gráficas Chart.js.
function chartInk() {
    return { grid: 'rgba(15, 42, 74, 0.08)', tick: '#5A7FA8', tooltip: 'rgba(11, 58, 102, 0.94)', donutBorder: '#ffffff', centerText: '#0B3A66' };
}
const GRID_COLOR = () => chartInk().grid;
const TICK_COLOR = () => chartInk().tick;

// Paleta categórica institucional: colores planos, saturados y con contraste
// claro entre series. Sin degradados: lavaban el color y dejaban el doughnut
// ilegible.
const C = {
    leche: '#1E5799',
    lecheFill: 'rgba(30, 87, 153, 0.10)',
    hojuelas: '#C77700',
    hojuelasFill: 'rgba(199, 119, 0, 0.10)',
    bar: '#2C6BB3',
    barHover: '#1E5799',
    socios: '#1E5799',
    sociosHover: '#17457A',
    beneficiarios: '#0E8A7A',
    beneficiariosHover: '#0B6E61',
    navy: '#0B3A66',
};

// Aplica los estilos base compartidos por todas las gráficas (tipografía y
// tooltips). Se vuelve a llamar al alternar el tema para refrescar los colores
// dependientes del tema antes de re-montar las gráficas.
function applyChartDefaults() {
    Chart.defaults.font.family = CHART_FONT.family;
    Chart.defaults.color = TICK_COLOR();
    Chart.defaults.plugins.tooltip.backgroundColor = chartInk().tooltip;
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.boxPadding = 6;
    Chart.defaults.plugins.tooltip.titleFont = { family: CHART_FONT.family, size: 12, weight: '700' };
    Chart.defaults.plugins.tooltip.bodyFont = { family: CHART_FONT.family, size: 12 };
}
applyChartDefaults();

// Texto centrado dentro del doughnut (total de la comparativa).
const donutCenterText = {
    id: 'donutCenterText',
    afterDatasetsDraw(chart) {
        const { ctx, chartArea } = chart;
        const meta = chart.getDatasetMeta(0);
        if (!meta || !meta.data || !meta.data.length) return;
        const total = chart.data.datasets[0].data.reduce((a, b) => a + Number(b || 0), 0);
        const cx = (chartArea.left + chartArea.right) / 2;
        const cy = (chartArea.top + chartArea.bottom) / 2;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = chartInk().centerText;
        ctx.font = `700 20px ${CHART_FONT.family}`;
        ctx.fillText(total.toLocaleString('es-PE'), cx, cy - 4);
        ctx.fillStyle = TICK_COLOR();
        ctx.font = `600 10px ${CHART_FONT.family}`;
        ctx.fillText('TOTAL', cx, cy + 14);
        ctx.restore();
    },
};

function StatCard({ icon, iconClass, barClass, badge, badgeClass, value, label, detail, onDetail, className = '' }) {
    const hasObservations = Boolean(detail?.observations?.length);

    return (
        <div className={`stat-card stagger-enter bg-white rounded-xl sm:rounded-2xl p-3 sm:p-5 border border-mist shadow-sm relative overflow-hidden flex flex-col ${className}`}>
            <div className={`absolute top-0 left-0 right-0 h-1 ${barClass}`} />
            <div className="flex items-center justify-between mb-3 sm:mb-4">
                <div className={`w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl flex items-center justify-center text-sm sm:text-lg ${iconClass}`}>
                    <i className={`fas ${icon}`} />
                </div>
                <span className={`max-w-[8rem] truncate text-[11px] sm:text-xs font-bold px-1.5 sm:px-2 py-0.5 rounded-full ${badgeClass}`} title={badge}>{badge}</span>
            </div>
            <div className="text-2xl sm:text-4xl font-bold text-navy leading-none mb-1 tabular-nums">{value}</div>
            <div className="text-xs sm:text-sm font-medium text-slate flex-1">{label}</div>
            {hasObservations && (
                <button
                    type="button"
                    onClick={onDetail}
                    className="mt-3 min-h-11 w-full inline-flex items-center justify-between gap-2 rounded-lg bg-amber-light px-3 py-2 text-xs sm:text-sm font-bold text-sun transition-colors hover:bg-amber/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber focus-visible:ring-offset-2"
                    aria-label={`Ver detalle y observaciones de ${label}`}
                >
                    <span className="inline-flex items-center gap-2">
                        <i className="fas fa-circle-exclamation" aria-hidden="true" />
                        Ver detalle
                    </span>
                    <i className="fas fa-chevron-right text-[10px]" aria-hidden="true" />
                </button>
            )}
        </div>
    );
}

const AUDIT_TITLES = {
    socios: { title: 'Detalle de socias', label: 'Socias', icon: 'fa-users' },
    beneficiarios: { title: 'Detalle de beneficiarios', label: 'Beneficiarios', icon: 'fa-user-check' },
    clubes: { title: 'Detalle de clubes', label: 'Clubes', icon: 'fa-heart' },
};

function AuditValue({ label, value, emphasized = false }) {
    return (
        <div className="min-w-0 py-3">
            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate">{label}</div>
            <div className={`mt-1 text-xl sm:text-2xl font-extrabold tabular-nums ${emphasized ? 'text-blue' : 'text-charcoal'}`}>
                {Number(value || 0).toLocaleString('es-PE')}
            </div>
        </div>
    );
}

function RosterAuditModal({ audit, metricKey, periodLabel, onClose }) {
    const definition = AUDIT_TITLES[metricKey];
    const metric = audit?.metrics?.[metricKey];
    if (!definition || !metric) return null;

    const dualRole = audit.dual_role || {};
    const isPeopleMetric = metricKey === 'socios' || metricKey === 'beneficiarios';

    return (
        <DetailModal
            open
            onClose={onClose}
            title={`${definition.title} · ${periodLabel}`}
            icon={definition.icon}
            maxWidth="sm:max-w-3xl"
        >
            <DetailGroup>
                <div className={`rounded-xl px-4 py-3 border ${metric.verified ? 'bg-leaf-light border-leaf/20 text-leaf' : 'bg-sun-light border-amber/30 text-sun'}`}>
                    <div className="flex items-start gap-3">
                        <i className={`fas ${metric.verified ? 'fa-circle-check' : 'fa-triangle-exclamation'} mt-0.5`} aria-hidden="true" />
                        <div>
                            <p className="font-bold text-sm">{metric.verified ? 'Conteo verificado con la auditoría' : 'El conteo actual requiere revisión'}</p>
                            <p className="mt-0.5 text-xs sm:text-sm leading-relaxed">
                                El card muestra el total corregido. El número histórico no se elimina: permanece separado como evidencia del padrón original.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-x-5 divide-y sm:divide-y-0 sm:divide-x divide-mist">
                    <AuditValue label="Excel original" value={metric.excel} />
                    <div className="sm:pl-5"><AuditValue label="Relaciones en BD" value={metric.database} /></div>
                    <div className="sm:pl-5"><AuditValue label="Total corregido" value={metric.corrected} emphasized /></div>
                </div>

                {metric.database !== metric.migrated && (
                    <p className="text-xs sm:text-sm text-sun">
                        La auditoría esperaba {Number(metric.migrated).toLocaleString('es-PE')} relaciones migradas; actualmente la BD devuelve {Number(metric.database).toLocaleString('es-PE')}.
                    </p>
                )}
            </DetailGroup>

            {isPeopleMetric && (
                <DetailGroup title="Socias que también son beneficiarias" icon="fa-people-arrows-left-right">
                    <p className="text-sm text-charcoal leading-relaxed">
                        Estas personas cuentan una vez en <strong>Socias</strong> y también una vez en <strong>Beneficiarios</strong>, porque cumplen ambos roles. El cruce considera únicamente LAC, GES y DIS vigentes en el período.
                    </p>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        {['LAC', 'GES', 'DIS'].map((type) => (
                            <div key={type} className="rounded-lg bg-blue-light px-3 py-2.5 text-center">
                                <div className="text-lg font-extrabold text-blue tabular-nums">{Number(dualRole[type] || 0).toLocaleString('es-PE')}</div>
                                <div className="text-[10px] font-bold uppercase tracking-wide text-slate">{type}</div>
                            </div>
                        ))}
                        <div className="rounded-lg bg-teal-light px-3 py-2.5 text-center">
                            <div className="text-lg font-extrabold text-teal tabular-nums">{Number(dualRole.total || 0).toLocaleString('es-PE')}</div>
                            <div className="text-[10px] font-bold uppercase tracking-wide text-slate">Total</div>
                        </div>
                    </div>
                </DetailGroup>
            )}

            <DetailGroup title="Observaciones históricas" icon="fa-clipboard-list">
                <ul className="space-y-2.5">
                    {metric.observations.map((observation) => (
                        <li key={observation} className="flex items-start gap-2.5 text-sm leading-relaxed text-charcoal">
                            <i className="fas fa-circle-info mt-1 text-xs text-amber" aria-hidden="true" />
                            <span>{observation}</span>
                        </li>
                    ))}
                </ul>
                <p className="pt-2 text-xs text-slate break-words">
                    Fuente histórica: <span className="font-semibold text-charcoal">{audit.source}</span>
                </p>
            </DetailGroup>
        </DetailModal>
    );
}

function StockMiniStat({ label, value, tone = 'default' }) {
    const tones = {
        default: 'text-navy',
        danger: 'text-coral',
    };
    const bg = tone === 'danger' ? 'bg-coral-light' : 'bg-blue-light';

    return (
        <div className={`rounded-lg px-1.5 py-1.5 text-center ${bg}`}>
            <div className={`text-base sm:text-lg font-extrabold tabular-nums tracking-tight ${tones[tone]}`}>{value}</div>
            <div className="mt-0.5 text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-slate">{label}</div>
        </div>
    );
}

function StockCard({ products = [], closed = false, periodLabel = '' }) {
    const formatStock = (value) => Number(value || 0).toLocaleString('es-PE');

    return (
        <div className="stat-card stagger-enter bg-white rounded-xl sm:rounded-2xl p-3 sm:p-5 border border-mist shadow-sm relative overflow-hidden">
            <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-teal to-[#5ec4b3]" />
            <div className="flex items-center justify-between mb-2 sm:mb-3">
                <div className="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl flex items-center justify-center text-sm sm:text-lg bg-teal-light text-teal">
                    <i className="fas fa-box" aria-hidden="true" />
                </div>
                <span
                    className={`text-[10px] sm:text-xs font-bold px-1.5 sm:px-2 py-0.5 rounded-full whitespace-nowrap ${
                        closed ? 'text-teal bg-teal-light' : 'text-amber bg-amber-light'
                    }`}
                >
                    {closed ? `Cierre: ${periodLabel}` : 'Mes en curso'}
                </span>
            </div>
            <div className="divide-y divide-mist">
                {products.map((product) => {
                    const faltante = product.restante < 0;
                    return (
                        <div key={product.key} className="py-2 sm:py-2.5 first:pt-0 last:pb-0">
                            <div className="flex items-baseline justify-between gap-2 mb-1.5">
                                <span className="text-xs sm:text-sm font-bold text-slate truncate">{product.name}</span>
                                {product.unit && (
                                    <span className="text-[9px] sm:text-[10px] font-semibold text-slate whitespace-nowrap">{product.unit}</span>
                                )}
                            </div>
                            <div className="grid grid-cols-3 gap-1.5 sm:gap-2">
                                <StockMiniStat label="Stock mes" value={formatStock(product.ingresado)} />
                                <StockMiniStat label="Utilizado" value={formatStock(product.utilizado)} />
                                <StockMiniStat
                                    label={faltante ? 'Faltante' : 'Restante'}
                                    value={formatStock(faltante ? Math.abs(product.restante) : product.restante)}
                                    tone={faltante ? 'danger' : 'default'}
                                />
                            </div>
                        </div>
                    );
                })}
            </div>
            <div className="text-[10px] sm:text-xs font-medium text-slate mt-2">
                {closed
                    ? 'Cifras definitivas del mes cerrado'
                    : 'Cifras parciales: el detalle definitivo aparece al cerrar el mes'}
            </div>
        </div>
    );
}

// Acceso rápido con aspecto de botón pulsable: borde, sombra y respuesta al
// hover/pressed (se eleva al pasar el cursor, se hunde al pulsar) para dejar
// claro que es accionable y no un icono decorativo.
function QuickButton({ onClick, icon, label, bgClass, tileClass, textClass }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`quick-btn flex flex-col items-center gap-1 p-2 sm:p-3 rounded-lg sm:rounded-xl border border-mist shadow-sm cursor-pointer transition-all group hover:-translate-y-0.5 hover:shadow-md active:translate-y-0 active:shadow-sm ${bgClass}`}
        >
            <div className={`w-7 h-7 sm:w-8 sm:h-8 rounded-md sm:rounded-lg flex items-center justify-center text-white text-xs sm:text-sm group-hover:scale-105 transition-all ${tileClass}`}>
                <i className={`fas ${icon}`} />
            </div>
            <span className={`text-[10px] sm:text-xs font-semibold text-center leading-tight ${textClass}`}>{label}</span>
        </button>
    );
}

export default function Inicio({ onNavigate, allowedSections = new Set() }) {
    const [panel, setPanel] = useState(null);
    const [error, setError] = useState(false);
    const [auditMetric, setAuditMetric] = useState(null);

// Filtros de las dos gráficas anuales (independientes entre sí). Los
    // widgets de "Resumen del período" (periodoAnio/periodoMes) comparten el
    // período del resumen: tarjetas KPI, stock, "Socios vs Beneficiarios" y
    // "Top Comités" se redibujan cuando cambia.
    const [anioPecosas, setAnioPecosas] = useState(ANIO_ACTUAL);
    const [anioProductos, setAnioProductos] = useState(ANIO_ACTUAL);
    const [periodoAnio, setPeriodoAnio] = useState(null);
    const [periodoMes, setPeriodoMes] = useState(null);

    const pecosasCanvas = useRef(null);
    const productosCanvas = useRef(null);
    const donutCanvas = useRef(null);
    const topComitesCanvas = useRef(null);
    const charts = useRef({});

    useEffect(() => {
        let active = true;
        (async () => {
            try {
                const res = await http.get(`${BASE}/panel`, {
params: {
                        anio_pecosas: anioPecosas,
                        anio_productos: anioProductos,
                        ...(periodoAnio && periodoMes ? { periodo_anio: periodoAnio, periodo_mes: periodoMes } : {}),
                    },
                });
                if (active) {
                    setPanel(res.data);
                    if (!periodoAnio || !periodoMes) {
                        setPeriodoAnio(res.data.stats.period.year);
                        setPeriodoMes(res.data.stats.period.month);
                    }
                    setError(false);
                }
            } catch {
                if (active) setError(true);
            }
        })();
        return () => {
            active = false;
        };
    }, [anioPecosas, anioProductos, periodoAnio, periodoMes]);

    // Cada gráfica se monta en su propio efecto y depende SOLO de su porción
    // de datos (serializada). Así, cambiar el filtro de una no redibuja las
    // demás.
    const nfmt = (v) => Number(v || 0).toLocaleString('es-PE');

    useEffect(() => {
        if (!panel) return undefined;

        charts.current.pecosas?.destroy();
        charts.current.pecosas = null;

        const pecosaData = panel.pecosas_por_mes.data;
        if (pecosasCanvas.current && !pecosaData.every((v) => v === 0)) {
            charts.current.pecosas = new Chart(pecosasCanvas.current, {
                type: 'bar',
                data: {
                    labels: MESES,
                    datasets: [{
                        label: 'PECOSAs',
                        data: pecosaData,
                        backgroundColor: C.bar,
                        hoverBackgroundColor: C.barHover,
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 30,
                        categoryPercentage: 0.68,
                        barPercentage: 0.9,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 500, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            displayColors: false,
                            callbacks: {
                                title: (items) => `${items[0].label}`,
                                label: (item) => `  ${nfmt(item.raw)} PECOSA(s)`,
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { font: CHART_FONT, color: TICK_COLOR() } },
                        y: { grid: { color: GRID_COLOR() }, border: { display: false }, ticks: { font: CHART_FONT, color: TICK_COLOR(), precision: 0, padding: 6 }, beginAtZero: true },
                    },
                },
            });
        }

        return () => {
            charts.current.pecosas?.destroy();
            charts.current.pecosas = null;
        };
    }, [panel && JSON.stringify(panel.pecosas_por_mes)]);

    useEffect(() => {
        if (!panel) return undefined;

        charts.current.productos?.destroy();
        charts.current.productos = null;

        const { leche, hojuelas } = panel.productos_distribuidos;
        if (productosCanvas.current && !(leche.every((v) => v === 0) && hojuelas.every((v) => v === 0))) {
            const lineSeries = (label, values, color, fill, dash) => ({
                label,
                data: values,
                borderColor: color,
                backgroundColor: fill,
                borderWidth: 2.5,
                borderDash: dash || [],
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHitRadius: 12,
                fill: true,
                tension: 0.35,
            });
            charts.current.productos = new Chart(productosCanvas.current, {
                type: 'line',
                data: {
                    labels: MESES,
                    datasets: [
                        lineSeries('Leche', leche, C.leche, C.lecheFill),
                        lineSeries('Hojuelas', hojuelas, C.hojuelas, C.hojuelasFill, [6, 4]),
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 500, easing: 'easeOutQuart' },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: true, position: 'top', align: 'end', labels: { font: CHART_FONT, boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', padding: 14 } },
                        tooltip: { callbacks: { label: (item) => ` ${item.dataset.label}: ${nfmt(item.raw)}` } },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { font: CHART_FONT, color: TICK_COLOR() } },
                        y: { grid: { color: GRID_COLOR() }, border: { display: false }, ticks: { font: CHART_FONT, color: TICK_COLOR(), padding: 6 }, beginAtZero: true },
                    },
                },
            });
        }

        return () => {
            charts.current.productos?.destroy();
            charts.current.productos = null;
        };
    }, [panel && JSON.stringify(panel.productos_distribuidos)]);

    useEffect(() => {
        if (!panel) return undefined;

        charts.current.donut?.destroy();
        charts.current.donut = null;

        if (donutCanvas.current) {
            const { socios, beneficiarios } = panel.socios_vs_beneficiarios;
            charts.current.donut = new Chart(donutCanvas.current, {
                type: 'doughnut',
                data: {
                    labels: ['Socios', 'Beneficiarios'],
                    datasets: [{
                        data: [socios, beneficiarios],
                        backgroundColor: [C.socios, C.beneficiarios],
                        hoverBackgroundColor: [C.sociosHover, C.beneficiariosHover],
                        borderColor: chartInk().donutBorder,
                        borderWidth: 3,
                        spacing: 2,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    animation: { animateRotate: true, duration: 550 },
                    plugins: {
                        legend: { display: false },
                        tooltip: { displayColors: false, callbacks: { label: (item) => ` ${item.label}: ${nfmt(item.raw)}` } },
                    },
                },
                plugins: [donutCenterText],
            });
        }

        return () => {
            charts.current.donut?.destroy();
            charts.current.donut = null;
        };
    }, [panel && JSON.stringify(panel.socios_vs_beneficiarios)]);

    useEffect(() => {
        if (!panel) return undefined;

        charts.current.topComites?.destroy();
        charts.current.topComites = null;

        if (topComitesCanvas.current) {
            const labels = panel.top_comites.map((c) => c.nombre.slice(0, 12));
            const data = panel.top_comites.map((c) => c.total);
            charts.current.topComites = new Chart(topComitesCanvas.current, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Beneficiarios',
                        data,
                        backgroundColor: C.bar,
                        hoverBackgroundColor: C.barHover,
                        borderRadius: 5,
                        borderSkipped: false,
                        maxBarThickness: 22,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 500, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: { displayColors: false, callbacks: { label: (item) => ` ${nfmt(item.raw)} beneficiarios` } },
                    },
                    scales: {
                        x: { grid: { color: GRID_COLOR() }, border: { display: false }, ticks: { font: CHART_FONT, color: TICK_COLOR(), precision: 0 } },
                        y: { grid: { display: false }, border: { display: false }, ticks: { font: { family: CHART_FONT.family, size: 10 }, color: TICK_COLOR() } },
                    },
                },
            });
        }

        return () => {
            charts.current.topComites?.destroy();
            charts.current.topComites = null;
        };
    }, [panel && JSON.stringify(panel.top_comites)]);

    if (error) {
        return (
            <div className="empty-state">
                <i className="fas fa-exclamation-triangle" />
                <p>No se pudo cargar el panel de inicio. Recarga la página.</p>
            </div>
        );
    }

    if (!panel) {
        return (
            <div className="flex items-center justify-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin mr-2" /> Cargando panel...
            </div>
        );
    }

const { stats, pecosas_por_mes: pecosasPorMes, socios_vs_beneficiarios: sociosVsBeneficiarios, top_comites: topComites } = panel;
    const cardsPeriodLabel = periodoMes && periodoAnio ? `${MESES_LARGOS[periodoMes - 1]} ${periodoAnio}` : '';

    return (
        <div>
            <div className="inicio-hero relative rounded-2xl sm:rounded-3xl overflow-hidden mb-6 sm:mb-8 shadow-lg">
                <div className="absolute inset-0">
                    <img src={`${window.APP_URL || ''}/img/niños.jpg`} alt="Banner" className="w-full h-full object-cover" />
                </div>
                <div className="absolute inset-0 bg-gradient-to-r from-blue/60 to-navy/40" />
                <div className="relative z-10 w-full flex flex-col sm:flex-row items-start sm:items-end justify-between p-5 sm:p-8 gap-4 sm:gap-8">
                    <div>
                        <div className="flex items-center gap-2 mb-3 sm:mb-4">
                            <span className="w-2.5 h-2.5 rounded-full pulse-dot" style={{ background: '#D6EAFC' }} />
                            <span className="text-white/90 text-xs sm:text-sm font-semibold uppercase tracking-widest">Sistema activo</span>
                        </div>
                        <h1 className="text-white font-extrabold text-3xl sm:text-5xl leading-tight mb-3">
                            Panel de Control
                            <br />
                            <span style={{ color: '#FEF3DC' }}>PROVALE</span>
                        </h1>
                        <p className="text-white/80 text-sm sm:text-lg font-medium max-w-lg">
                            Gestiona beneficiarios, club de madres y entregas de manera eficiente.
                        </p>
                        <div className="flex flex-wrap gap-3 sm:gap-4 mt-5 sm:mt-7">
                            {allowedSections.has('productos') && (
                                <button
                                    type="button"
                                    onClick={() => onNavigate?.('productos', 'new-pecosa')}
                                    className="px-4 sm:px-6 py-2.5 sm:py-3 bg-white/20 backdrop-blur-sm text-white font-semibold rounded-lg text-sm sm:text-base hover:bg-white/30 transition-all border border-white/20"
                                >
                                    <i className="fas fa-plus mr-2" />Registrar Pecosa
                                </button>
                            )}
                            {allowedSections.has('comites') && (
                                <button
                                    type="button"
                                    onClick={() => onNavigate?.('comites')}
                                    className="px-4 sm:px-6 py-2.5 sm:py-3 bg-white font-semibold rounded-lg text-sm sm:text-base border border-blue/15 hover:bg-blue-light transition-all shadow-sm"
                                    style={{ color: '#0B3A66' }}
                                >
                                    <i className="fas fa-file-alt mr-2" />Comités
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-3">
                <div>
                    <h2 className="font-extrabold text-charcoal text-base sm:text-lg">Resumen del período</h2>
                    <p className="text-xs sm:text-sm text-slate" aria-live="polite">Datos vigentes y entregas de {cardsPeriodLabel}</p>
                </div>
                <div className="flex items-center gap-2">
                    <FiltroSelect value={periodoMes || ''} onChange={setPeriodoMes} label="Mes del resumen">
                        {MESES_LARGOS.map((month, index) => <option key={month} value={index + 1}>{month}</option>)}
                    </FiltroSelect>
                    <FiltroSelect value={periodoAnio || ''} onChange={setPeriodoAnio} label="Año del resumen">
                        {ANIOS.map((year) => <option key={year} value={year}>{year}</option>)}
                    </FiltroSelect>
                </div>
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
                <StatCard
                    icon="fa-users"
                    iconClass="bg-blue-light text-blue"
                    barClass="bg-gradient-to-r from-blue to-sky"
                    badge={cardsPeriodLabel}
                    badgeClass="text-blue bg-blue-light"
                    value={nfmt(stats.total_socios)}
                    label="Socias únicas corregidas"
                    detail={stats.roster_audit?.metrics?.socios}
                    onDetail={() => setAuditMetric('socios')}
                />
                <StatCard
                    icon="fa-user-check"
                    iconClass="bg-sky-light text-sky"
                    barClass="bg-gradient-to-r from-sky to-[#7ec3e8]"
                    badge={cardsPeriodLabel}
                    badgeClass="text-sky bg-sky-light"
                    value={nfmt(stats.total_beneficiarios)}
                    label="Beneficiarios únicos corregidos"
                    detail={stats.roster_audit?.metrics?.beneficiarios}
                    onDetail={() => setAuditMetric('beneficiarios')}
                />
                <StatCard
                    icon="fa-heart"
                    iconClass="bg-amber-light text-amber"
                    barClass="bg-gradient-to-r from-amber to-[#f0c567]"
                    badge={`${stats.total_pecosas} PECOSAs`}
                    badgeClass="text-amber bg-amber-light"
                    value={nfmt(stats.total_comites)}
                    label="Clubes del padrón"
                    detail={stats.roster_audit?.metrics?.clubes}
                    onDetail={() => setAuditMetric('clubes')}
                />
                <StockCard
                    products={stats.stock_productos}
                    closed={(stats.stock_productos ?? []).every((product) => product.cerrado)}
                    periodLabel={cardsPeriodLabel}
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div className="panel-card bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-mist shadow-sm">
                    <div className="flex items-center justify-between mb-4">
                        <div>
                            <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base">PECOSAs por Mes</h3>
                            <p className="text-slate text-xs sm:text-sm">Salidas {anioPecosas}</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="px-2 py-1 text-[10px] sm:text-xs font-bold bg-blue-light text-blue rounded-lg whitespace-nowrap">{pecosasPorMes.total_anio} total</span>
                            <FiltroSelect value={anioPecosas} onChange={setAnioPecosas} label="Año de PECOSAs por mes">
                                {ANIOS.map((y) => <option key={y} value={y}>{y}</option>)}
                            </FiltroSelect>
                        </div>
                    </div>
                    <div className="chart-wrap h-40 sm:h-48 relative">
                        {pecosasPorMes.data.every((v) => v === 0) ? (
                            <div className="empty-state absolute inset-0">
                                <i className="fas fa-file-invoice" />
                                <p className="text-xs sm:text-sm">Aún no hay PECOSAs registradas en {anioPecosas}</p>
                            </div>
                        ) : (
                            <canvas ref={pecosasCanvas} />
                        )}
                    </div>
                </div>

                <div className="panel-card bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-mist shadow-sm">
                    <div className="flex items-center justify-between mb-4">
                        <div>
                            <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base">Productos Distribuidos</h3>
                            <p className="text-slate text-xs sm:text-sm">Leche y Hojuelas - {anioProductos}</p>
                        </div>
                        <div className="flex items-center gap-1 sm:gap-2">
                            <span className="px-1.5 sm:px-2 py-1 text-[10px] sm:text-xs font-semibold bg-blue-light text-blue rounded">Leche</span>
                            <span className="px-1.5 sm:px-2 py-1 text-[10px] sm:text-xs font-semibold bg-amber-light text-amber rounded">Hojuelas</span>
                            <FiltroSelect value={anioProductos} onChange={setAnioProductos} label="Año de productos distribuidos">
                                {ANIOS.map((y) => <option key={y} value={y}>{y}</option>)}
                            </FiltroSelect>
                        </div>
                    </div>
                    <div className="chart-wrap h-40 sm:h-48 relative">
                        {panel.productos_distribuidos.leche.every((v) => v === 0) && panel.productos_distribuidos.hojuelas.every((v) => v === 0) ? (
                            <div className="empty-state absolute inset-0">
                                <i className="fas fa-boxes-stacked" />
                                <p className="text-xs sm:text-sm">Aún no hay movimientos de productos en {anioProductos}</p>
                            </div>
                        ) : (
                            <canvas ref={productosCanvas} />
                        )}
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div className="panel-card bg-white rounded-xl sm:rounded-2xl p-4 sm:p-5 border border-mist shadow-sm">
                    <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base mb-1">Socios vs Beneficiarios</h3>
                    <p className="text-slate text-xs sm:text-sm mb-2">Vigentes · {cardsPeriodLabel}</p>
                    <div className="chart-wrap h-32 sm:h-36">
                        <canvas ref={donutCanvas} />
                    </div>
                    <div className="mt-3 grid grid-cols-2 gap-2">
                        <div className="text-center p-2 bg-blue-light rounded-lg">
                            <div className="text-lg sm:text-xl font-bold text-blue">{sociosVsBeneficiarios.socios}</div>
                            <div className="text-[9px] sm:text-[10px] font-medium text-slate uppercase">Socios</div>
                        </div>
                        <div className="text-center p-2 bg-sky-light rounded-lg">
                            <div className="text-lg sm:text-xl font-bold text-sky">{sociosVsBeneficiarios.beneficiarios}</div>
                            <div className="text-[9px] sm:text-[10px] font-medium text-slate uppercase">Beneficiarios</div>
                        </div>
                    </div>
                </div>

                <div className="panel-card bg-white rounded-xl sm:rounded-2xl p-4 sm:p-5 border border-mist shadow-sm">
                    <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base mb-1">Top Comités</h3>
                    <p className="text-slate text-xs sm:text-sm mb-3">Con más beneficiarios · {cardsPeriodLabel}</p>
                    <div className="chart-wrap h-36 sm:h-44">
                        {topComites.length === 0 ? (
                            <div className="empty-state">
                                <i className="fas fa-people-roof" />
                                <p className="text-xs sm:text-sm">Aún no hay comités con beneficiarios</p>
                            </div>
                        ) : (
                            <canvas ref={topComitesCanvas} />
                        )}
                    </div>
                </div>

                <div className="panel-card bg-white rounded-xl sm:rounded-2xl p-4 sm:p-5 border border-mist shadow-sm">
                    <h3 className="dashboard-section-title font-extrabold text-sm sm:text-base mb-4">Acciones Rápidas</h3>
                    <div className="grid grid-cols-2 gap-2">
                        {allowedSections.has('socios') && (
                            <QuickButton
                                onClick={() => onNavigate?.('socios', 'beneficiarios-padron')}
                                icon="fa-file-pdf"
                                label="Padrón Beneficiarios"
                                bgClass="bg-blue-light hover:bg-blue/10"
                                tileClass="bg-blue"
                                textClass="text-blue"
                            />
                        )}
                        {allowedSections.has('comites') && (
                            <QuickButton
                                onClick={() => onNavigate?.('comites', 'comites-padron')}
                                icon="fa-file-pdf"
                                label="Padrón Comité"
                                bgClass="bg-amber-light hover:bg-amber/10"
                                tileClass="bg-amber"
                                textClass="text-amber"
                            />
                        )}
                        {allowedSections.has('movimientos') && (
                            <QuickButton
                                onClick={() => onNavigate?.('movimientos', 'reparticion')}
                                icon="fa-file-pdf"
                                label="Repartición"
                                bgClass="bg-teal-light hover:bg-teal/10"
                                tileClass="bg-teal"
                                textClass="text-teal"
                            />
                        )}
                        {allowedSections.has('productos') && (
                            <QuickButton
                                onClick={() => onNavigate?.('productos', 'productos')}
                                icon="fa-box"
                                label="Productos"
                                bgClass="bg-sky-light hover:bg-sky/10"
                                tileClass="bg-sky"
                                textClass="text-sky"
                            />
                        )}
                    </div>
                </div>
            </div>

            {auditMetric && stats.roster_audit && (
                <RosterAuditModal
                    audit={stats.roster_audit}
                    metricKey={auditMetric}
                    periodLabel={cardsPeriodLabel}
                    onClose={() => setAuditMetric(null)}
                />
            )}
        </div>
    );
}
