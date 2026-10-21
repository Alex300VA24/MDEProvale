import PortalLayout, { portalPath } from '../../Layouts/PortalLayout';

const DOCUMENTS = [
    ['ficha', 'Ficha de beneficiarios', 'Ficha de inscripción y empadronamiento de la socia y sus beneficiarios.', 'fa-file-lines'],
    ['declaracion-domicilio', 'Declaración jurada de domicilio', 'Formato para que la socia declare su domicilio actual.', 'fa-house'],
    ['declaracion-parentesco', 'Declaración jurada de parentesco', 'Formato para declarar el parentesco con los beneficiarios.', 'fa-people-roof'],
    ['acta-renuncia', 'Acta de renuncia', 'Formato para registrar la renuncia de una socia.', 'fa-file-signature'],
];

export default function Documentos({ association }) {
    return (
        <>
            <div className="pagehead">
                <div>
                    <p className="eyebrow">Formatos en blanco</p>
                    <h1>Documentos descargables</h1>
                    <p className="subtitle">Descarga, imprime y llena a mano{association?.name ? ` · ${association.name}` : ''}</p>
                </div>
            </div>

            {!association ? (
                <div className="notice" role="alert">
                    <strong>No existe comité vigente asignado.</strong><br />
                    Solicita actualización de DNI, cargo o período de directiva al administrador.
                </div>
            ) : (
                <section className="section" aria-label="Documentos descargables">
                    <div className="table-wrap">
                        <table>
                            <thead><tr><th>Documento</th><th>Descripción</th><th>Acciones</th></tr></thead>
                            <tbody>
                                {DOCUMENTS.map(([slug, label, description, icon]) => (
                                    <tr key={slug}>
                                        <td><strong><i className={`fas ${icon}`} aria-hidden="true" /> {label}</strong></td>
                                        <td>{description}</td>
                                        <td>
                                            <a className="row-link" href={portalPath(`/portal-presidentas/documentos/${slug}`)}>
                                                <i className="fas fa-download" aria-hidden="true" /> Descargar
                                            </a>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            )}
        </>
    );
}

Documentos.layout = (page) => <PortalLayout title="Documentos descargables">{page}</PortalLayout>;
