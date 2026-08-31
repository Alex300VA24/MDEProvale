import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import PortalLayout, { portalPath } from '../../Layouts/PortalLayout';

const nf = new Intl.NumberFormat('es-PE');

function pageLabel(raw) {
    const txt = String(raw).replace(/&laquo;|pagination\.previous/gi, '«').replace(/&raquo;|pagination\.next/gi, '»');
    return txt.replace(/<[^>]*>/g, '').trim();
}

function Pagination({ links }) {
    if (!links || links.length <= 3) return null;
    return (
        <nav className="pagination" aria-label="Paginación del historial de Pecosas">
            {links.map((link, i) => {
                const label = pageLabel(link.label);
                if (!link.url) return <span key={i} className="is-disabled">{label}</span>;
                if (link.active) return <span key={i} className="is-current" aria-current="page">{label}</span>;
                return <Link key={i} href={link.url} preserveState preserveScroll>{label}</Link>;
            })}
        </nav>
    );
}

export default function Index({ presidenta, association, scheduledPecosa, allocation, history, filters, periodIsPast, periodLabel, months, years }) {
    const [month, setMonth] = useState(filters.month);
    const [year, setYear] = useState(filters.year);
    const hasScheduledPecosa = Boolean(scheduledPecosa);
    const pecosaIsExpired = hasScheduledPecosa && !scheduledPecosa.vigente;
    const deliveryCardClass = pecosaIsExpired ? 'is-expired' : hasScheduledPecosa ? 'is-ready' : 'is-pending';
    const periodStatusClass = pecosaIsExpired ? 'status-expired' : hasScheduledPecosa ? 'status-ready' : 'status-pending';
    const periodStatusIcon = pecosaIsExpired ? 'fa-ban' : hasScheduledPecosa ? 'fa-circle-check' : 'fa-clock';
    const periodStatusLabel = pecosaIsExpired
        ? 'Vencida'
        : hasScheduledPecosa
            ? 'Programada'
            : periodIsPast ? 'No registrada' : 'Pendiente de programación';

    const applyPeriod = (nextMonth, nextYear) => {
        router.get(
            portalPath('/portal-presidentas'),
            { month: nextMonth, year: nextYear },
            {
                only: ['scheduledPecosa', 'allocation', 'history', 'filters', 'periodIsPast', 'periodLabel'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const changeMonth = (value) => {
        const nextMonth = Number(value);
        setMonth(nextMonth);
        applyPeriod(nextMonth, year);
    };

    const changeYear = (value) => {
        const nextYear = Number(value);
        setYear(nextYear);
        applyPeriod(month, nextYear);
    };

    return (
        <>
            <div className="pagehead">
                <div>
                    <p className="eyebrow">Consulta privada</p>
                    <h1>Hola, {presidenta}</h1>
                    <p className="subtitle">Programación y Pecosas del comité asignado.</p>
                </div>
                <div className="portal-form" aria-label="Seleccionar período">
                    <div>
                        <label htmlFor="month">Mes</label>
                        <select id="month" value={month} onChange={(e) => changeMonth(e.target.value)}>
                            {months.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="year">Año</label>
                        <select id="year" value={year} onChange={(e) => changeYear(e.target.value)}>
                            {years.map((y) => <option key={y} value={y}>{y}</option>)}
                        </select>
                    </div>
                    <span className="portal-auto-filter"><i className="fas fa-bolt" aria-hidden="true" /> Actualización automática</span>
                </div>
            </div>

            {!association ? (
                <div className="notice" role="alert">
                    <strong>No existe comité vigente asignado.</strong><br />
                    Solicita actualización de DNI, cargo o período de directiva al administrador.
                </div>
            ) : (
                <>
                    <div className="overview">
                        <section className="committee-card" aria-labelledby="committee-title">
                            <div>
                                <p className="eyebrow">Comité asignado</p>
                                <h2 id="committee-title">{association.name}</h2>
                                <p className="committee-meta">
                                    <i className="fas fa-location-dot" aria-hidden="true" />{' '}
                                    {association.address || 'Dirección no registrada'}
                                    {association.sector ? ` · ${association.sector}` : ''}
                                </p>
                            </div>
                            <div className="committee-code"><span>Código</span><strong>{association.code}</strong></div>
                        </section>

                        <section className={`next-delivery ${deliveryCardClass}`} aria-labelledby="next-delivery-title">
                            <p className="eyebrow" id="next-delivery-title">{periodIsPast ? 'Repartición' : 'Próxima repartición'} · {periodLabel}</p>
                            <strong className="next-delivery-date">{scheduledPecosa?.delivery_date ?? (periodIsPast ? 'Sin registro' : 'Por confirmar')}</strong>
                            <span className={`status ${periodStatusClass}`}>
                                <i className={`fas ${periodStatusIcon}`} aria-hidden="true" />
                                {periodStatusLabel}
                            </span>
                            <dl className="next-delivery-meta">
                                <div><dt>N.º de Pecosa</dt><dd>{scheduledPecosa?.pecosa_number ?? 'No emitida'}</dd></div>
                                <div><dt>Vigencia</dt><dd>{scheduledPecosa?.vigencia ?? 'Sin registro'}</dd></div>
                            </dl>
                            {scheduledPecosa && (
                                <Link className="next-delivery-link" href={portalPath(`/portal-presidentas/pecosas/${scheduledPecosa.id}`)}>
                                    <i className="fas fa-eye" aria-hidden="true" /> Ver detalle de la Pecosa
                                </Link>
                            )}
                        </section>
                    </div>

                    <div className="stats" aria-label="Resumen de repartición">
                        <div className="stat"><div className="stat-icon"><i className="fas fa-users" aria-hidden="true" /></div><span>Beneficiarios</span><strong>{allocation?.beneficiarios ?? '—'}</strong></div>
                        <div className="stat"><div className="stat-icon"><i className="fas fa-box" aria-hidden="true" /></div><span>Leche asignada</span><strong>{allocation ? `${Math.round(allocation.leche_litros)} tarros` : '—'}</strong></div>
                        <div className="stat"><div className="stat-icon"><i className="fas fa-wheat-awn" aria-hidden="true" /></div><span>Hojuelas asignadas</span><strong>{allocation ? `${Math.round(allocation.hojuelas_kg)} kg` : '—'}</strong></div>
                        <div className="stat"><div className="stat-icon"><i className="fas fa-file-lines" aria-hidden="true" /></div><span>Pecosas registradas</span><strong>{history?.total ?? 0}</strong></div>
                    </div>

                    <section className="section" aria-labelledby="history-title">
                        <div className="section-head">
                            <div>
                                <h2 id="history-title">Historial de Pecosas</h2>
                                <p>Documentos del comité · {history?.total ?? 0} en total</p>
                            </div>
                        </div>
                        {history && history.data.length > 0 ? (
                            <>
                                <div className="table-wrap">
                                    <table>
                                        <thead><tr><th>Número</th><th>Fecha de entrega</th><th>Productos</th><th>Cantidad</th><th>Vigencia</th><th>Acciones</th></tr></thead>
                                        <tbody>
                                            {history.data.map((p) => (
                                                <tr key={p.id}>
                                                    <td><strong>{p.pecosa_number}</strong></td>
                                                    <td>{p.delivery_date ?? '—'}</td>
                                                    <td>{p.products_count}</td>
                                                    <td>{nf.format(p.quantity)}</td>
                                                    <td>
                                                        {p.vigente
                                                            ? <span className="status status-ready"><i className="fas fa-circle-check" aria-hidden="true" />Vigente</span>
                                                            : <span className="status status-expired"><i className="fas fa-ban" aria-hidden="true" />Vencido</span>}
                                                    </td>
                                                    <td>
                                                        <Link className="row-link" href={portalPath(`/portal-presidentas/pecosas/${p.id}`)}>
                                                            <i className="fas fa-eye" aria-hidden="true" /> Ver
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <Pagination links={history.links} />
                            </>
                        ) : (
                            <div className="empty"><i className="fas fa-folder-open" aria-hidden="true" /><br />No hay Pecosas registradas para el comité.</div>
                        )}
                    </section>
                </>
            )}
        </>
    );
}

Index.layout = (page) => <PortalLayout title="Portal de Presidentas">{page}</PortalLayout>;
