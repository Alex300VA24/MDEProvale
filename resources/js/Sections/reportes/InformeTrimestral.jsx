import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import Chart from 'chart.js/auto';
import http from '../../http';
import errorMessage from '../../errorMessage';
import { useToast } from '../../Components/Toast';

const BASE = '/api/dashboard/reportes-pvl';
const QUARTERS = [['1', 'I Trimestre (ene – mar)'], ['2', 'II Trimestre (abr – jun)'], ['3', 'III Trimestre (jul – sep)'], ['4', 'IV Trimestre (oct – dic)']];
const COLORS = { blue: '#1E5799', amber: '#C77700', teal: '#0E8A7A', navy: '#0B3A66', coral: '#C4452D' };
const PALETTE = [COLORS.blue, COLORS.amber, COLORS.teal, COLORS.navy, COLORS.coral, '#6B7FA3', '#8A5A00', '#2C6BB3'];
const FONT = { family: "'Source Sans 3', system-ui, sans-serif", size: 11 };
const META_KEY = 'reportes.informe.meta';

const labelClass = 'block mb-1 text-xs font-bold uppercase tracking-wider text-slate';
const inputClass = 'w-full border-2 border-mist bg-white px-3 py-2.5 text-sm font-semibold text-charcoal focus:border-blue focus:outline-none focus:ring-2 focus:ring-blue/15';

const n0 = (value) => Number(value || 0).toLocaleString('es-PE');
const n2 = (value) => Number(value || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const TICK = '#5A7FA8';
const GRID = 'rgba(15,42,74,.08)';

function baseOptions(extra = {}) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 400 },
        plugins: { legend: { display: false }, ...(extra.plugins || {}) },
        scales: {
            x: { grid: { display: false }, border: { display: false }, ticks: { font: FONT, color: TICK }, ...(extra.x || {}) },
            y: { grid: { color: GRID }, border: { display: false }, beginAtZero: true, ticks: { font: FONT, color: TICK, precision: 0 }, ...(extra.y || {}) },
        },
    };
}

function ChartCard({ chartKey, registry, onReady, title, subtitle, config, empty, height = 'h-64' }) {
    const canvas = useRef(null);
    const key = JSON.stringify(config);

    useEffect(() => {
        if (empty) {
            delete registry.current[chartKey];
            onReady();
            return undefined;
        }
        if (!canvas.current) return undefined;
        const chart = new Chart(canvas.current, { ...config, options: { ...config.options, animation: false } });
        registry.current[chartKey] = chart;
        onReady();
        return () => { chart.destroy(); delete registry.current[chartKey]; };
    }, [key, empty]);

    return (
        <figure className="border border-mist bg-white p-4">
            <figcaption className="mb-3">
                <strong className="block font-heading text-sm font-extrabold text-navy">{title}</strong>
                {subtitle && <span className="text-xs text-slate">{subtitle}</span>}
            </figcaption>
            {empty
                ? <div className={`flex ${height} items-center justify-center text-sm text-earth`}>Sin datos registrados en el periodo.</div>
                : <div className={`relative ${height}`}><canvas ref={canvas} role="img" aria-label={title} /></div>}
        </figure>
    );
}

function Kpi({ label, value, hint, tone = 'text-navy' }) {
    return (
        <div className="border border-mist bg-white px-4 py-3">
            <span className="text-[11px] font-bold uppercase tracking-wide text-slate">{label}</span>
            <strong className={`mt-1 block font-heading text-2xl font-extrabold tabular-nums ${tone}`}>{value}</strong>
            {hint && <span className="text-xs text-earth">{hint}</span>}
        </div>
    );
}

function Charts({ report, registry, onReady }) {
    const { months, ingresos_por_producto: byProduct, distribucion_por_producto: byDist, beneficiarios_categorias: cats } = report;
    const labels = months.map((m) => m.name);
    const hasCats = cats.some((c) => c.rural + c.urbana > 0);
    const colorsFor = (rows) => rows.map((_, i) => PALETTE[i % PALETTE.length]);
    const common = { registry, onReady };
    const stackedScales = { x: { stacked: true, grid: { display: false }, ticks: { font: FONT, color: TICK } }, y: { stacked: true, beginAtZero: true, grid: { color: GRID }, ticks: { font: FONT, color: TICK, precision: 0 } } };

    return (
        <div className="grid gap-4 lg:grid-cols-2">
            <ChartCard {...common} chartKey="ingresos_mes" title="Ingresos por mes (S/)" subtitle="Valor de productos ingresados al almacén" empty={months.every((m) => m.ingresos_importe === 0)}
                config={{ type: 'bar', data: { labels, datasets: [{ label: 'S/', data: months.map((m) => m.ingresos_importe), backgroundColor: COLORS.blue, borderRadius: 6, maxBarThickness: 48 }] }, options: { ...baseOptions(), plugins: { legend: { display: false }, tooltip: { callbacks: { label: (i) => ` S/ ${n2(i.raw)}` } } } } }} />
            <ChartCard {...common} chartKey="entregas_mes" title="Entregas por mes y producto" subtitle="Cantidad entregada mediante PECOSA (unidad de cada producto)" empty={!report.distribucion_mensual.length}
                config={{ type: 'bar', data: { labels, datasets: report.distribucion_mensual.map((r, i) => ({ label: r.producto, data: r.data, backgroundColor: PALETTE[i % PALETTE.length], borderRadius: 4, maxBarThickness: 48 })) }, options: { ...baseOptions({ ...stackedScales }), plugins: { legend: { position: 'bottom', labels: { font: FONT, boxWidth: 12 } } } } }} />
            <ChartCard {...common} chartKey="distribucion_producto" title="Distribución por producto" subtitle="Participación en el trimestre (por cantidad entregada)" empty={!byDist.length}
                config={{ type: 'doughnut', data: { labels: byDist.map((r) => r.producto), datasets: [{ data: byDist.map((r) => r.cantidad), backgroundColor: colorsFor(byDist), borderColor: '#fff', borderWidth: 2 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '58%', plugins: { legend: { position: 'bottom', labels: { font: FONT, boxWidth: 12 } }, tooltip: { callbacks: { label: (i) => ` ${i.label}: ${n0(i.raw)} ${byDist[i.dataIndex].unidad}(s)` } } } } }} />
            <ChartCard {...common} chartKey="ingresos_producto" title="Ingresos por producto (S/)" subtitle="Valor acumulado del trimestre" empty={!byProduct.length}
                config={{ type: 'bar', data: { labels: byProduct.map((r) => r.producto), datasets: [{ label: 'S/', data: byProduct.map((r) => r.importe), backgroundColor: colorsFor(byProduct), borderRadius: 6, maxBarThickness: 28 }] }, options: { ...baseOptions(), indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: { label: (i) => ` S/ ${n2(i.raw)}` } } } } }} />
            <div className="lg:col-span-2">
                <ChartCard {...common} chartKey="beneficiarios" title="Beneficiarios por categoría y zona" subtitle="Población activa al cierre del periodo" empty={!hasCats} height="h-72"
                    config={{ type: 'bar', data: { labels: cats.map((c) => c.label), datasets: [{ label: 'Urbana', data: cats.map((c) => c.urbana), backgroundColor: COLORS.blue, borderRadius: 4 }, { label: 'Rural', data: cats.map((c) => c.rural), backgroundColor: COLORS.teal, borderRadius: 4 }] }, options: { ...baseOptions({ ...stackedScales }), plugins: { legend: { position: 'bottom', labels: { font: FONT, boxWidth: 12 } } } } }} />
            </div>
        </div>
    );
}

/** Vista previa fiel al documento: Times New Roman, todo en negro. */
function Letterhead({ data }) {
    return (
        <div className="informe-lh">
            <img src="/img/muni2-bn.png" alt="" />
            <div>
                <div className="muni">{data.municipalidad}</div>
                <div className="small">{data.creacion}</div>
                <div className="small">{data.telefono}</div>
                <div className="area">{data.area}</div>
                <div className="prog">{data.programa}</div>
            </div>
        </div>
    );
}

function Block({ block, chartImage }) {
    switch (block.type) {
        case 'title': return <div className="informe-title">{block.text}</div>;
        case 'meta': return (
            <dl className="informe-meta">
                {block.rows.map(([label, lines]) => (
                    <div key={label}><dt>{label}</dt><dd>: {lines.map(([text, bold], i) => <span key={i} className="block">{bold ? <strong>{text}</strong> : text}</span>)}</dd></div>
                ))}
            </dl>
        );
        case 'rule': return <hr className="informe-rule" />;
        case 'h': return <h3 className="informe-h">{block.text}</h3>;
        case 'p': return <p>{block.text}</p>;
        case 'ol': return <ol className="list-decimal pl-6">{block.items.map((item, i) => <li key={i}>{item}</li>)}</ol>;
        case 'table': return (
            <>
                {block.caption && <div className="informe-cap">{block.caption}</div>}
                <table className="informe-table">
                    <thead><tr>{block.head.map((h, i) => <th key={h} className={block.align[i] === 'r' ? 'num' : ''}>{h}</th>)}</tr></thead>
                    <tbody>
                        {block.rows.map((row, r) => <tr key={r}>{row.map((v, i) => <td key={i} className={block.align[i] === 'r' ? 'num' : ''}>{v}</td>)}</tr>)}
                        {block.total && <tr className="total">{block.total.map((v, i) => <td key={i} className={block.align[i] === 'r' ? 'num' : ''}>{v}</td>)}</tr>}
                    </tbody>
                </table>
            </>
        );
        case 'image': return chartImage ? <figure className="informe-fig"><img src={chartImage} alt={block.caption} /><figcaption>{block.caption}</figcaption></figure> : null;
        case 'sign': return <div className="informe-sign"><div className="line" /><strong>{block.name}</strong><span>{block.role}</span></div>;
        case 'note': return <p className="informe-note">{block.text}</p>;
        default: return null;
    }
}

function AiNotice() {
    return (
        <section className="no-print border border-mist bg-white p-4" aria-label="Análisis con IA">
            <strong className="font-heading text-base font-extrabold text-navy"><i className="fas fa-wand-magic-sparkles mr-2 text-blue" aria-hidden="true" />Análisis con IA al descargar</strong>
            <p className="mt-1 max-w-3xl text-sm text-earth">Al descargar el Word o el PDF, la IA lee las cifras de los 3 meses, las compara con el trimestre anterior y redacta el análisis de cada gráfico, el análisis por mes, las conclusiones y las recomendaciones. La vista previa muestra solo las cifras y los gráficos.</p>
        </section>
    );
}

export default function InformeTrimestral({ canView }) {
    const toast = useToast();
    const now = new Date();
    const [year, setYear] = useState(now.getFullYear());
    const [quarter, setQuarter] = useState(String(Math.ceil((now.getMonth() + 1) / 3)));
    const [report, setReport] = useState(null);
    const [loading, setLoading] = useState(false);
    const [exporting, setExporting] = useState(null);
    const [blocks, setBlocks] = useState([]);
    const [letterhead, setLetterhead] = useState(null);
    const [chartsTick, setChartsTick] = useState(0);
    const registry = useRef({});
    const [meta, setMeta] = useState(() => {
        try { return JSON.parse(localStorage.getItem(META_KEY)) || {}; } catch { return {}; }
    });

    const onReady = useCallback(() => setChartsTick((tick) => tick + 1), []);
    const setField = (field) => (event) => setMeta((m) => ({ ...m, [field]: event.target.value }));
    useEffect(() => { try { localStorage.setItem(META_KEY, JSON.stringify(meta)); } catch { /* sin almacenamiento */ } }, [meta]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const response = await http.get(`${BASE}/trimestral`, { params: { year, quarter }, timeout: 120000 });
            setReport(response.data);
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo consolidar el trimestre.'));
        } finally {
            setLoading(false);
        }
    }, [year, quarter, toast]);

    useEffect(() => { if (canView) load(); }, [canView, load]);

    // Vista previa: el servidor arma los bloques del documento (misma fuente que Word y PDF).
    useEffect(() => {
        if (!report?.has_data) { setBlocks([]); return undefined; }
        const timer = setTimeout(async () => {
            try {
                const response = await http.post(`${BASE}/trimestral/documento`, { year, quarter, meta });
                setBlocks(response.data.blocks || []);
                setLetterhead(response.data.letterhead || null);
            } catch { /* la vista previa se reintenta con el siguiente cambio */ }
        }, 450);
        return () => clearTimeout(timer);
    }, [report, meta, year, quarter]);

    const chartImages = useMemo(() => Object.fromEntries(Object.entries(registry.current).map(([key, chart]) => [key, chart.toBase64Image('image/png', 1)])), [chartsTick, report]);

    const exportAs = async (format) => {
        setExporting(format);
        try {
            const response = await http.post(`${BASE}/trimestral/exportar/${format}`, { year, quarter, meta, charts: chartImages }, { responseType: 'blob', timeout: 240000 });
            if (response.headers['x-analisis-ia'] === 'no-disponible') {
                toast.error('La IA no respondió: el documento se generó solo con las cifras. Intenta descargarlo de nuevo.');
            }
            const url = URL.createObjectURL(response.data);
            const link = document.createElement('a');
            link.href = url;
            link.download = `INFORME-PVL-${quarter}T-${year}.${format}`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        } catch (error) {
            toast.error(`No se pudo generar el ${format === 'pdf' ? 'PDF' : 'Word'}.`);
        } finally {
            setExporting(null);
        }
    };

    return (
        <div className="px-4 py-6 sm:px-6">
            <div className="grid gap-4 md:grid-cols-[minmax(220px,1fr)_minmax(110px,.4fr)_auto] md:items-end">
                <div><label className={labelClass} htmlFor="rq-quarter">Trimestre</label><select id="rq-quarter" value={quarter} onChange={(e) => setQuarter(e.target.value)} className={inputClass}>{QUARTERS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</select></div>
                <div><label className={labelClass} htmlFor="rq-year">Año</label><input id="rq-year" type="number" min="2000" max="2100" value={year} onChange={(e) => setYear(e.target.value)} className={inputClass} /></div>
                <button type="button" onClick={load} disabled={loading} className="btn-secondary inline-flex min-h-11 items-center justify-center gap-2 disabled:opacity-50"><i className={`fas ${loading ? 'fa-spinner fa-spin' : 'fa-rotate'}`} aria-hidden="true" /> Actualizar datos</button>
            </div>

            {loading && !report && <div className="flex items-center justify-center gap-2 py-16 text-earth"><i className="fas fa-spinner fa-spin" /> Consolidando información del trimestre…</div>}

            {report && (
                <div className={`mt-6 space-y-6 ${loading ? 'opacity-60' : ''}`}>
                    {!report.has_data && <div className="border border-amber/35 bg-amber-light px-4 py-3 text-sm text-amber-dark">El sistema no tiene ingresos, entregas ni beneficiarios registrados para este trimestre.</div>}

                    <section className="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Indicadores del trimestre">
                        <Kpi label="Ingresos (S/)" value={n2(report.totals.ingresos_importe)} hint={`${n0(report.totals.ingresos_registros)} registro(s)`} tone="text-blue" />
                        <Kpi label="Cantidad entregada" value={n0(report.totals.unidades_distribuidas)} hint={`${n0(report.totals.distribuciones)} línea(s) PECOSA`} tone="text-amber-dark" />
                        <Kpi label="Comités atendidos" value={n0(report.totals.comites_atendidos)} />
                        <Kpi label="Beneficiarios" value={n0(report.totals.beneficiarios)} hint="Al cierre del periodo" tone="text-teal" />
                    </section>

                    <section aria-label="Gráficos"><Charts report={report} registry={registry} onReady={onReady} /></section>

                    <AiNotice />

                    <section className="grid gap-4 border border-mist bg-fog p-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Datos del informe">
                        <div className="sm:col-span-2 lg:col-span-3"><strong className="font-heading text-sm font-extrabold text-navy">Datos de cabecera del informe</strong><p className="text-xs text-slate">Se recuerdan en este equipo para el próximo informe.</p></div>
                        <div><label className={labelClass} htmlFor="im-number">N.° de informe</label><input id="im-number" value={meta.number || ''} onChange={setField('number')} maxLength={80} className={inputClass} placeholder={`123-${year}-MDE/GDS/SGPS`} /></div>
                        <div><label className={labelClass} htmlFor="im-to">A (destinatario)</label><input id="im-to" value={meta.to || ''} onChange={setField('to')} maxLength={160} className={inputClass} /></div>
                        <div><label className={labelClass} htmlFor="im-torole">Cargo del destinatario</label><input id="im-torole" value={meta.to_role || ''} onChange={setField('to_role')} maxLength={160} className={inputClass} /></div>
                        <div><label className={labelClass} htmlFor="im-from">De (remitente)</label><input id="im-from" value={meta.from || ''} onChange={setField('from')} maxLength={160} className={inputClass} /></div>
                        <div><label className={labelClass} htmlFor="im-fromrole">Cargo del remitente</label><input id="im-fromrole" value={meta.from_role || ''} onChange={setField('from_role')} maxLength={160} className={inputClass} placeholder="Programa Vaso de Leche" /></div>
                        <div><label className={labelClass} htmlFor="im-place">Lugar</label><input id="im-place" value={meta.place || ''} onChange={setField('place')} maxLength={100} className={inputClass} placeholder="La Esperanza" /></div>
                        <div><label className={labelClass} htmlFor="im-date">Fecha</label><input id="im-date" value={meta.date || ''} onChange={setField('date')} maxLength={60} className={inputClass} placeholder="Hoy, si se deja vacío" /></div>
                        <div className="sm:col-span-2"><label className={labelClass} htmlFor="im-subject">Asunto (opcional)</label><input id="im-subject" value={meta.subject || ''} onChange={setField('subject')} maxLength={300} className={inputClass} placeholder="Por defecto: INFORMA REPORTES DEL SISTEMA DE CONTRALORÍA RESPECTO AL PROGRAMA VASO DE LECHE" /></div>
                    </section>

                    {report.has_data && (
                        <>
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <strong className="font-heading text-base font-extrabold text-navy">Vista previa del informe</strong>
                                <div className="flex flex-wrap gap-2">
                                    <button type="button" onClick={() => exportAs('docx')} disabled={!!exporting} className="btn-secondary inline-flex min-h-11 items-center gap-2 disabled:opacity-50"><i className={`fas ${exporting === 'docx' ? 'fa-spinner fa-spin' : 'fa-file-word'}`} aria-hidden="true" /> Descargar Word</button>
                                    <button type="button" onClick={() => exportAs('pdf')} disabled={!!exporting} className="btn-primary inline-flex min-h-11 items-center gap-2 disabled:opacity-50"><i className={`fas ${exporting === 'pdf' ? 'fa-spinner fa-spin' : 'fa-file-pdf'}`} aria-hidden="true" /> Descargar PDF</button>
                                </div>
                            </div>
                            <div className="overflow-x-auto bg-fog p-3 sm:p-6">
                                <article className="informe-paper mx-auto bg-white">
                                    {letterhead && <Letterhead data={letterhead} />}
                                    {blocks.map((block, i) => <Block key={i} block={block} chartImage={block.type === 'image' ? chartImages[block.key] : null} />)}
                                    {!blocks.length && <p className="text-center">Preparando vista previa…</p>}
                                </article>
                            </div>
                        </>
                    )}

                    <aside className="border border-mist bg-white px-4 py-3 text-sm text-earth">
                        <strong className="block text-charcoal"><i className="fas fa-circle-info mr-2 text-blue" aria-hidden="true" />Información que Contraloría solicita y el sistema aún no registra</strong>
                        <ul className="mt-2 list-disc pl-5">{report.no_disponible.map((line) => <li key={line}>{line}</li>)}</ul>
                    </aside>
                </div>
            )}
        </div>
    );
}
