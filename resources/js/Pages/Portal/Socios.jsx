import { useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import PortalLayout, { portalPath } from '../../Layouts/PortalLayout';

export default function Socios({ association, partners, filters }) {
    const [search, setSearch] = useState(filters.search || '');

    const clear = () => {
        setSearch('');
    };

    useEffect(() => {
        const normalizedSearch = search.trim();
        if (normalizedSearch === (filters.search || '')) return undefined;

        const debounce = window.setTimeout(() => {
            router.get(
                portalPath('/portal-presidentas/socios'),
                normalizedSearch ? { search: normalizedSearch } : {},
                {
                    only: ['association', 'partners', 'filters'],
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 400);

        return () => window.clearTimeout(debounce);
    }, [search, filters.search]);

    const totalBeneficiarios = partners.reduce((n, p) => n + p.beneficiaries.length, 0);

    return (
        <>
            <Link className="portal-back-link" href={portalPath('/portal-presidentas')}>
                <i className="fas fa-arrow-left" aria-hidden="true" /> Volver al portal
            </Link>

            <div className="pagehead">
                <div>
                    <p className="eyebrow">Consulta privada</p>
                    <h1>Socios y beneficiarios</h1>
                    <p className="subtitle">Padrón del comité{association?.name ? ` · ${association.name}` : ''}</p>
                </div>
                {association && (
                    <div className="portal-form" role="search">
                        <div>
                            <label htmlFor="search">Buscar socio</label>
                            <input id="search" name="search" type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nombre o DNI" />
                        </div>
                        <span className="portal-auto-filter"><i className="fas fa-bolt" aria-hidden="true" /> Búsqueda automática</span>
                        {search && <button type="button" className="btn-clear" onClick={clear}>Limpiar</button>}
                    </div>
                )}
            </div>

            {!association ? (
                <div className="notice" role="alert">
                    <strong>No existe comité vigente asignado.</strong><br />
                    Solicita actualización de DNI, cargo o período de directiva al administrador.
                </div>
            ) : (
                <>
                    <p className="count-line">
                        {partners.length} socio(s){filters.search ? ` para "${filters.search}"` : ''} · {totalBeneficiarios} beneficiario(s)
                    </p>

                    {partners.length === 0 ? (
                        <div className="page-empty">
                            <i className="fas fa-users-slash" aria-hidden="true" /><br />
                            No se encontraron socios{filters.search ? ' para la búsqueda.' : ' en el comité.'}
                        </div>
                    ) : (
                        partners.map((partner) => (
                            <details className="partner" key={partner.id}>
                                <summary>
                                    <span className="partner-name">{partner.name}</span>
                                    <span className="badge">DNI {partner.dni || '—'}</span>
                                    <span className="badge badge-muted">{partner.beneficiaries.length} beneficiario(s)</span>
                                    {partner.state && <span className="badge badge-muted">{partner.state}</span>}
                                </summary>
                                <div className="partner-body">
                                    <dl className="data three">
                                        <div><dt>Sexo</dt><dd>{partner.gender || '—'}</dd></div>
                                        <div><dt>Fecha de nacimiento</dt><dd>{partner.birthdate || '—'}</dd></div>
                                        <div><dt>Edad</dt><dd>{partner.age || '—'}</dd></div>
                                        <div><dt>Teléfono</dt><dd>{partner.phone || '—'}</dd></div>
                                        <div><dt>Dirección</dt><dd>{partner.address || '—'}</dd></div>
                                        <div><dt>Ingreso</dt><dd>{partner.date_begin || '—'}</dd></div>
                                        <div><dt>Retiro</dt><dd>{partner.date_end || '—'}</dd></div>
                                    </dl>

                                    {partner.observations && (
                                        <dl className="data one">
                                            <div><dt>Observaciones</dt><dd style={{ fontWeight: 600 }}>{partner.observations}</dd></div>
                                        </dl>
                                    )}

                                    <h3 className="sub">Beneficiarios</h3>
                                    {partner.beneficiaries.length > 0 ? (
                                        <div className="table-wrap">
                                            <table>
                                                <thead><tr><th>Nombre</th><th>DNI</th><th>Parentesco</th><th>Sexo</th><th>Edad</th></tr></thead>
                                                <tbody>
                                                    {partner.beneficiaries.map((b, i) => (
                                                        <tr key={i}>
                                                            <td>{b.name}</td>
                                                            <td>{b.dni || '—'}</td>
                                                            <td>{b.relationship || '—'}</td>
                                                            <td>{b.gender || '—'}</td>
                                                            <td>{b.age || '—'}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <div className="sub-empty">Este socio no tiene beneficiarios registrados.</div>
                                    )}
                                </div>
                            </details>
                        ))
                    )}
                </>
            )}
        </>
    );
}

Socios.layout = (page) => <PortalLayout title="Socios y beneficiarios">{page}</PortalLayout>;
