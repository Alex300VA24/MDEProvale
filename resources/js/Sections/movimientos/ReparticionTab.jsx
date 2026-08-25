import { useEffect, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import { useDebounced } from '../socios/hooks';
import errorMessage from '../../errorMessage';

const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';

const MONTHS = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

const PAGE_SIZE = 15;

function quantityLabel(value, singular, plural) {
    const quantity = Number(value ?? 0);
    return `${quantity} ${quantity === 1 ? singular : plural}`;
}

function packageBreakdown(primaryValue, primarySingular, primaryPlural, secondaryValue, secondarySingular, secondaryPlural) {
    return `${quantityLabel(primaryValue, primarySingular, primaryPlural)} y ${quantityLabel(secondaryValue, secondarySingular, secondaryPlural)}`;
}

export default function ReparticionTab() {
    const toast = useToast();
    const now = new Date();
    const [year, setYear] = useState(now.getFullYear());
    const [month, setMonth] = useState(now.getMonth() + 1);
    const [report, setReport] = useState(null);
    const [notice, setNotice] = useState(null);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);

    const debouncedYear = useDebounced(year, 500);

    useEffect(() => {
        if (!debouncedYear || debouncedYear < 2000 || debouncedYear > 2100) return;

        let active = true;
        setLoading(true);
        setNotice(null);
        (async () => {
            try {
                const res = await http.get('/api/dashboard/movimientos/reparticion', { params: { year: debouncedYear, month } });
                if (active) {
                    setReport(res.data);
                    setPage(1);
                }
            } catch (err) {
                if (active) {
                    setReport(null);
                    setNotice(errorMessage(err, 'No se pudo generar el reporte de repartición.'));
                }
            } finally {
                if (active) setLoading(false);
            }
        })();
        return () => {
            active = false;
        };
    }, [debouncedYear, month]);

    const totalAssociations = report?.associations.length ?? 0;
    const totalPages = Math.max(1, Math.ceil(totalAssociations / PAGE_SIZE));
    const pagedAssociations = report
        ? report.associations.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE)
        : [];
    const rangeFrom = totalAssociations === 0 ? 0 : (page - 1) * PAGE_SIZE + 1;
    const rangeTo = Math.min(page * PAGE_SIZE, totalAssociations);

    return (
        <>
            <div className="mb-6 flex flex-col sm:flex-row items-end gap-3 sm:gap-4 flex-wrap">
                <div className="w-full sm:w-32">
                    <label className={labelCls}>Año</label>
                    <input type="number" min="2000" max="2100" value={year} onChange={(e) => setYear(Number(e.target.value))} className={inputCls} />
                </div>
                <div className="w-full sm:w-48">
                    <label className={labelCls}>Mes</label>
                    <select value={month} onChange={(e) => setMonth(Number(e.target.value))} className={inputCls}>
                        {MONTHS.map((m, i) => (
                            <option key={i} value={i + 1}>{m}</option>
                        ))}
                    </select>
                </div>
                {loading && (
                    <span className="text-xs sm:text-sm text-earth font-semibold">
                        <i className="fas fa-spinner fa-spin mr-2" /> Cargando...
                    </span>
                )}
                {report && (
                    <a href={report.pdf_url} target="_blank" rel="noreferrer" className="btn-primary text-xs sm:text-sm">
                        <i className="fas fa-file-pdf mr-2" /> Descargar Repartición
                    </a>
                )}
            </div>

            {notice && (
                <div className="empty-state">
                    <i className="fas fa-circle-exclamation" />
                    <p>{notice}</p>
                </div>
            )}

            {report && (
                <>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div className="p-4 rounded-xl border-2 border-wheat bg-cream">
                            <p className="text-[11px] font-bold text-earth uppercase tracking-wider">Total Beneficiarios</p>
                            <p className="text-2xl font-extrabold text-charcoal">{report.total_beneficiarios}</p>
                        </div>
                        <div className="p-4 rounded-xl border-2 border-wheat bg-cream">
                            <p className="text-[11px] font-bold text-earth uppercase tracking-wider">Leche (tarros)</p>
                            <p className="text-2xl font-extrabold text-charcoal">{report.total_leche_litros}</p>
                        </div>
                        <div className="p-4 rounded-xl border-2 border-wheat bg-cream">
                            <p className="text-[11px] font-bold text-earth uppercase tracking-wider">Hojuelas (kg)</p>
                            <p className="text-2xl font-extrabold text-charcoal">{report.total_hojuelas_kg}</p>
                        </div>
                    </div>

                    <div className="overflow-x-auto -mx-4 sm:mx-0">
                        <table className="data-table w-full text-xs sm:text-sm min-w-[900px]">
                            <thead>
                                <tr>
                                    <th className="px-3 sm:px-4 py-3 text-left">Código</th>
                                    <th className="px-3 sm:px-4 py-3 text-left">Comité</th>
                                    <th className="px-3 sm:px-4 py-3 text-left">Presidenta</th>
                                    <th className="px-3 sm:px-4 py-3 text-right">Beneficiarios</th>
                                    <th className="px-3 sm:px-4 py-3 text-right">Leche</th>
                                    <th className="px-3 sm:px-4 py-3 text-right">Hojuelas</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.associations.length === 0 ? (
                                    <tr>
                                        <td colSpan={6}>
                                            <div className="empty-state">
                                                <i className="fas fa-truck" />
                                                <p>No hay comités con beneficiarios activos en el período seleccionado</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    pagedAssociations.map((a) => (
                                        <tr key={a.id} className="row-enter">
                                            <td className="px-3 sm:px-4 py-3 font-mono text-earth">{a.codigo}</td>
                                            <td className="px-3 sm:px-4 py-3 font-semibold">{a.nombre}</td>
                                            <td className="px-3 sm:px-4 py-3">{a.presidenta || '-'}</td>
                                            <td className="px-3 sm:px-4 py-3 text-right font-bold">{a.beneficiarios}</td>
                                            <td className="px-3 sm:px-4 py-3 text-right font-semibold whitespace-nowrap">
                                                {packageBreakdown(a.leche_cajas, 'caja', 'cajas', a.leche_tarros, 'tarro', 'tarros')}
                                            </td>
                                            <td className="px-3 sm:px-4 py-3 text-right font-semibold whitespace-nowrap">
                                                {packageBreakdown(a.hojuelas_sacos, 'saco', 'sacos', a.hojuelas_kilos, 'kg', 'kg')}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {totalAssociations > 0 && (
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4">
                            <span className="text-xs sm:text-sm text-earth font-semibold">
                                Mostrando {rangeFrom} - {rangeTo} de {totalAssociations} comités
                            </span>
                            <nav className="flex flex-wrap items-center justify-center gap-1">
                                <button
                                    type="button"
                                    disabled={page <= 1}
                                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                                    className="min-w-9 h-9 px-2 rounded-lg text-sm font-semibold transition-all bg-cream text-earth hover:bg-mist disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    ‹
                                </button>
                                {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
                                    <button
                                        key={p}
                                        type="button"
                                        onClick={() => setPage(p)}
                                        className={`min-w-9 h-9 px-2 rounded-lg text-sm font-semibold transition-all ${
                                            p === page
                                                ? 'bg-gradient-to-r from-sky to-blue text-white shadow'
                                                : 'bg-cream text-earth hover:bg-mist'
                                        }`}
                                    >
                                        {p}
                                    </button>
                                ))}
                                <button
                                    type="button"
                                    disabled={page >= totalPages}
                                    onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                                    className="min-w-9 h-9 px-2 rounded-lg text-sm font-semibold transition-all bg-cream text-earth hover:bg-mist disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    ›
                                </button>
                            </nav>
                        </div>
                    )}
                </>
            )}

            {!report && !notice && !loading && (
                <div className="empty-state">
                    <i className="fas fa-truck" />
                    <p>Seleccione año y mes para generar el reporte de repartición mensual.</p>
                </div>
            )}
        </>
    );
}
