import { Link } from '@inertiajs/react';
import PortalLayout, { portalPath } from '../../Layouts/PortalLayout';

const nf = new Intl.NumberFormat('es-PE');
const money = (v) => (v === null || v === undefined ? '—' : `S/ ${new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v)}`);

export default function Pecosa({ association, pecosa }) {
    const zone = [pecosa.association_zone_code, pecosa.association_zone_name].filter(Boolean).join(' · ') || '—';

    return (
        <>
            <Link className="portal-back-link" href={portalPath('/portal-presidentas')}>
                <i className="fas fa-arrow-left" aria-hidden="true" /> Volver al portal
            </Link>

            <div className="pagehead">
                <div>
                    <p className="eyebrow">Detalle de Pecosa</p>
                    <h1>Pecosa N.º {pecosa.pecosa_number || 'Sin número'}</h1>
                    <p className="subtitle">Entrega del {pecosa.delivery_date ?? 'Por confirmar'}</p>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
                    {pecosa.vigente
                        ? <span className="status status-ready"><i className="fas fa-circle-check" aria-hidden="true" /> Vigente</span>
                        : <span className="status status-expired"><i className="fas fa-ban" aria-hidden="true" /> Vencido</span>}
                    <a className="btn-primary portal-primary-action" href={portalPath(`/portal-presidentas/pecosas/${pecosa.id}/pdf`)} target="_blank" rel="noopener noreferrer">
                        <i className="fas fa-file-pdf" aria-hidden="true" /> Ver PDF
                    </a>
                </div>
            </div>

            <div className="grid-2">
                <section className="section" aria-labelledby="committee-title">
                    <div className="section-head"><h2 id="committee-title">Comité</h2></div>
                    <div className="section-body">
                        <dl className="data">
                            <div><dt>Nombre</dt><dd>{pecosa.association_name || association.name}</dd></div>
                            <div><dt>Código</dt><dd>{pecosa.association_code || association.code}</dd></div>
                            <div><dt>Dirección</dt><dd>{pecosa.association_address || '—'}</dd></div>
                            <div><dt>Sector</dt><dd>{pecosa.association_sector_name || '—'}</dd></div>
                            <div><dt>Zona</dt><dd>{zone}</dd></div>
                            <div><dt>Beneficiarios</dt><dd>{pecosa.beneficiaries_count ?? '—'}</dd></div>
                        </dl>
                    </div>
                </section>

                <section className="section" aria-labelledby="responsibles-title">
                    <div className="section-head"><h2 id="responsibles-title">Responsables</h2></div>
                    <div className="section-body">
                        <dl className="data">
                            {[
                                ['Presidenta', pecosa.president_name, pecosa.president_dni],
                                ['Socia encargada', pecosa.managing_partner_name, pecosa.managing_partner_dni],
                                ['Jefe de almacén', pecosa.chief_name, pecosa.chief_dni],
                                ['Almacenero', pecosa.storekeeper_name, pecosa.storekeeper_dni],
                            ].map(([label, name, dni]) => (
                                <div key={label}>
                                    <dt>{label}</dt>
                                    <dd>{name || '—'}<br /><span className="muted">DNI {dni || '—'}</span></dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </section>
            </div>

            <section className="section" aria-labelledby="products-title">
                <div className="section-head"><h2 id="products-title">Productos</h2></div>
                {pecosa.details.length > 0 ? (
                    <div className="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th><th>Unidad</th>
                                    <th className="num">Solicitado</th><th className="num">Entregado</th>
                                    <th className="num">P. unitario</th><th className="num">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                {pecosa.details.map((d, i) => (
                                    <tr key={i}>
                                        <td><strong>{d.product}</strong></td>
                                        <td>{d.uom}</td>
                                        <td className="num">{nf.format(d.quantity)}</td>
                                        <td className="num">{nf.format(d.delivered_quantity)}</td>
                                        <td className="num">{money(d.unit_price)}</td>
                                        <td className="num">{money(d.subtotal)}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colSpan={2}>Totales</td>
                                    <td className="num">{nf.format(pecosa.totals.quantity)}</td>
                                    <td className="num">{nf.format(pecosa.totals.delivered_quantity)}</td>
                                    <td />
                                    <td className="num">{money(pecosa.totals.subtotal)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                ) : (
                    <div className="empty"><i className="fas fa-box-open" aria-hidden="true" /><br />Esta Pecosa no tiene productos registrados.</div>
                )}
            </section>

            {pecosa.observation && (
                <section className="section" aria-labelledby="obs-title">
                    <div className="section-head"><h2 id="obs-title">Observación</h2></div>
                    <div className="section-body"><p style={{ margin: 0 }}>{pecosa.observation}</p></div>
                </section>
            )}
        </>
    );
}

Pecosa.layout = ({ pecosa }) => [
    PortalLayout,
    { title: `Pecosa ${pecosa?.pecosa_number ?? ''}`.trim() },
];
