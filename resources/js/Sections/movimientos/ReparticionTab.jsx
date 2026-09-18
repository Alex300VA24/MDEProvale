import { useCallback, useEffect, useMemo, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import { useDebounced } from '../socios/hooks';
import errorMessage from '../../errorMessage';
import PdfLinkButton from '../../Components/PdfLinkButton';

const BASE = '/api/dashboard/movimientos/reparticion';
const PAGE_SIZE = 12;
const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const inputCls = 'w-full min-h-11 px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20 transition-colors';
const compactInputCls = 'w-full min-h-10 px-2 py-1.5 border border-mist rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20';
const labelCls = 'block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1';

function calculate(club, assignment, config) {
    const beneficiaries = Math.max(0, Number(club.beneficiarios_base) + Number(assignment.beneficiary_adjustment || 0));
    const days = Math.max(1, Number(config.service_days) || 1);
    const cansPerBox = Math.max(1, Number(config.milk_cans_per_box) || 1);
    const kgPerSack = Math.max(1, Number(config.oat_kg_per_sack) || 1);
    const milk = Math.round((beneficiaries * (Number(config.milk_grams_per_beneficiary) || 0) * days) / Math.max(1, Number(config.milk_can_grams) || 1));
    const oat = Math.round((beneficiaries * (Number(config.oat_grams_per_beneficiary) || 0) * days) / Math.max(1, Number(config.oat_bag_grams) || 1));
    return {
        ...club,
        beneficiarios: beneficiaries,
        ajuste_beneficiarios: Number(assignment.beneficiary_adjustment || 0),
        vuelta: Math.max(1, Number(assignment.route_number || 1)),
        observacion: assignment.observation || '',
        leche_total: milk,
        leche_cajas: Math.floor(milk / cansPerBox),
        leche_tarros: milk % cansPerBox,
        hojuelas_kg: oat,
        hojuelas_sacos: Math.floor(oat / kgPerSack),
        hojuelas_kilos: oat % kgPerSack,
        racion_diaria_leche: milk / days,
        racion_diaria_hojuelas: oat / days,
    };
}

function ExportButton({ href, icon, children, tone = 'secondary', disabled = false }) {
    const className = `${tone === 'primary' ? 'btn-primary' : 'btn-secondary'} inline-flex min-h-11 items-center justify-center gap-2 text-xs sm:text-sm ${disabled ? 'pointer-events-none opacity-40' : ''}`;
    return <a href={disabled ? undefined : href} target="_blank" rel="noreferrer" aria-disabled={disabled} className={className}><i className={`fas ${icon}`} aria-hidden="true" />{children}</a>;
}

function ExportPdfButton({ href, icon, children, tone = 'secondary', disabled = false, loadingTitle }) {
    const className = `${tone === 'primary' ? 'btn-primary' : 'btn-secondary'} inline-flex min-h-11 items-center justify-center gap-2 text-xs sm:text-sm disabled:opacity-40 disabled:pointer-events-none`;
    return <PdfLinkButton href={disabled ? '' : href} icon={icon} disabled={disabled} loadingTitle={loadingTitle} className={className}>{children}</PdfLinkButton>;
}

export default function ReparticionTab({ can = {}, onGoToMovimientos }) {
    const toast = useToast();
    const now = new Date();
    const [year, setYear] = useState(now.getFullYear());
    const [month, setMonth] = useState(now.getMonth() + 1);
    const [report, setReport] = useState(null);
    const [config, setConfig] = useState(null);
    const [assignments, setAssignments] = useState({});
    const [productMode, setProductMode] = useState('complete');
    const [advanced, setAdvanced] = useState(false);
    const [notice, setNotice] = useState(null);
    const [noticeCode, setNoticeCode] = useState(null);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [dirty, setDirty] = useState(false);
    const [page, setPage] = useState(1);
    const debouncedYear = useDebounced(year, 500);
    const maximumServiceDays = new Date(Number(year), Number(month), 0).getDate();

    const receiveReport = useCallback((data) => {
        setReport(data);
        setConfig(data.configuration);
        setAssignments(Object.fromEntries(data.associations.map((club) => [club.id, {
            association_id: club.id,
            route_number: club.vuelta,
            beneficiary_adjustment: club.ajuste_beneficiarios,
            observation: club.observacion || '',
        }])));
        setDirty(false);
        setPage(1);
    }, []);

    const load = useCallback(async () => {
        if (!debouncedYear || debouncedYear < 2000 || debouncedYear > 2100) return;
        setLoading(true);
        setNotice(null);
        setNoticeCode(null);
        try {
            const response = await http.get(BASE, { params: { year: debouncedYear, month } });
            receiveReport(response.data);
        } catch (error) {
            setReport(null);
            setConfig(null);
            setNoticeCode(error?.response?.data?.code ?? null);
            setNotice(errorMessage(error, 'No se pudo generar el reporte de distribución.'));
        } finally {
            setLoading(false);
        }
    }, [debouncedYear, month, receiveReport]);

    useEffect(() => { load(); }, [load]);

    const calculated = useMemo(() => {
        if (!report || !config) return [];
        return report.associations.map((club) => calculate(club, assignments[club.id] || {}, config))
            .sort((a, b) => a.vuelta - b.vuelta || String(a.codigo).localeCompare(String(b.codigo)));
    }, [report, config, assignments]);

    const totals = useMemo(() => {
        const beneficiaries = calculated.reduce((sum, club) => sum + club.beneficiarios, 0);
        const milk = calculated.reduce((sum, club) => sum + club.leche_total, 0);
        const oat = calculated.reduce((sum, club) => sum + club.hojuelas_kg, 0);
        return {
            beneficiaries,
            milk,
            oat,
            milkBoxes: config ? Math.floor(milk / Math.max(1, Number(config.milk_cans_per_box) || 1)) : 0,
            milkLoose: config ? milk % Math.max(1, Number(config.milk_cans_per_box) || 1) : 0,
            oatSacks: config ? Math.floor(oat / Math.max(1, Number(config.oat_kg_per_sack) || 1)) : 0,
            oatLoose: config ? oat % Math.max(1, Number(config.oat_kg_per_sack) || 1) : 0,
        };
    }, [calculated, config]);

    const totalPages = Math.max(1, Math.ceil(calculated.length / PAGE_SIZE));
    const pageRows = calculated.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

    const changeConfig = (key, value) => {
        setConfig((current) => ({ ...current, [key]: value }));
        setDirty(true);
    };

    const changeAssignment = (id, key, value) => {
        setAssignments((current) => ({
            ...current,
            [id]: { ...current[id], association_id: id, [key]: value },
        }));
        setDirty(true);
    };

    const save = async () => {
        setSaving(true);
        try {
            const response = await http.put(BASE, {
                year: Number(year),
                month: Number(month),
                service_days: Number(config.service_days),
                milk_grams_per_beneficiary: Number(config.milk_grams_per_beneficiary),
                oat_grams_per_beneficiary: Number(config.oat_grams_per_beneficiary),
                milk_can_grams: Number(config.milk_can_grams),
                oat_bag_grams: Number(config.oat_bag_grams),
                milk_cans_per_box: Number(config.milk_cans_per_box),
                oat_kg_per_sack: Number(config.oat_kg_per_sack),
                assignments: Object.values(assignments).map((item) => ({
                    ...item,
                    route_number: Number(item.route_number || 1),
                    beneficiary_adjustment: Number(item.beneficiary_adjustment || 0),
                })),
            });
            receiveReport(response.data);
            toast.success('Configuración y vueltas guardadas. Todos los totales fueron recalculados.');
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo guardar la distribución.'));
        } finally {
            setSaving(false);
        }
    };

    const actaUrl = report?.exports?.acta_pdf ? `${report.exports.acta_pdf}&product_mode=${productMode}` : '#';

    return (
        <div aria-busy={loading || saving}>
            <div className="mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[128px_190px_150px_1fr] gap-3 items-end">
                <div><label htmlFor="distribution-year" className={labelCls}>Año</label><input id="distribution-year" type="number" min="2000" max="2100" value={year} onChange={(event) => setYear(Number(event.target.value))} className={inputCls} /></div>
                <div><label htmlFor="distribution-month" className={labelCls}>Mes</label><select id="distribution-month" value={month} onChange={(event) => setMonth(Number(event.target.value))} className={inputCls}>{MONTHS.map((name, index) => <option key={name} value={index + 1}>{name}</option>)}</select></div>
                {config && <div><label htmlFor="service-days" className={labelCls}>Días de atención</label><input id="service-days" type="number" min="28" max={maximumServiceDays} value={config.service_days} onChange={(event) => changeConfig('service_days', event.target.value)} className={inputCls} /></div>}
                <div className="flex flex-wrap gap-2 lg:justify-end">
                    {can.edit && config && <button type="button" onClick={save} disabled={saving || !dirty} className="btn-primary min-h-11 disabled:opacity-40 disabled:cursor-not-allowed"><i className={`fas ${saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'} mr-2`} aria-hidden="true" />{saving ? 'Guardando…' : 'Guardar cambios'}</button>}
                    {loading && <span role="status" className="min-h-11 inline-flex items-center text-sm font-semibold text-earth"><i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />Recalculando…</span>}
                </div>
            </div>

            {notice && <div className="empty-state" role="alert"><i className="fas fa-circle-exclamation" aria-hidden="true" /><h4 className="font-extrabold text-charcoal">Repartición no disponible</h4><p>{notice}</p><div className="mt-3 flex w-full flex-wrap justify-center gap-2">{noticeCode === 'INGRESO_REQUIRED' && can.create && <button type="button" onClick={onGoToMovimientos} className="btn-primary inline-flex h-11 min-w-48 items-center justify-center px-6 py-0"><i className="fas fa-right-left mr-2 text-sm" aria-hidden="true" />Ir a Movimientos</button>}<button type="button" onClick={load} className="btn-secondary inline-flex h-11 min-w-48 items-center justify-center px-6 py-0">Reintentar</button></div></div>}

            {report && config && <>
                <section className="mb-5 rounded-2xl border border-mist bg-cream/50 p-4" aria-labelledby="formula-title">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div><h4 id="formula-title" className="font-extrabold text-charcoal">Parámetros del período</h4><p className="text-sm text-earth mt-1">Vista previa automática. Exportaciones se habilitan después de guardar.</p></div>
                        <button type="button" onClick={() => setAdvanced((value) => !value)} aria-expanded={advanced} className="btn-secondary min-h-11"><i className="fas fa-sliders mr-2" aria-hidden="true" />{advanced ? 'Ocultar capacidades' : 'Editar capacidades'}</button>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                        <div><label htmlFor="milk-grams" className={labelCls}>Leche por beneficiario/día (g)</label><input id="milk-grams" type="number" min="0.001" step="0.001" value={config.milk_grams_per_beneficiary} onChange={(event) => changeConfig('milk_grams_per_beneficiary', event.target.value)} className={inputCls} /></div>
                        <div><label htmlFor="oat-grams" className={labelCls}>Hojuela por beneficiario/día (g)</label><input id="oat-grams" type="number" min="0.001" step="0.001" value={config.oat_grams_per_beneficiary} onChange={(event) => changeConfig('oat_grams_per_beneficiary', event.target.value)} className={inputCls} /></div>
                    </div>
                    {advanced && <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
                        <div><label htmlFor="can-grams" className={labelCls}>Capacidad tarro (g)</label><input id="can-grams" type="number" min="1" step="0.001" value={config.milk_can_grams} onChange={(event) => changeConfig('milk_can_grams', event.target.value)} className={inputCls} /></div>
                        <div><label htmlFor="bag-grams" className={labelCls}>Capacidad bolsa (g)</label><input id="bag-grams" type="number" min="1" step="0.001" value={config.oat_bag_grams} onChange={(event) => changeConfig('oat_bag_grams', event.target.value)} className={inputCls} /></div>
                        <div><label htmlFor="box-cans" className={labelCls}>Tarros por caja</label><input id="box-cans" type="number" min="1" value={config.milk_cans_per_box} onChange={(event) => changeConfig('milk_cans_per_box', event.target.value)} className={inputCls} /></div>
                        <div><label htmlFor="sack-kg" className={labelCls}>Kg por saco</label><input id="sack-kg" type="number" min="1" value={config.oat_kg_per_sack} onChange={(event) => changeConfig('oat_kg_per_sack', event.target.value)} className={inputCls} /></div>
                    </div>}
                </section>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                    {[
                        ['fa-users', 'Beneficiarios', totals.beneficiaries, 'bg-blue-light text-blue'],
                        ['fa-box', 'Carga de leche', `${totals.milkBoxes} cajas + ${totals.milkLoose} tarros`, 'bg-teal-light text-teal'],
                        ['fa-wheat-awn', 'Carga de hojuela', `${totals.oatSacks} sacos + ${totals.oatLoose} kg`, 'bg-sun-light text-amber-700'],
                    ].map(([icon, label, value, tone]) => <div key={label} className="rounded-2xl border border-mist bg-white p-4 shadow-sm"><div className={`w-10 h-10 rounded-xl inline-flex items-center justify-center ${tone}`}><i className={`fas ${icon}`} aria-hidden="true" /></div><div className="mt-3 text-xl sm:text-2xl font-extrabold text-navy tabular-nums">{value}</div><div className="text-sm text-earth">{label}</div></div>)}
                </div>

                <section className="mb-5" aria-labelledby="routes-title">
                    <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-2 mb-3"><div><h4 id="routes-title" className="font-extrabold text-charcoal">Vueltas y diversificación</h4><p className="text-sm text-earth">Ajuste suma o resta cupos solo en este período.</p></div><span className="text-xs font-bold text-earth">{calculated.length} comités · {new Set(calculated.map((club) => club.vuelta)).size} vueltas</span></div>

                    <div className="space-y-3 md:hidden">
                        {pageRows.map((club) => <article key={club.id} className="rounded-2xl border border-mist bg-white p-4 shadow-sm">
                            <div className="flex justify-between gap-3"><div className="min-w-0"><div className="font-mono text-xs text-earth">{club.codigo}</div><h5 className="font-extrabold text-charcoal break-words">{club.nombre}</h5><p className="text-sm text-earth break-words">{club.sector}</p></div><span className="h-fit rounded-full bg-blue-light text-blue px-3 py-1 text-xs font-bold whitespace-nowrap">{club.beneficiarios} benef.</span></div>
                            <div className="grid grid-cols-2 gap-3 mt-4"><div><label className={labelCls} htmlFor={`route-mobile-${club.id}`}>Vuelta N°</label><input id={`route-mobile-${club.id}`} type="number" min="1" value={assignments[club.id]?.route_number ?? 1} onChange={(event) => changeAssignment(club.id, 'route_number', event.target.value)} disabled={!can.edit} className={compactInputCls} /></div><div><label className={labelCls} htmlFor={`adjust-mobile-${club.id}`}>Ajuste cupos</label><input id={`adjust-mobile-${club.id}`} type="number" value={assignments[club.id]?.beneficiary_adjustment ?? 0} onChange={(event) => changeAssignment(club.id, 'beneficiary_adjustment', event.target.value)} disabled={!can.edit} className={compactInputCls} /></div></div>
                            <div className="mt-3 grid grid-cols-2 gap-2 text-sm"><div className="rounded-lg bg-teal-light p-2"><strong>{club.leche_total}</strong> tarros<br /><span>{club.leche_cajas} cajas + {club.leche_tarros} sueltos</span></div><div className="rounded-lg bg-sun-light p-2"><strong>{club.hojuelas_kg}</strong> kg<br /><span>{club.hojuelas_sacos} sacos + {club.hojuelas_kilos} kg</span></div></div>
                        </article>)}
                    </div>

                    <div className="hidden md:block overflow-x-auto rounded-xl border border-mist">
                        <table className="data-table w-full min-w-[1050px] text-sm">
                            <thead><tr><th className="px-3 py-3 text-left">Código / comité</th><th className="px-3 py-3 text-left">Sector</th><th className="px-3 py-3 text-center">Base</th><th className="px-3 py-3 text-center">Ajuste</th><th className="px-3 py-3 text-center">Total</th><th className="px-3 py-3 text-center">Vuelta</th><th className="px-3 py-3 text-right">Leche</th><th className="px-3 py-3 text-right">Hojuela</th><th className="px-3 py-3 text-left">Observación</th></tr></thead>
                            <tbody>{pageRows.map((club) => <tr key={club.id}>
                                <td className="px-3 py-3"><span className="font-mono text-xs text-earth">{club.codigo}</span><div className="font-bold text-charcoal max-w-52 break-words">{club.nombre}</div></td><td className="px-3 py-3 max-w-36 break-words">{club.sector || '—'}</td><td className="px-3 py-3 text-center tabular-nums">{club.beneficiarios_base}</td>
                                <td className="px-2 py-2 w-24"><label className="sr-only" htmlFor={`adjust-${club.id}`}>Ajuste de cupos para {club.nombre}</label><input id={`adjust-${club.id}`} type="number" value={assignments[club.id]?.beneficiary_adjustment ?? 0} onChange={(event) => changeAssignment(club.id, 'beneficiary_adjustment', event.target.value)} disabled={!can.edit} className={compactInputCls} /></td>
                                <td className="px-3 py-3 text-center font-extrabold tabular-nums">{club.beneficiarios}</td><td className="px-2 py-2 w-20"><label className="sr-only" htmlFor={`route-${club.id}`}>Vuelta para {club.nombre}</label><input id={`route-${club.id}`} type="number" min="1" value={assignments[club.id]?.route_number ?? 1} onChange={(event) => changeAssignment(club.id, 'route_number', event.target.value)} disabled={!can.edit} className={compactInputCls} /></td>
                                <td className="px-3 py-3 text-right whitespace-nowrap tabular-nums"><strong>{club.leche_total}</strong><br /><span className="text-xs text-earth">{club.leche_cajas} cajas + {club.leche_tarros}</span></td><td className="px-3 py-3 text-right whitespace-nowrap tabular-nums"><strong>{club.hojuelas_kg} kg</strong><br /><span className="text-xs text-earth">{club.hojuelas_sacos} sacos + {club.hojuelas_kilos} kg</span></td>
                                <td className="px-2 py-2 min-w-44"><label className="sr-only" htmlFor={`observation-${club.id}`}>Observación para {club.nombre}</label><input id={`observation-${club.id}`} type="text" maxLength="250" value={assignments[club.id]?.observation ?? ''} onChange={(event) => changeAssignment(club.id, 'observation', event.target.value)} disabled={!can.edit} className={compactInputCls} placeholder="Opcional" /></td>
                            </tr>)}</tbody>
                        </table>
                    </div>

                    {calculated.length > PAGE_SIZE && <nav aria-label="Paginación de comités" className="mt-4 flex items-center justify-between gap-3"><span className="text-sm text-earth">Página {page} de {totalPages}</span><div className="flex gap-2"><button type="button" aria-label="Página anterior" disabled={page <= 1} onClick={() => setPage((value) => value - 1)} className="btn-secondary min-w-11 min-h-11 disabled:opacity-40"><i className="fas fa-chevron-left" aria-hidden="true" /></button><button type="button" aria-label="Página siguiente" disabled={page >= totalPages} onClick={() => setPage((value) => value + 1)} className="btn-secondary min-w-11 min-h-11 disabled:opacity-40"><i className="fas fa-chevron-right" aria-hidden="true" /></button></div></nav>}
                </section>

                <section className="rounded-2xl border-2 border-wheat bg-white p-4" aria-labelledby="documents-title">
                    <div className="flex flex-col lg:flex-row lg:items-end justify-between gap-4"><div><h4 id="documents-title" className="font-extrabold text-charcoal">Documentos oficiales</h4><p className="text-sm text-earth mt-1">PDF para firma y Excel para control operativo. Reparto separa cada vuelta por página.</p></div><div className="w-full sm:w-64"><label htmlFor="product-mode" className={labelCls}>Producto del acta</label><select id="product-mode" value={productMode} onChange={(event) => setProductMode(event.target.value)} className={inputCls}><option value="complete">Leche + Hojuela</option><option value="milk">Solo Leche</option><option value="oat">Solo Hojuela</option></select></div></div>
                    {dirty && <div role="status" className="mt-3 rounded-xl bg-sun-light px-3 py-2 text-sm font-semibold text-amber-900"><i className="fas fa-circle-info mr-2" aria-hidden="true" />Guarde cambios para exportar totales actualizados.</div>}
                    <div className="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                        <div className="rounded-xl border border-mist p-3"><strong className="block text-charcoal mb-2">Reparto por vueltas</strong><div className="flex gap-2"><ExportPdfButton href={report.exports.reparto_pdf} icon="fa-file-pdf" tone="primary" disabled={dirty} loadingTitle="Generando Reparto por Vueltas">PDF</ExportPdfButton><ExportButton href={report.exports.reparto_excel} icon="fa-file-excel" disabled={dirty}>Excel</ExportButton></div></div>
                        <div className="rounded-xl border border-mist p-3"><strong className="block text-charcoal mb-2">Cargo general</strong><div className="flex gap-2"><ExportPdfButton href={report.exports.cargo_pdf} icon="fa-file-pdf" tone="primary" disabled={dirty} loadingTitle="Generando Cargo General">PDF</ExportPdfButton><ExportButton href={report.exports.cargo_excel} icon="fa-file-excel" disabled={dirty}>Excel</ExportButton></div></div>
                        <div className="rounded-xl border border-mist p-3"><strong className="block text-charcoal mb-2">Fiscalización</strong><div className="flex gap-2"><ExportPdfButton href={report.exports.fiscalizacion_pdf} icon="fa-file-pdf" tone="primary" disabled={dirty} loadingTitle="Generando Fiscalización">PDF</ExportPdfButton><ExportButton href={report.exports.fiscalizacion_excel} icon="fa-file-excel" disabled={dirty}>Excel</ExportButton></div></div>
                        <div className="rounded-xl border border-mist p-3"><strong className="block text-charcoal mb-2">Acta condicional</strong><ExportPdfButton href={actaUrl} icon="fa-file-signature" tone="primary" disabled={dirty} loadingTitle="Generando Acta Condicional">Abrir acta PDF</ExportPdfButton></div>
                    </div>
                </section>
            </>}
        </div>
    );
}
