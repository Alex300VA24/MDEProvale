import { useCallback, useEffect, useMemo, useState } from 'react';
import http from '../../http';
import errorMessage from '../../errorMessage';
import PdfLinkButton from '../../Components/PdfLinkButton';
import { useToast } from '../../Components/Toast';
import { useDebounced } from '../socios/hooks';

const BASE = '/api/dashboard/productos-pecosas/programacion-entrega';
const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const inputCls = 'w-full min-h-11 px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20 transition-colors';
const labelCls = 'block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1';
const editableCellCls = 'w-full min-h-10 min-w-[88px] rounded-lg border-2 border-wheat bg-white px-2 py-1.5 text-right text-sm font-bold tabular-nums text-charcoal focus:border-leaf focus:outline-none focus:ring-2 focus:ring-leaf/20 disabled:border-transparent disabled:bg-transparent disabled:font-semibold disabled:text-charcoal';
const formatAmount = (value) => Number(value ?? 0).toLocaleString('es-PE', { maximumFractionDigits: 2 });

function calculateProducts(beneficiaries, configuration) {
    if (!configuration) return null;
    const days = Math.max(1, Number(configuration.service_days) || 1);
    const milk = (beneficiaries * Number(configuration.milk_grams_per_beneficiary || 0) * days)
        / Math.max(1, Number(configuration.milk_can_grams) || 1);
    const oat = (beneficiaries * Number(configuration.oat_grams_per_beneficiary || 0) * days)
        / Math.max(1, Number(configuration.oat_bag_grams) || 1);

    return {
        leche: Math.round(milk * 100) / 100,
        leche_redondeo: Math.round(milk),
        hojuela: Math.round(oat * 100) / 100,
        hojuela_redondeo: Math.round(oat),
    };
}

export default function ProgramacionEntregaTab({ can = {}, onGoToIngresos }) {
    const toast = useToast();
    const now = new Date();
    const [year, setYear] = useState(now.getFullYear());
    const [month, setMonth] = useState(now.getMonth() + 1);
    const [sector, setSector] = useState('');
    const [payload, setPayload] = useState(null);
    const [loading, setLoading] = useState(false);
    const [notice, setNotice] = useState('');
    const [noticeCode, setNoticeCode] = useState('');
    const [draftRows, setDraftRows] = useState([]);
    const [dirty, setDirty] = useState(false);
    const [saving, setSaving] = useState(false);
    const debouncedYear = useDebounced(year, 500);

    const load = useCallback(async () => {
        if (!debouncedYear || debouncedYear < 2019 || debouncedYear > 2100) return;
        setLoading(true);
        setNotice('');
        setNoticeCode('');
        try {
            const response = await http.get(BASE, {
                params: { year: debouncedYear, month, ...(sector ? { sector } : {}) },
            });
            setPayload(response.data);
            setDraftRows(response.data?.data ?? []);
            setDirty(false);
        } catch (error) {
            setPayload(null);
            setNoticeCode(error?.response?.data?.code ?? '');
            setNotice(errorMessage(error, 'No se pudo cargar la programación de entrega.'));
        } finally {
            setLoading(false);
        }
    }, [debouncedYear, month, sector]);

    useEffect(() => { load(); }, [load]);

    const rows = draftRows;
    const sectors = useMemo(() => payload?.sectors ?? [], [payload?.sectors]);
    const periodMonth = payload?.period?.month ?? month;
    const periodYear = payload?.period?.year ?? year;
    const lastDay = new Date(periodYear, periodMonth, 0).getDate();
    const rosterTitle = `PROGRAMACION DE ENTREGA DE LOS PRODUCTOS DEL PROGRAMA VASO DE LECHE (PERIODO DEL 01 AL ${String(lastDay).padStart(2, '0')} DE ${MONTHS[periodMonth - 1].toUpperCase()}) LECHE EVAPORADA ENTERA Y HOJUELAS DE QUINUA AVENA CON AZUCAR FORTIFICADO CON VITAMINAS Y MINERALES`;
    const totals = useMemo(() => rows.reduce((result, row) => ({
        beneficiaries: result.beneficiaries + Number(row.total_beneficiarios ?? 0),
        milk: result.milk + Number(row.leche ?? 0),
        milkRounded: result.milkRounded + Number(row.leche_redondeo ?? 0),
        oat: result.oat + Number(row.hojuela ?? 0),
        oatRounded: result.oatRounded + Number(row.hojuela_redondeo ?? 0),
    }), { beneficiaries: 0, milk: 0, milkRounded: 0, oat: 0, oatRounded: 0 }), [rows]);

    const changeRow = (associationId, field, value) => {
        setDraftRows((current) => current.map((row) => {
            if (row.association_id !== associationId) return row;
            const updated = { ...row, [field]: value };
            if (field === 'total_beneficiarios' && value !== '') {
                const products = calculateProducts(Math.max(0, Number(value)), payload?.calculation);
                if (products) Object.assign(updated, products);
            }
            return updated;
        }));
        setDirty(true);
    };

    const save = async () => {
        const numericFields = ['total_beneficiarios', 'leche', 'leche_redondeo', 'hojuela', 'hojuela_redondeo'];
        const invalid = rows.some((row) => numericFields.some((field) => row[field] === '' || !Number.isFinite(Number(row[field])) || Number(row[field]) < 0))
            || rows.some((row) => !Number.isInteger(Number(row.total_beneficiarios)) || !Number.isInteger(Number(row.leche_redondeo)) || !Number.isInteger(Number(row.hojuela_redondeo)));
        if (invalid) {
            toast.error('Complete todos los valores con números válidos. Beneficiarios y redondeos deben ser enteros.');
            return;
        }

        setSaving(true);
        try {
            const response = await http.put(BASE, {
                year: Number(periodYear),
                month: Number(periodMonth),
                ...(sector ? { sector } : {}),
                rows: rows.map((row) => ({
                    association_id: row.association_id,
                    total_beneficiarios: Number(row.total_beneficiarios),
                    leche: Number(row.leche),
                    leche_redondeo: Number(row.leche_redondeo),
                    hojuela: Number(row.hojuela),
                    hojuela_redondeo: Number(row.hojuela_redondeo),
                })),
            });
            setPayload(response.data);
            setDraftRows(response.data?.data ?? []);
            setDirty(false);
            toast.success('Programación guardada. PDF y totales fueron actualizados.');
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo guardar la programación.'));
        } finally {
            setSaving(false);
        }
    };

    return (
        <div aria-busy={loading || saving}>
            <div className="mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[128px_190px_minmax(220px,1fr)_auto] gap-3 items-end">
                <div>
                    <label htmlFor="schedule-year" className={labelCls}>Año</label>
                    <input id="schedule-year" type="number" min="2019" max="2100" value={year} onChange={(event) => setYear(Number(event.target.value))} className={inputCls} />
                </div>
                <div>
                    <label htmlFor="schedule-month" className={labelCls}>Mes</label>
                    <select id="schedule-month" value={month} onChange={(event) => setMonth(Number(event.target.value))} className={inputCls}>
                        {MONTHS.map((name, index) => <option key={name} value={index + 1}>{name}</option>)}
                    </select>
                </div>
                <div>
                    <label htmlFor="schedule-sector" className={labelCls}>Sector</label>
                    <select id="schedule-sector" value={sector} onChange={(event) => setSector(event.target.value)} className={inputCls}>
                        <option value="">Todos los sectores</option>
                        {sectors.map((name) => <option key={name} value={name}>{name}</option>)}
                    </select>
                </div>
                <div className="flex min-h-11 items-center justify-end gap-2">
                    {can.edit && payload && (
                        <button type="button" onClick={save} disabled={!dirty || saving} className="btn-primary inline-flex min-h-11 items-center gap-2 disabled:cursor-not-allowed disabled:opacity-40">
                            <i className={`fas ${saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'}`} aria-hidden="true" />
                            {saving ? 'Guardando…' : 'Guardar cambios'}
                        </button>
                    )}
                    {payload?.pdf_url && (
                        <PdfLinkButton href={dirty || saving ? '' : payload.pdf_url} icon="fa-file-pdf" disabled={dirty || saving} loadingTitle="Generando Programación de Entrega" className="btn-secondary inline-flex min-h-11 items-center gap-2 disabled:pointer-events-none disabled:opacity-40">
                            Abrir PDF
                        </PdfLinkButton>
                    )}
                    {loading && <span role="status" className="text-sm font-semibold text-earth"><i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />Actualizando…</span>}
                </div>
            </div>

            {notice && (
                <div className="empty-state" role="alert">
                    <i className="fas fa-calendar-xmark" aria-hidden="true" />
                    <h4 className="font-extrabold text-charcoal">Programación no disponible</h4>
                    <p className="max-w-2xl">{notice}</p>
                    <div className="mt-3 flex flex-wrap justify-center gap-2">
                        {noticeCode === 'INGRESO_REQUIRED' && (
                            <button type="button" onClick={onGoToIngresos} className="btn-primary min-h-11">
                                <i className="fas fa-arrow-right-to-bracket mr-2" aria-hidden="true" />Ir a Registro de Ingreso
                            </button>
                        )}
                        <button type="button" onClick={load} className="btn-secondary min-h-11">Reintentar</button>
                    </div>
                </div>
            )}

            {payload && (
                <>
                    <div className="mb-5 grid grid-cols-2 lg:grid-cols-4 gap-3">
                        {[
                            ['Comités programados', rows.length],
                            ['Beneficiarios', formatAmount(totals.beneficiaries)],
                            ['Leche al redondeo', formatAmount(totals.milkRounded)],
                            ['Hojuela al redondeo', `${formatAmount(totals.oatRounded)} kg`],
                        ].map(([label, value]) => (
                            <div key={label} className="rounded-xl border border-mist bg-surface px-4 py-3">
                                <div className="text-xl font-extrabold text-navy tabular-nums">{value}</div>
                                <div className="text-sm text-earth">{label}</div>
                            </div>
                        ))}
                    </div>

                    <section aria-labelledby="schedule-roster-title" className="overflow-hidden rounded-xl border border-mist bg-white">
                        <h3 id="schedule-roster-title" className="border-b border-mist bg-surface px-4 py-4 text-center text-sm font-extrabold leading-relaxed text-navy sm:px-6">
                            {rosterTitle}
                        </h3>
                        {can.edit && (
                            <p className="border-b border-mist bg-cream/40 px-4 py-2 text-center text-xs font-semibold text-earth">
                                Edite beneficiarios, leche u hojuelas. Al cambiar beneficiarios, cantidades se recalculan automáticamente.
                            </p>
                        )}
                        <div className="m-3 overflow-auto max-h-[calc(100vh-20rem)] rounded-lg border-2 border-charcoal/30">
                        <table className="data-table w-full min-w-[1420px] border-collapse text-sm [&_th]:border [&_th]:border-white/20 [&_td]:border [&_td]:border-charcoal/20 [&_th]:sticky [&_th]:top-0 [&_th]:z-10">
                            <thead>
                                <tr>
                                    <th scope="col" className="px-3 py-3 text-center">N°</th>
                                    <th scope="col" className="px-3 py-3 text-left">COD</th>
                                    <th scope="col" className="px-3 py-3 text-left">Club de madres</th>
                                    <th scope="col" className="px-3 py-3 text-left">Presidenta</th>
                                    <th scope="col" className="px-3 py-3 text-left">Dirección</th>
                                    <th scope="col" className="px-3 py-3 text-left">Sector</th>
                                    <th scope="col" className="px-3 py-3 text-center">Benef. (total)</th>
                                    <th scope="col" className="px-3 py-3 text-right">Leche</th>
                                    <th scope="col" className="px-3 py-3 text-right">Leche al redondeo</th>
                                    <th scope="col" className="px-3 py-3 text-right">Hojuela kg</th>
                                    <th scope="col" className="px-3 py-3 text-right">Hojuela al redondeo</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 ? (
                                    <tr><td colSpan={11}><div className="empty-state"><i className="fas fa-calendar-day" /><p>No hay comités para este filtro.</p></div></td></tr>
                                ) : rows.map((row, index) => (
                                    <tr key={row.association_id ?? row.codigo}>
                                        <td className="px-3 py-3 text-center tabular-nums">{index + 1}</td>
                                        <td className="px-3 py-3 font-mono text-xs text-earth">{row.codigo}</td>
                                        <td className="px-3 py-3 font-bold text-charcoal">{row.nombre}</td>
                                        <td className="px-3 py-3">{row.presidenta || 'Sin asignar'}</td>
                                        <td className="px-3 py-3">{row.direccion || '—'}</td>
                                        <td className="px-3 py-3">{row.sector || '—'}</td>
                                        <td className="px-2 py-2"><input aria-label={`Beneficiarios de ${row.nombre}`} type="number" min="0" step="1" value={row.total_beneficiarios} onChange={(event) => changeRow(row.association_id, 'total_beneficiarios', event.target.value)} disabled={!can.edit} className={editableCellCls} /></td>
                                        <td className="px-2 py-2"><input aria-label={`Leche de ${row.nombre}`} type="number" min="0" step="0.01" value={row.leche} onChange={(event) => changeRow(row.association_id, 'leche', event.target.value)} disabled={!can.edit} className={editableCellCls} /></td>
                                        <td className="px-2 py-2"><input aria-label={`Leche al redondeo de ${row.nombre}`} type="number" min="0" step="1" value={row.leche_redondeo} onChange={(event) => changeRow(row.association_id, 'leche_redondeo', event.target.value)} disabled={!can.edit} className={editableCellCls} /></td>
                                        <td className="px-2 py-2"><input aria-label={`Hojuela de ${row.nombre}`} type="number" min="0" step="0.01" value={row.hojuela} onChange={(event) => changeRow(row.association_id, 'hojuela', event.target.value)} disabled={!can.edit} className={editableCellCls} /></td>
                                        <td className="px-2 py-2"><input aria-label={`Hojuela al redondeo de ${row.nombre}`} type="number" min="0" step="1" value={row.hojuela_redondeo} onChange={(event) => changeRow(row.association_id, 'hojuela_redondeo', event.target.value)} disabled={!can.edit} className={editableCellCls} /></td>
                                    </tr>
                                ))}
                            </tbody>
                            {rows.length > 0 && (
                                <tfoot>
                                    <tr className="bg-surface font-extrabold text-charcoal">
                                        <th scope="row" colSpan={6} className="px-3 py-3 text-right">TOTAL</th>
                                        <td className="px-3 py-3 text-center tabular-nums">{formatAmount(totals.beneficiaries)}</td>
                                        <td className="px-3 py-3 text-right tabular-nums">{formatAmount(totals.milk)}</td>
                                        <td className="px-3 py-3 text-right tabular-nums">{formatAmount(totals.milkRounded)}</td>
                                        <td className="px-3 py-3 text-right tabular-nums">{formatAmount(totals.oat)}</td>
                                        <td className="px-3 py-3 text-right tabular-nums">{formatAmount(totals.oatRounded)}</td>
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                        </div>
                    </section>
                </>
            )}
        </div>
    );
}
