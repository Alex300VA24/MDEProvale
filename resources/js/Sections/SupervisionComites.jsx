import { useCallback, useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import { useToast } from '../Components/Toast';
import PdfLinkButton from '../Components/PdfLinkButton';
import errorMessage from '../errorMessage';

const BASE = '/api/dashboard/supervision-comites';

const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const MONTHS_SHORT = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

const pad = (n) => String(n).padStart(2, '0');
const NEXT = { '': 'SI', SI: 'NO', NO: '' };

const selectCls =
    'px-3 py-2 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf';

const headCell = 'border border-charcoal/40 bg-slate/20 px-2 py-1.5 text-[11px] font-extrabold text-charcoal whitespace-nowrap';
const bodyCell = 'border border-charcoal/30 px-2 py-1 text-[11px] font-semibold text-charcoal';

export default function SupervisionComites() {
    const toast = useToast();
    const { modules } = usePage().props;
    const canEdit = !!(modules ?? []).find((m) => m.slug === 'club-madres')?.can_edit;

    const now = new Date();
    const [year, setYear] = useState(now.getFullYear());
    const [month, setMonth] = useState(now.getMonth() + 1);
    const [search, setSearch] = useState('');
    const [rows, setRows] = useState([]);
    const [offDays, setOffDays] = useState({});
    const [loading, setLoading] = useState(true);

    const days = useMemo(() => {
        const total = new Date(year, month, 0).getDate();
        return Array.from({ length: total }, (_, i) => {
            const d = i + 1;
            const dow = new Date(year, month - 1, d).getDay();
            return { d, date: `${year}-${pad(month)}-${pad(d)}`, weekend: dow === 0 || dow === 6, sunday: dow === 0 };
        });
    }, [year, month]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const { data } = await http.get(BASE, { params: { year, month } });
            setRows(data.rows ?? []);
            setOffDays(data.off_days ?? {});
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo cargar la supervisión.'));
        } finally {
            setLoading(false);
        }
    }, [year, month]);

    useEffect(() => {
        load();
    }, [load]);

    const visible = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return rows;
        return rows.filter((r) => `${r.code} ${r.name} ${r.president} ${r.sector}`.toLowerCase().includes(q));
    }, [rows, search]);

    const toggle = async (row, date) => {
        if (!canEdit) return;
        const prev = row.visits[date] ?? '';
        const next = NEXT[prev];
        const apply = (value) =>
            setRows((all) =>
                all.map((r) => {
                    if (r.id !== row.id) return r;
                    const visits = { ...r.visits };
                    if (value) visits[date] = value;
                    else delete visits[date];
                    return { ...r, visits };
                })
            );
        apply(next);
        try {
            await http.put(`${BASE}/${row.id}/visita`, { date, result: next || null });
        } catch (err) {
            apply(prev);
            toast.error(errorMessage(err, 'No se pudo guardar la marca.'));
        }
    };

    const toggleOffDay = async (day) => {
        if (!canEdit || day.weekend) return;
        const isOff = day.date in offDays;
        let reason = null;
        if (!isOff) {
            reason = window.prompt('Motivo de que no hay supervisión este día:', 'SIN SUPERVISION');
            if (reason === null) return;
            reason = reason.trim().toUpperCase() || 'SIN SUPERVISION';
        }
        const before = offDays;
        setOffDays((current) => {
            const next = { ...current };
            if (isOff) delete next[day.date];
            else next[day.date] = reason;
            return next;
        });
        try {
            await http.put(`${BASE}/dia-sin-supervision`, { date: day.date, off: !isOff, reason });
        } catch (err) {
            setOffDays(before);
            toast.error(errorMessage(err, 'No se pudo guardar el día.'));
        }
    };

    const years = [now.getFullYear() - 1, now.getFullYear(), now.getFullYear() + 1];

    return (
        <section className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
            <header className="px-4 sm:px-6 py-4 sm:py-5">
                <h1 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                    <i className="fas fa-clipboard-check text-leaf" aria-hidden="true" />
                    Supervisión de Comités
                </h1>
                <p className="text-earth text-xs sm:text-sm mt-1">
                    Marca cada visita del día: un clic para SI, otro para NO y otro para limpiar.
                </p>
            </header>

            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-3 px-4 sm:px-6 py-3 border-y-2 border-wheat">
                <div className="flex flex-wrap items-center gap-2">
                    <select value={month} onChange={(e) => setMonth(Number(e.target.value))} className={selectCls} aria-label="Mes">
                        {MONTHS.map((m, i) => (
                            <option key={m} value={i + 1}>{m}</option>
                        ))}
                    </select>
                    <select value={year} onChange={(e) => setYear(Number(e.target.value))} className={selectCls} aria-label="Año">
                        {years.map((y) => (
                            <option key={y} value={y}>{y}</option>
                        ))}
                    </select>
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Buscar comité, presidenta o sector"
                        className={`${selectCls} w-full sm:w-72`}
                    />
                </div>
                <PdfLinkButton
                    href={`${window.APP_URL || ''}/club-reconocimientos/acta-supervision`}
                    loadingTitle="Generando Acta de Supervisión"
                    icon="fa-file-signature"
                    className="btn-secondary flex items-center justify-center gap-2 text-xs sm:text-sm disabled:opacity-60"
                >
                    Acta de Supervisión
                </PdfLinkButton>
            </div>

            <div className="p-4 sm:p-6">
                {loading ? (
                    <p className="text-sm text-earth py-10 text-center">
                        <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />Cargando comités…
                    </p>
                ) : visible.length === 0 ? (
                    <p className="text-sm text-earth py-10 text-center">No hay comités que coincidan con la búsqueda.</p>
                ) : (
                    <div className="overflow-auto max-h-[calc(100vh-20rem)] min-h-[16rem] border-2 border-charcoal/30 rounded-lg">
                        <table className="border-collapse text-[11px]">
                            <thead className="sticky top-0 z-20">
                                <tr>
                                    <th className={headCell}>Cód.</th>
                                    <th className={headCell}>Club de Madres</th>
                                    <th className={headCell}>Presidenta</th>
                                    <th className={headCell}>Dirección</th>
                                    <th className={headCell}>Sector</th>
                                    <th className={headCell}>Benef.</th>
                                    {days.map((day) => {
                                        const off = day.weekend || day.date in offDays;
                                        const clickable = canEdit && !day.weekend;
                                        return (
                                            <th
                                                key={day.date}
                                                className={`${headCell} text-center ${off ? 'bg-yellow-300' : ''}`}
                                            >
                                                {clickable ? (
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleOffDay(day)}
                                                        title={day.date in offDays ? 'Quitar día sin supervisión' : 'Marcar día sin supervisión'}
                                                        className="font-extrabold hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-leaf"
                                                    >
                                                        {pad(day.d)}-{MONTHS_SHORT[month - 1]}
                                                    </button>
                                                ) : (
                                                    <>{pad(day.d)}-{MONTHS_SHORT[month - 1]}</>
                                                )}
                                            </th>
                                        );
                                    })}
                                </tr>
                            </thead>
                            <tbody>
                                {visible.map((row, rowIndex) => (
                                    <tr key={row.id} className="hover:bg-leaf-light/40">
                                        <td className={`${bodyCell} text-center`}>{row.code}</td>
                                        <td className={`${bodyCell} min-w-[200px] uppercase`}>{row.name}</td>
                                        <td className={`${bodyCell} min-w-[180px] uppercase`}>{row.president}</td>
                                        <td className={`${bodyCell} min-w-[160px] uppercase`}>{row.address}</td>
                                        <td className={`${bodyCell} min-w-[120px] uppercase`}>{row.sector}</td>
                                        <td className={`${bodyCell} text-center`}>{row.beneficiaries}</td>
                                        {days.map((day) => {
                                            const closed = day.weekend || day.date in offDays;
                                            if (closed) {
                                                if (rowIndex !== 0) return null;
                                                return (
                                                    <td
                                                        key={day.date}
                                                        rowSpan={visible.length}
                                                        className="border border-charcoal/30 bg-yellow-300 text-center align-middle w-8 min-w-[2rem]"
                                                    >
                                                        <span
                                                            className="inline-block font-extrabold tracking-[0.35em] text-charcoal"
                                                            style={{ writingMode: 'vertical-rl', textOrientation: 'upright' }}
                                                        >
                                                            {day.weekend ? (day.sunday ? 'DOMINGO' : 'SABADO') : offDays[day.date] || 'SIN SUPERVISION'}
                                                        </span>
                                                    </td>
                                                );
                                            }
                                            const value = row.visits[day.date] ?? '';
                                            const tone =
                                                value === 'SI'
                                                    ? 'bg-lime-500 text-charcoal'
                                                    : value === 'NO'
                                                    ? 'bg-red-500 text-white'
                                                    : '';
                                            return (
                                                <td
                                                    key={day.date}
                                                    className={`border border-charcoal/30 p-0 text-center font-extrabold w-16 min-w-[4rem] ${tone}`}
                                                >
                                                    <button
                                                        type="button"
                                                        onClick={() => toggle(row, day.date)}
                                                        disabled={!canEdit}
                                                        aria-label={`${row.name}, ${day.date}: ${value || 'sin marca'}`}
                                                        className="block w-full h-7 focus:outline-none focus-visible:ring-2 focus-visible:ring-leaf disabled:cursor-default"
                                                    >
                                                        {value}
                                                    </button>
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </section>
    );
}
