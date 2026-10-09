import { useCallback, useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import errorMessage from '../errorMessage';
import MarkdownContent from '../Components/MarkdownContent';
import { useToast } from '../Components/Toast';

const BASE = '/api/dashboard/base-conocimiento';

const labelClass = 'block mb-1 text-xs font-bold uppercase tracking-wider text-slate';
const inputClass = 'w-full border-2 border-mist bg-white px-3 py-2.5 text-sm font-semibold text-charcoal focus:border-blue focus:outline-none focus:ring-2 focus:ring-blue/15';

function validateMunicipalReference(value) {
    const reference = value.trim();
    if (!reference) return 'Ingrese el enlace de Copia verificable o el número de resolución.';

    if (/^https?:\/\//i.test(reference)) {
        try {
            const url = new URL(reference);
            const validHost = url.hostname.toLowerCase() === 'www.muniesperanza.gob.pe';
            const validPath = url.pathname.replace(/\/$/, '') === '/website/mde2026/norma_descargar.php';
            if (!validHost || !validPath || !/^\d+$/.test(url.searchParams.get('id') || '')) {
                return 'Pegue un enlace de Copia verificable válido del portal municipal.';
            }
            return '';
        } catch {
            return 'El enlace de Copia verificable no es válido.';
        }
    }

    return /\d{1,6}\s*[-/]\s*\d{4}/i.test(reference)
        ? ''
        : 'Use un número como 0750-2026-MDE o pegue el enlace completo.';
}

function StatusBadge({ status }) {
    const style = status === 'INDEXADO'
        ? 'bg-teal-light text-teal'
        : status === 'ERROR'
            ? 'bg-coral-light text-coral'
            : 'bg-blue-light text-blue';
    return <span className={`badge ${style}`}>{status}</span>;
}

export default function BaseConocimiento() {
    const toast = useToast();
    const { modules } = usePage().props;
    const permission = (modules || []).find((item) => item.slug === 'base-conocimiento');
    const can = { view: !!permission?.can_view, create: !!permission?.can_create, del: !!permission?.can_delete };

    const [tab, setTab] = useState('preguntar');
    const [documents, setDocuments] = useState([]);
    const [municipalDocuments, setMunicipalDocuments] = useState([]);
    const [municipalSource, setMunicipalSource] = useState(null);
    const [loading, setLoading] = useState(true);
    const [title, setTitle] = useState('');
    const [selectedFile, setSelectedFile] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [importing, setImporting] = useState(false);
    const [importResult, setImportResult] = useState(null);
    const [municipalReference, setMunicipalReference] = useState('');
    const [referenceError, setReferenceError] = useState('');
    const [referenceResult, setReferenceResult] = useState(null);
    const [importingReference, setImportingReference] = useState(false);

    const [question, setQuestion] = useState('');
    const [asking, setAsking] = useState(false);
    const [conversation, setConversation] = useState([]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const response = await http.get(BASE);
            setDocuments(response.data.documents || []);
            setMunicipalDocuments(response.data.municipal_documents || []);
            setMunicipalSource(response.data.municipal_source || null);
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo cargar la base de conocimiento.'));
        } finally {
            setLoading(false);
        }
    }, [toast]);

    useEffect(() => { load(); }, [load]);

    const upload = async (event) => {
        event.preventDefault();
        const formElement = event.currentTarget;
        if (!selectedFile) return toast.error('Seleccione un archivo para indexar.');
        const form = new FormData();
        if (title.trim()) form.append('title', title.trim());
        form.append('reindex', '1');
        form.append('file', selectedFile);

        setUploading(true);
        try {
            const response = await http.post(`${BASE}/documents`, form, { headers: { 'Content-Type': 'multipart/form-data' }, timeout: 600000 });
            toast.success(response.data.message);
            setTitle('');
            setSelectedFile(null);
            formElement.reset();
            await load();
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo indexar el documento.'));
            await load();
        } finally {
            setUploading(false);
        }
    };

    const removeDocument = async (document) => {
        if (!window.confirm(`¿Eliminar ${document.file_name} y sus fragmentos indexados?`)) return;
        try {
            const response = await http.delete(`${BASE}/documents/${document.id}`);
            toast.success(response.data.message);
            await load();
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo eliminar el documento.'));
        }
    };

    const importNormativa = async () => {
        setImporting(true);
        setImportResult(null);
        try {
            const response = await http.post(`${BASE}/normativa/importar`, {}, { timeout: 600000 });
            setImportResult(response.data.result || null);
            toast.success(response.data.message);
            await load();
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo extraer la normativa municipal.'));
        } finally {
            setImporting(false);
        }
    };

    const importNormativaDocument = async (event) => {
        event.preventDefault();
        const validationError = validateMunicipalReference(municipalReference);
        setReferenceError(validationError);
        setReferenceResult(null);
        if (validationError) return;

        setImportingReference(true);
        try {
            const response = await http.post(`${BASE}/normativa/importar-documento`, {
                reference: municipalReference.trim(),
            }, { timeout: 600000 });
            setReferenceResult(response.data.result || null);
            setMunicipalReference('');
            toast.success(response.data.message);
            await load();
        } catch (error) {
            const message = errorMessage(error, 'No se pudo importar la copia verificable.');
            setReferenceError(message);
            toast.error(message);
            await load();
        } finally {
            setImportingReference(false);
        }
    };

    const ask = async (event) => {
        event.preventDefault();
        const text = question.trim();
        if (!text) return;
        setConversation((current) => [...current, { role: 'user', content: text }]);
        setQuestion('');
        setAsking(true);
        try {
            const response = await http.post(`${BASE}/preguntar`, { question: text }, { timeout: 90000 });
            setConversation((current) => [...current, {
                role: 'assistant',
                content: response.data.answer,
                sources: response.data.sources || [],
            }]);
        } catch (error) {
            const message = errorMessage(error, 'No se pudo generar una respuesta.');
            setConversation((current) => [...current, { role: 'error', content: message }]);
            toast.error(message);
        } finally {
            setAsking(false);
        }
    };

    const allDocuments = [...municipalDocuments, ...documents].sort((left, right) => {
        const dateOrder = (right.sort_date || '').localeCompare(left.sort_date || '');
        if (dateOrder !== 0) return dateOrder;

        return String(right.id).localeCompare(String(left.id), 'es', { numeric: true });
    });

    if (!can.view) return <div className="empty-state"><i className="fas fa-lock" /><p>No tiene acceso a este módulo.</p></div>;

    return (
        <div className="overflow-hidden rounded-2xl border-2 border-mist bg-white shadow-sm">
            <header className="px-4 py-5 sm:px-6">
                <h1 className="flex items-center gap-3 font-heading text-xl font-extrabold text-navy sm:text-2xl"><i className="fas fa-database text-blue" /> Base de Conocimiento IA</h1>
                <p className="mt-1 max-w-3xl text-sm text-earth">Suba documentos, incluidos PDF escaneados e imágenes, para indexarlos automáticamente y hacer preguntas en lenguaje natural sobre su contenido.</p>
            </header>

            <nav className="flex gap-1 overflow-x-auto border-y-2 border-mist px-4 pt-2 sm:px-6" role="tablist" aria-label="Secciones de base de conocimiento">
                {[['preguntar', 'Preguntar', 'fa-comments'], ['documentos', 'Documentos', 'fa-folder-open']].map(([key, label, icon]) => (
                    <button key={key} type="button" role="tab" aria-selected={tab === key} onClick={() => setTab(key)} className={`flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-bold ${tab === key ? 'border-blue text-blue' : 'border-transparent text-earth hover:text-navy'}`}><i className={`fas ${icon}`} aria-hidden="true" /> {label}</button>
                ))}
            </nav>

            {tab === 'preguntar' && (
                <div className="flex flex-col">
                    <div className="max-h-[520px] min-h-[280px] overflow-y-auto px-4 py-5 sm:px-6">
                        {conversation.length === 0 && (
                            <div className="empty-state py-10"><i className="fas fa-comment-dots" /><p>Haga una pregunta sobre los documentos indexados.</p></div>
                        )}
                        <div className="space-y-4" aria-live="polite">
                            {conversation.map((entry, index) => (
                                <div key={index} className={`flex ${entry.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                                    <div className={`min-w-0 rounded-2xl px-4 py-3 text-sm ${
                                        entry.role === 'user'
                                            ? 'max-w-2xl bg-blue text-white'
                                            : entry.role === 'error'
                                                ? 'max-w-2xl bg-coral-light text-coral'
                                                : 'w-full max-w-3xl border border-mist bg-base text-charcoal'
                                    }`}>
                                        {entry.role === 'assistant'
                                            ? <MarkdownContent content={entry.content} />
                                            : <p className="whitespace-pre-wrap break-words">{entry.content}</p>}
                                        {entry.sources && entry.sources.length > 0 && (
                                            <section className="mt-4 border-t border-mist/80 pt-3" aria-label="Fuentes utilizadas">
                                                <h3 className="mb-2 text-xs font-bold uppercase tracking-wide text-slate">Fuentes utilizadas</h3>
                                                <ul className="space-y-2">
                                                    {entry.sources.map((source, sourceIndex) => (
                                                        <li key={sourceIndex} className="flex min-w-0 items-start gap-2 text-xs text-earth">
                                                            <i className="fas fa-file-lines mt-0.5 shrink-0 text-blue" aria-hidden="true" />
                                                            <span className="min-w-0 break-words">
                                                                {source.download_url ? (
                                                                    <a href={source.download_url} target="_blank" rel="noreferrer" className="font-semibold text-blue underline decoration-blue/30 underline-offset-2 hover:decoration-blue">
                                                                        {source.archivo}
                                                                    </a>
                                                                ) : <span className="font-semibold text-charcoal">{source.archivo}</span>}
                                                                <span className="ml-1 whitespace-nowrap text-slate">
                                                                    {source.pagina ? `· Pág. ${source.pagina} ` : ''}· Frag. {source.chunk}
                                                                </span>
                                                            </span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </section>
                                        )}
                                    </div>
                                </div>
                            ))}
                            {asking && (
                                <div className="flex justify-start">
                                    <div className="rounded-2xl border border-mist bg-base px-4 py-3 text-sm text-earth">
                                        <i className="fas fa-spinner fa-spin mr-2" /> Buscando en los documentos…
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                    <form onSubmit={ask} className="flex items-end gap-3 border-t border-mist px-4 py-4 sm:px-6">
                        <div className="flex-1">
                            <label className={labelClass} htmlFor="kb-question">Pregunta</label>
                            <input
                                id="kb-question"
                                value={question}
                                onChange={(event) => setQuestion(event.target.value)}
                                maxLength={1000}
                                className={inputClass}
                                placeholder="Ej. ¿Qué dice el documento sobre el proceso de distribución?"
                            />
                        </div>
                        <button type="submit" disabled={asking || !question.trim()} className="btn-primary inline-flex min-h-11 items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <i className={`fas ${asking ? 'fa-spinner fa-spin' : 'fa-paper-plane'}`} /> Preguntar
                        </button>
                    </form>
                </div>
            )}

            {tab === 'documentos' && (
                <div>
                    <section className="border-b border-mist bg-base/60 p-4 sm:p-6" aria-labelledby="municipal-import-title">
                        <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div className="min-w-0 max-w-3xl">
                                <h2 id="municipal-import-title" className="flex items-center gap-2 font-heading text-lg font-extrabold text-navy">
                                    <i className="fas fa-building-columns text-blue" aria-hidden="true" /> Normativa municipal para PROVALE
                                </h2>
                                <p className="mt-1 text-sm text-earth">Revisa el repositorio oficial, detecta normas relacionadas con el Programa del Vaso de Leche, guarda cada PDF e indexa su contenido sin duplicarlo.</p>
                                <p className="mt-2 text-xs text-slate">La búsqueda incluye Vaso de Leche, PVL, Club de Madres, comités y productos como hojuela de quinua y avena fortificada con vitaminas y minerales.</p>
                                <div className="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                    <span className="badge bg-blue-light text-blue">{municipalSource?.indexed_count || 0} normas indexadas</span>
                                    <a href={municipalSource?.url || 'https://www.muniesperanza.gob.pe/website/mde2026/normativa.php'} target="_blank" rel="noreferrer" className="font-bold text-blue underline decoration-blue/30 underline-offset-2 hover:decoration-blue">
                                        Ver repositorio oficial <i className="fas fa-arrow-up-right-from-square ml-1" aria-hidden="true" />
                                    </a>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={importNormativa}
                                disabled={!can.create || importing || importingReference || uploading}
                                className="btn-primary inline-flex min-h-11 shrink-0 items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <i className={`fas ${importing ? 'fa-spinner fa-spin' : 'fa-file-arrow-down'}`} aria-hidden="true" />
                                {importing ? 'Revisando e indexando…' : 'Extraer normativa PROVALE'}
                            </button>
                        </div>
                        <form onSubmit={importNormativaDocument} className="mt-4 border-t border-mist pt-4" noValidate>
                            <label className={labelClass} htmlFor="kb-municipal-reference">Copia verificable o número de resolución</label>
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-start">
                                <div className="min-w-0 flex-1">
                                    <input
                                        id="kb-municipal-reference"
                                        value={municipalReference}
                                        onChange={(event) => {
                                            setMunicipalReference(event.target.value);
                                            if (referenceError) setReferenceError('');
                                        }}
                                        onBlur={() => setReferenceError(validateMunicipalReference(municipalReference))}
                                        maxLength={500}
                                        className={`${inputClass} ${referenceError ? 'border-coral focus:border-coral focus:ring-coral/15' : ''}`}
                                        placeholder="0750-2026-MDE o https://…/norma_descargar.php?id=35639"
                                        aria-invalid={referenceError ? 'true' : 'false'}
                                        aria-describedby="kb-municipal-reference-help kb-municipal-reference-error"
                                    />
                                    <p id="kb-municipal-reference-help" className="mt-1 text-xs text-earth">Buscaremos el número en el portal o descargaremos el PDF desde su enlace oficial.</p>
                                    <p id="kb-municipal-reference-error" className="mt-1 min-h-4 text-xs font-semibold text-coral" role={referenceError ? 'alert' : undefined}>{referenceError}</p>
                                </div>
                                <button
                                    type="submit"
                                    disabled={!can.create || importing || importingReference || uploading || !municipalReference.trim()}
                                    className="btn-primary inline-flex min-h-11 shrink-0 items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <i className={`fas ${importingReference ? 'fa-spinner fa-spin' : 'fa-link'}`} aria-hidden="true" />
                                    {importingReference ? 'Importando…' : 'Importar documento'}
                                </button>
                            </div>
                        </form>
                        <div className="mt-3 min-h-5 text-sm" aria-live="polite" role="status">
                            {importing && <p className="text-earth">La revisión puede tardar varios minutos. No cierre esta pantalla.</p>}
                            {importingReference && <p className="text-earth">Validando la copia verificable y preparando su índice…</p>}
                            {referenceResult && (
                                <p className="text-teal">
                                    <strong>{referenceResult.numero || referenceResult.titulo}</strong> {referenceResult.ya_importado ? 'ya estaba disponible.' : 'se incorporó a la base de conocimiento.'}
                                </p>
                            )}
                            {importResult && (
                                <p className={importResult.errores > 0 ? 'text-clay' : 'text-teal'}>
                                    Se revisaron <strong>{importResult.revisados}</strong> registros: <strong>{importResult.importados}</strong> nuevos, <strong>{importResult.ya_importados}</strong> ya existentes y <strong>{importResult.descartados}</strong> descartados por no ser relevantes{importResult.errores > 0 ? `; ${importResult.errores} no pudieron procesarse.` : '.'}
                                </p>
                            )}
                        </div>
                    </section>

                    <div className="grid lg:grid-cols-[minmax(290px,.8fr)_minmax(0,1.7fr)]">
                        <form onSubmit={upload} className="space-y-4 border-b border-mist p-4 sm:p-6 lg:border-b-0 lg:border-r">
                            <div><h2 className="font-heading text-lg font-extrabold text-navy">Subir otro documento</h2><p className="mt-1 text-sm text-earth">El archivo se procesa y fragmenta automáticamente para responder preguntas sobre su contenido.</p></div>
                            <div><label className={labelClass} htmlFor="kb-title">Título (opcional)</label><input id="kb-title" value={title} onChange={(event) => setTitle(event.target.value)} maxLength={255} className={inputClass} placeholder="Ej. Reglamento interno 2026" /></div>
                            <div><label className={labelClass} htmlFor="kb-file">Archivo</label><input id="kb-file" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx,.xls,.xlsx" onChange={(event) => setSelectedFile(event.target.files?.[0] || null)} className={`${inputClass} file:mr-3 file:border-0 file:bg-blue-light file:px-3 file:py-1 file:font-bold file:text-blue`} /></div>
                            <p className="text-xs text-earth">Formatos admitidos: PDF (también escaneado), JPG, PNG, Word (.docx) y Excel (.xls, .xlsx).</p>
                            <button type="submit" disabled={!can.create || uploading || importing || importingReference} className="btn-primary inline-flex w-full items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-50"><i className={`fas ${uploading ? 'fa-spinner fa-spin' : 'fa-cloud-arrow-up'}`} aria-hidden="true" /> {uploading ? 'Indexando…' : 'Guardar e indexar'}</button>
                        </form>
                        <div className="min-w-0 p-4 sm:p-6">
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <h2 className="font-heading text-lg font-extrabold text-navy">Documentos disponibles</h2>
                                {!loading && <span className="text-sm font-semibold tabular-nums text-slate">{allDocuments.length} en esta vista</span>}
                            </div>
                            {loading ? (
                                <div className="flex items-center justify-center gap-2 py-16 text-earth"><i className="fas fa-spinner fa-spin" aria-hidden="true" /> Cargando documentos…</div>
                            ) : (
                                <div className="mt-4 overflow-x-auto">
                                    <table className="data-table min-w-[860px] w-full text-sm"><thead><tr><th className="px-3 py-3 text-left">Documento</th><th className="px-3 py-3 text-left">Origen</th><th className="px-3 py-3 text-left">Tamaño</th><th className="px-3 py-3 text-left">Indexación</th><th className="px-3 py-3 text-left">Fecha <span className="sr-only">(más reciente primero)</span></th><th className="px-3 py-3 text-right">Acciones</th></tr></thead><tbody>
                                        {allDocuments.length ? allDocuments.map((document) => (
                                            <tr key={`${document.origin}-${document.id}`}>
                                                <td className="max-w-[310px] px-3 py-3"><strong className="block break-words text-charcoal">{document.title}</strong><span className="block break-words text-xs text-slate">{document.file_name}</span>{document.summary && <p className="mt-1 line-clamp-2 text-xs text-earth">{document.summary}</p>}</td>
                                                <td className="px-3 py-3">
                                                    <span className={`badge ${document.origin === 'municipal' ? 'bg-blue-light text-blue' : 'bg-slate-100 text-slate'}`}>{document.origin === 'municipal' ? 'Portal municipal' : 'Carga manual'}</span>
                                                    {document.origin === 'municipal' && (
                                                        <span className={`mt-1 block text-xs font-semibold ${document.stored ? 'text-teal' : 'text-clay'}`}>
                                                            {document.stored ? 'PDF guardado' : 'PDF pendiente'}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 tabular-nums">{document.file_size ? `${Math.ceil(document.file_size / 1024)} KB` : '—'}</td>
                                                <td className="px-3 py-3"><StatusBadge status={document.index_status} />{document.index_error && <p className="mt-1 max-w-[220px] break-words text-xs text-coral">{document.index_error}</p>}</td>
                                                <td className="px-3 py-3 whitespace-nowrap text-earth">{document.created_at || '—'}</td>
                                                <td className="px-3 py-3 text-right">
                                                    <div className="inline-flex gap-2">
                                                        {document.preview_url && <a href={document.preview_url} target="_blank" rel="noreferrer" className="btn-action bg-blue-light text-blue" title="Ver PDF guardado" aria-label={`Ver ${document.file_name}`}><i className="fas fa-eye" aria-hidden="true" /></a>}
                                                        {document.origin === 'municipal' && document.download_url && <a href={document.download_url} className="btn-action bg-teal-light text-teal" title="Descargar PDF guardado" aria-label={`Descargar ${document.file_name}`}><i className="fas fa-download" aria-hidden="true" /></a>}
                                                        {document.origin === 'municipal' && document.official_url && <a href={document.official_url} target="_blank" rel="noreferrer" className="btn-action bg-slate-100 text-slate hover:bg-blue-light hover:text-blue" title="Abrir copia verificable oficial" aria-label={`Abrir copia verificable de ${document.file_name}`}><i className="fas fa-arrow-up-right-from-square" aria-hidden="true" /></a>}
                                                        {document.origin === 'upload' && can.del && <button type="button" onClick={() => removeDocument(document)} className="btn-action bg-clay-light text-clay" title="Eliminar documento" aria-label={`Eliminar ${document.file_name}`}><i className="fas fa-trash" aria-hidden="true" /></button>}
                                                    </div>
                                                </td>
                                            </tr>
                                        )) : <tr><td colSpan="6"><div className="empty-state py-10"><i className="fas fa-folder-open" /><p>No hay documentos indexados. Extraiga la normativa municipal o suba un archivo.</p></div></td></tr>}
                                    </tbody></table>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
