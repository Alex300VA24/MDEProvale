import { Fragment, useCallback, useEffect, useMemo, useState } from 'react';
import http from '../../http';
import errorMessage from '../../errorMessage';
import MarkdownContent from '../../Components/MarkdownContent';
import { useToast } from '../../Components/Toast';

const BASE = '/api/dashboard/base-conocimiento';

// Etiquetas legibles para las claves técnicas más comunes del historial.
const DETAIL_LABELS = {
    urgencia: 'Urgencia',
    cambio: 'Qué cambió',
    texto: 'Descripción',
    anterior: 'Valor anterior',
    actual: 'Valor actual',
    accion_sugerida: 'Acción sugerida',
    dias_restantes: 'Días restantes',
    total: 'Total de beneficiarios',
    ya_superaron: 'Ya superaron el límite',
    numero: 'Número',
    tipo_documento: 'Tipo de documento',
    fecha_documento: 'Fecha del documento',
    motivo: 'Motivo de relevancia',
    pdf_url: 'Enlace al PDF',
    resultado: 'Resultado',
    normas: 'Normas nuevas',
    avisos: 'Avisos generados',
};

const humanizeKey = (key) => DETAIL_LABELS[key] || String(key).replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());

function DetailValue({ value }) {
    if (value === null || value === undefined || value === '') return <span className="text-slate">—</span>;
    if (typeof value === 'boolean') return <span>{value ? 'Sí' : 'No'}</span>;
    if (typeof value === 'string' && /^https?:\/\//.test(value)) {
        return <a href={value} target="_blank" rel="noreferrer" className="break-all font-semibold text-blue underline">{value}</a>;
    }
    if (Array.isArray(value)) {
        if (value.length === 0) return <span className="text-slate">—</span>;
        return (
            <ul className="space-y-1">
                {value.map((item, i) => (
                    <li key={i} className="flex gap-2"><i className="fas fa-angle-right mt-1 text-xs text-slate" aria-hidden="true" /><div className="min-w-0"><DetailValue value={item} /></div></li>
                ))}
            </ul>
        );
    }
    if (typeof value === 'object') return <DetailsView data={value} nested />;
    return <span className="break-words">{String(value)}</span>;
}

// Presenta el detalle técnico como ficha clave/valor en lugar de JSON crudo.
function DetailsView({ data, nested = false }) {
    const entries = Object.entries(data || {});
    return (
        <dl className={`grid grid-cols-1 gap-x-4 gap-y-2 text-xs sm:grid-cols-[minmax(0,11rem)_1fr] ${nested ? 'mt-1' : 'mt-2 rounded-lg border border-mist bg-fog p-3'}`}>
            {entries.map(([key, value]) => (
                <Fragment key={key}>
                    <dt className="font-bold text-slate"><i className="fas fa-circle-dot mr-1.5 text-xs text-blue/60" aria-hidden="true" />{humanizeKey(key)}</dt>
                    <dd className="min-w-0 text-charcoal"><DetailValue value={value} /></dd>
                </Fragment>
            ))}
        </dl>
    );
}

const labelClass = 'block mb-1 text-xs font-bold uppercase tracking-wider text-slate';
const inputClass = 'w-full border-2 border-mist bg-white px-3 py-2.5 text-sm font-semibold text-charcoal focus:border-blue focus:outline-none focus:ring-2 focus:ring-blue/15';

const SOURCE_LABELS = {
    portal: { label: 'Portal municipal', icon: 'fa-building-columns', cls: 'bg-blue-light text-blue' },
    beneficiarios: { label: 'Beneficiarios', icon: 'fa-user-clock', cls: 'bg-violet-100 text-violet-700' },
    resoluciones: { label: 'Resoluciones', icon: 'fa-file-signature', cls: 'bg-orange-100 text-orange-700' },
    sistema: { label: 'Sistema', icon: 'fa-gear', cls: 'bg-slate-100 text-slate' },
};

const PRIORITY_CLS = { alta: 'bg-coral-light text-coral', media: 'bg-sun-light text-earth', baja: 'bg-teal-light text-teal' };

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

function formatDateTime(value) {
    if (!value) return '—';
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? value : d.toLocaleString('es-PE', { dateStyle: 'medium', timeStyle: 'short' });
}

function Accordion({ title, icon, open, onToggle, children, hint }) {
    return (
        <section className="rounded-xl border-2 border-mist bg-white">
            <button type="button" onClick={onToggle} aria-expanded={open} className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
                <span className="flex min-w-0 items-center gap-2 font-heading text-sm font-extrabold text-navy">
                    <i className={`fas ${icon} text-blue`} aria-hidden="true" /> {title}
                    {hint && <span className="hidden text-xs font-semibold text-slate sm:inline">· {hint}</span>}
                </span>
                <i className={`fas fa-chevron-down text-xs text-slate transition-transform ${open ? 'rotate-180' : ''}`} aria-hidden="true" />
            </button>
            {open && <div className="border-t-2 border-mist p-4">{children}</div>}
        </section>
    );
}

function EmptyState({ icon, text }) {
    return <div className="empty-state py-10"><i className={`fas ${icon}`} /><p>{text}</p></div>;
}

/* ------------------------------ Documentos ------------------------------ */

function DocumentsPanel() {
    const toast = useToast();
    const [documents, setDocuments] = useState([]);
    const [municipalDocuments, setMunicipalDocuments] = useState([]);
    const [municipalSource, setMunicipalSource] = useState(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [origin, setOrigin] = useState('all');
    const [expanded, setExpanded] = useState(null);
    const [addOpen, setAddOpen] = useState(null); // 'portal' | 'referencia' | 'archivo' | null

    const [title, setTitle] = useState('');
    const [selectedFile, setSelectedFile] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [importing, setImporting] = useState(false);
    const [importResult, setImportResult] = useState(null);
    const [municipalReference, setMunicipalReference] = useState('');
    const [referenceError, setReferenceError] = useState('');
    const [importingReference, setImportingReference] = useState(false);
    const busy = uploading || importing || importingReference;

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
            setAddOpen(null);
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
        if (validationError) return;

        setImportingReference(true);
        try {
            const response = await http.post(`${BASE}/normativa/importar-documento`, { reference: municipalReference.trim() }, { timeout: 600000 });
            setMunicipalReference('');
            toast.success(response.data.message);
            setAddOpen(null);
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

    const allDocuments = useMemo(() => [...municipalDocuments, ...documents].sort((left, right) => {
        const dateOrder = (right.sort_date || '').localeCompare(left.sort_date || '');
        if (dateOrder !== 0) return dateOrder;
        return String(right.id).localeCompare(String(left.id), 'es', { numeric: true });
    }), [municipalDocuments, documents]);

    const visible = useMemo(() => {
        const q = search.trim().toLowerCase();
        return allDocuments.filter((d) => (origin === 'all' || d.origin === origin)
            && (!q || `${d.title} ${d.file_name}`.toLowerCase().includes(q)));
    }, [allDocuments, search, origin]);

    const errors = allDocuments.filter((d) => d.index_status === 'ERROR').length;
    const toggle = (key) => setAddOpen((current) => (current === key ? null : key));

    return (
        <div className="space-y-5">
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                {[
                    ['Total', allDocuments.length, 'text-navy'],
                    ['Portal municipal', municipalSource?.indexed_count || municipalDocuments.length, 'text-blue'],
                    ['Cargas manuales', documents.length, 'text-slate'],
                    ['Con error', errors, errors ? 'text-coral' : 'text-teal'],
                ].map(([label, value, cls]) => (
                    <div key={label} className="rounded-xl border-2 border-mist bg-fog px-4 py-3">
                        <div className={`text-2xl font-extrabold tabular-nums ${cls}`}>{value}</div>
                        <div className="text-xs font-bold uppercase tracking-wide text-slate">{label}</div>
                    </div>
                ))}
            </div>

            <div className="space-y-2">
                <p className="text-xs font-bold uppercase tracking-wider text-slate">Agregar conocimiento</p>
                <Accordion title="Extraer normativa del portal" icon="fa-building-columns" open={addOpen === 'portal'} onToggle={() => toggle('portal')} hint="revisión automática de PROVALE">
                    <p className="text-sm text-earth">Revisa el repositorio oficial, detecta normas relacionadas con el Programa del Vaso de Leche, guarda cada PDF e indexa su contenido sin duplicarlo.</p>
                    <div className="mt-3 flex flex-wrap items-center gap-3">
                        <button type="button" onClick={importNormativa} disabled={busy} className="btn-primary inline-flex min-h-11 items-center gap-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <i className={`fas ${importing ? 'fa-spinner fa-spin' : 'fa-file-arrow-down'}`} aria-hidden="true" /> {importing ? 'Revisando e indexando…' : 'Extraer normativa PROVALE'}
                        </button>
                        <a href={municipalSource?.url || 'https://www.muniesperanza.gob.pe/website/mde2026/normativa.php'} target="_blank" rel="noreferrer" className="text-xs font-bold text-blue underline decoration-blue/30 underline-offset-2">
                            Ver repositorio oficial <i className="fas fa-arrow-up-right-from-square ml-1" aria-hidden="true" />
                        </a>
                    </div>
                    <div className="mt-3 min-h-5 text-sm" aria-live="polite" role="status">
                        {importing && <p className="text-earth">Puede tardar varios minutos. No cierre esta pantalla.</p>}
                        {importResult && (
                            <p className={importResult.errores > 0 ? 'text-clay' : 'text-teal'}>
                                Revisados <strong>{importResult.revisados}</strong>: <strong>{importResult.importados}</strong> nuevos, <strong>{importResult.ya_importados}</strong> existentes, <strong>{importResult.descartados}</strong> no relevantes{importResult.errores > 0 ? `; ${importResult.errores} con error.` : '.'}
                            </p>
                        )}
                    </div>
                </Accordion>

                <Accordion title="Importar por número o enlace" icon="fa-link" open={addOpen === 'referencia'} onToggle={() => toggle('referencia')}>
                    <form onSubmit={importNormativaDocument} noValidate>
                        <label className={labelClass} htmlFor="kb-municipal-reference">Copia verificable o número de resolución</label>
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-start">
                            <div className="min-w-0 flex-1">
                                <input
                                    id="kb-municipal-reference"
                                    value={municipalReference}
                                    onChange={(event) => { setMunicipalReference(event.target.value); if (referenceError) setReferenceError(''); }}
                                    onBlur={() => municipalReference && setReferenceError(validateMunicipalReference(municipalReference))}
                                    maxLength={500}
                                    className={`${inputClass} ${referenceError ? 'border-coral focus:border-coral focus:ring-coral/15' : ''}`}
                                    placeholder="0750-2026-MDE o https://…/norma_descargar.php?id=35639"
                                    aria-invalid={referenceError ? 'true' : 'false'}
                                />
                                <p className="mt-1 min-h-4 text-xs font-semibold text-coral" role={referenceError ? 'alert' : undefined}>{referenceError}</p>
                            </div>
                            <button type="submit" disabled={busy || !municipalReference.trim()} className="btn-primary inline-flex min-h-11 items-center gap-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <i className={`fas ${importingReference ? 'fa-spinner fa-spin' : 'fa-link'}`} aria-hidden="true" /> {importingReference ? 'Importando…' : 'Importar'}
                            </button>
                        </div>
                    </form>
                </Accordion>

                <Accordion title="Subir archivo" icon="fa-cloud-arrow-up" open={addOpen === 'archivo'} onToggle={() => toggle('archivo')} hint="PDF, imagen, Word o Excel">
                    <form onSubmit={upload} className="grid gap-3 sm:grid-cols-2">
                        <div><label className={labelClass} htmlFor="kb-title">Título (opcional)</label><input id="kb-title" value={title} onChange={(event) => setTitle(event.target.value)} maxLength={255} className={inputClass} placeholder="Ej. Reglamento interno 2026" /></div>
                        <div><label className={labelClass} htmlFor="kb-file">Archivo</label><input id="kb-file" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx,.xls,.xlsx" onChange={(event) => setSelectedFile(event.target.files?.[0] || null)} className={`${inputClass} file:mr-3 file:border-0 file:bg-blue-light file:px-3 file:py-1 file:font-bold file:text-blue`} /></div>
                        <div className="sm:col-span-2">
                            <button type="submit" disabled={busy} className="btn-primary inline-flex items-center gap-2 disabled:cursor-not-allowed disabled:opacity-50"><i className={`fas ${uploading ? 'fa-spinner fa-spin' : 'fa-cloud-arrow-up'}`} aria-hidden="true" /> {uploading ? 'Indexando…' : 'Guardar e indexar'}</button>
                        </div>
                    </form>
                </Accordion>
            </div>

            <div>
                <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 className="font-heading text-lg font-extrabold text-navy">Documentos <span className="text-sm font-semibold tabular-nums text-slate">({visible.length})</span></h3>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <input type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Buscar documento…" aria-label="Buscar documento" className={`${inputClass} sm:w-64`} />
                        <select value={origin} onChange={(e) => setOrigin(e.target.value)} aria-label="Filtrar por origen" className={`${inputClass} sm:w-44`}>
                            <option value="all">Todos los orígenes</option>
                            <option value="municipal">Portal municipal</option>
                            <option value="upload">Carga manual</option>
                        </select>
                    </div>
                </div>

                {loading ? (
                    <div className="flex items-center justify-center gap-2 py-16 text-earth"><i className="fas fa-spinner fa-spin" aria-hidden="true" /> Cargando documentos…</div>
                ) : visible.length === 0 ? (
                    <EmptyState icon="fa-folder-open" text={allDocuments.length ? 'Ningún documento coincide con el filtro.' : 'Aún no hay documentos. Extraiga la normativa municipal o suba un archivo.'} />
                ) : (
                    <ul className="divide-y-2 divide-mist overflow-hidden rounded-xl border-2 border-mist">
                        {visible.map((document) => {
                            const key = `${document.origin}-${document.id}`;
                            const isOpen = expanded === key;
                            return (
                                <li key={key} className="bg-white">
                                    <div className="flex items-center gap-3 px-4 py-3">
                                        <button type="button" onClick={() => setExpanded(isOpen ? null : key)} aria-expanded={isOpen} aria-label={`Detalles de ${document.title}`} className="flex min-w-0 flex-1 items-center gap-3 text-left">
                                            <i className={`fas fa-chevron-right text-xs text-slate transition-transform ${isOpen ? 'rotate-90' : ''}`} aria-hidden="true" />
                                            <span className="min-w-0">
                                                <strong className="block truncate text-charcoal">{document.title}</strong>
                                                <span className="block text-xs text-slate">{document.origin === 'municipal' ? 'Portal municipal' : 'Carga manual'} · {document.created_at || '—'}</span>
                                            </span>
                                        </button>
                                        <StatusBadge status={document.index_status} />
                                        <div className="inline-flex gap-2">
                                            {document.preview_url && <a href={document.preview_url} target="_blank" rel="noreferrer" className="btn-action bg-blue-light text-blue" title="Ver PDF" aria-label={`Ver ${document.file_name}`}><i className="fas fa-eye" aria-hidden="true" /></a>}
                                            {document.origin === 'upload' && <button type="button" onClick={() => removeDocument(document)} className="btn-action bg-clay-light text-clay" title="Eliminar documento" aria-label={`Eliminar ${document.file_name}`}><i className="fas fa-trash" aria-hidden="true" /></button>}
                                        </div>
                                    </div>
                                    {isOpen && (
                                        <dl className="grid gap-x-6 gap-y-2 border-t border-mist bg-fog px-4 py-3 pl-10 text-xs sm:grid-cols-2">
                                            <div><dt className="font-bold uppercase tracking-wide text-slate">Archivo</dt><dd className="break-words text-charcoal">{document.file_name}</dd></div>
                                            <div><dt className="font-bold uppercase tracking-wide text-slate">Tamaño</dt><dd className="text-charcoal">{document.file_size ? `${Math.ceil(document.file_size / 1024)} KB` : '—'}</dd></div>
                                            {document.summary && <div className="sm:col-span-2"><dt className="font-bold uppercase tracking-wide text-slate">Resumen</dt><dd className="text-earth">{document.summary}</dd></div>}
                                            {document.index_error && <div className="sm:col-span-2"><dt className="font-bold uppercase tracking-wide text-coral">Error de indexación</dt><dd className="break-words text-coral">{document.index_error}</dd></div>}
                                            {document.origin === 'municipal' && (
                                                <div className="flex flex-wrap gap-3 sm:col-span-2">
                                                    <span className={`font-semibold ${document.stored ? 'text-teal' : 'text-clay'}`}>{document.stored ? 'PDF guardado' : 'PDF pendiente'}</span>
                                                    {document.download_url && <a href={document.download_url} className="font-bold text-blue underline">Descargar PDF</a>}
                                                    {document.official_url && <a href={document.official_url} target="_blank" rel="noreferrer" className="font-bold text-blue underline">Copia verificable oficial</a>}
                                                </div>
                                            )}
                                        </dl>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </div>
    );
}

/* ------------------------------- Preguntar ------------------------------- */

function AskPanel() {
    const toast = useToast();
    const [question, setQuestion] = useState('');
    const [asking, setAsking] = useState(false);
    const [conversation, setConversation] = useState([]);

    const ask = async (event) => {
        event.preventDefault();
        const text = question.trim();
        if (!text) return;
        setConversation((current) => [...current, { role: 'user', content: text }]);
        setQuestion('');
        setAsking(true);
        try {
            const response = await http.post(`${BASE}/preguntar`, { question: text }, { timeout: 90000 });
            setConversation((current) => [...current, { role: 'assistant', content: response.data.answer, sources: response.data.sources || [] }]);
        } catch (error) {
            const message = errorMessage(error, 'No se pudo generar una respuesta.');
            setConversation((current) => [...current, { role: 'error', content: message }]);
            toast.error(message);
        } finally {
            setAsking(false);
        }
    };

    return (
        <div className="flex flex-col">
            <div className="max-h-[520px] min-h-[260px] overflow-y-auto py-2">
                {conversation.length === 0 && <EmptyState icon="fa-comment-dots" text="Haga una pregunta sobre los documentos indexados." />}
                <div className="space-y-4" aria-live="polite">
                    {conversation.map((entry, index) => (
                        <div key={index} className={`flex ${entry.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                            <div className={`min-w-0 rounded-2xl px-4 py-3 text-sm ${entry.role === 'user' ? 'max-w-2xl bg-blue text-white' : entry.role === 'error' ? 'max-w-2xl bg-coral-light text-coral' : 'w-full max-w-3xl border border-mist bg-fog text-charcoal'}`}>
                                {entry.role === 'assistant' ? <MarkdownContent content={entry.content} /> : <p className="whitespace-pre-wrap break-words">{entry.content}</p>}
                                {entry.sources && entry.sources.length > 0 && (
                                    <details className="mt-3 border-t border-mist/80 pt-2">
                                        <summary className="cursor-pointer text-xs font-bold uppercase tracking-wide text-slate">Fuentes utilizadas ({entry.sources.length})</summary>
                                        <ul className="mt-2 space-y-2">
                                            {entry.sources.map((source, sourceIndex) => (
                                                <li key={sourceIndex} className="flex min-w-0 items-start gap-2 text-xs text-earth">
                                                    <i className="fas fa-file-lines mt-0.5 shrink-0 text-blue" aria-hidden="true" />
                                                    <span className="min-w-0 break-words">
                                                        {source.download_url ? <a href={source.download_url} target="_blank" rel="noreferrer" className="font-semibold text-blue underline">{source.archivo}</a> : <span className="font-semibold text-charcoal">{source.archivo}</span>}
                                                        <span className="ml-1 text-slate">{source.pagina ? `· Pág. ${source.pagina} ` : ''}· Frag. {source.chunk}</span>
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </details>
                                )}
                            </div>
                        </div>
                    ))}
                    {asking && <div className="flex justify-start"><div className="rounded-2xl border border-mist bg-fog px-4 py-3 text-sm text-earth"><i className="fas fa-spinner fa-spin mr-2" /> Buscando en los documentos…</div></div>}
                </div>
            </div>
            <form onSubmit={ask} className="mt-3 flex items-end gap-3 border-t border-mist pt-4">
                <div className="flex-1">
                    <label className={labelClass} htmlFor="kb-question">Pregunta</label>
                    <input id="kb-question" value={question} onChange={(event) => setQuestion(event.target.value)} maxLength={1000} className={inputClass} placeholder="Ej. ¿Qué dice el documento sobre el proceso de distribución?" />
                </div>
                <button type="submit" disabled={asking || !question.trim()} className="btn-primary inline-flex min-h-11 items-center gap-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <i className={`fas ${asking ? 'fa-spinner fa-spin' : 'fa-paper-plane'}`} /> Preguntar
                </button>
            </form>
        </div>
    );
}

/* ------------------------------- Historial ------------------------------- */

function HistoryPanel() {
    const toast = useToast();
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [source, setSource] = useState('');
    const [search, setSearch] = useState('');
    const [query, setQuery] = useState('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [expanded, setExpanded] = useState(null);

    useEffect(() => {
        const t = setTimeout(() => { setQuery(search.trim()); setPage(1); }, 350);
        return () => clearTimeout(t);
    }, [search]);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);
        http.get(`${BASE}/historial`, { params: { source: source || undefined, q: query || undefined, page } })
            .then((res) => { if (!cancelled) { setRows(res.data.data || []); setMeta(res.data.meta || meta); } })
            .catch((error) => toast.error(errorMessage(error, 'No se pudo cargar el historial.')))
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [source, query, page]);

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm text-earth">Cada cambio detectado en el sistema o extraído del portal queda registrado aquí.</p>
                <div className="flex flex-col gap-2 sm:flex-row">
                    <input type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Buscar…" aria-label="Buscar en historial" className={`${inputClass} sm:w-56`} />
                    <select value={source} onChange={(e) => { setSource(e.target.value); setPage(1); }} aria-label="Filtrar por origen" className={`${inputClass} sm:w-48`}>
                        <option value="">Todos los orígenes</option>
                        {Object.entries(SOURCE_LABELS).map(([key, s]) => <option key={key} value={key}>{s.label}</option>)}
                    </select>
                </div>
            </div>

            {loading ? (
                <div className="flex items-center justify-center gap-2 py-16 text-earth"><i className="fas fa-spinner fa-spin" aria-hidden="true" /> Cargando historial…</div>
            ) : rows.length === 0 ? (
                <EmptyState icon="fa-clock-rotate-left" text="Todavía no hay cambios registrados." />
            ) : (
                <ul className="divide-y-2 divide-mist overflow-hidden rounded-xl border-2 border-mist">
                    {rows.map((row) => {
                        const src = SOURCE_LABELS[row.source] || SOURCE_LABELS.sistema;
                        const isOpen = expanded === row.id;
                        const hasDetails = row.details && Object.keys(row.details).length > 0;
                        return (
                            <li key={row.id} className="bg-white px-4 py-3">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className={`badge ${src.cls}`}><i className={`fas ${src.icon} mr-1`} aria-hidden="true" />{src.label}</span>
                                    {row.priority && <span className={`badge ${PRIORITY_CLS[row.priority] || ''}`}>Prioridad {row.priority}</span>}
                                    <span className="ml-auto text-xs text-slate">{formatDateTime(row.occurred_at)}</span>
                                </div>
                                <p className="mt-1 font-bold text-charcoal">{row.title}</p>
                                {row.summary && <p className="mt-0.5 text-sm text-earth">{row.summary}</p>}
                                {hasDetails && (
                                    <>
                                        <button type="button" onClick={() => setExpanded(isOpen ? null : row.id)} aria-expanded={isOpen} className="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-blue hover:underline">
                                            <i className="fas fa-sliders" aria-hidden="true" />
                                            {isOpen ? 'Ocultar detalle técnico' : 'Ver detalle técnico'}
                                            <i className={`fas fa-chevron-${isOpen ? 'up' : 'down'} text-xs`} aria-hidden="true" />
                                        </button>
                                        {isOpen && <DetailsView data={row.details} />}
                                    </>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}

            {meta.last_page > 1 && (
                <div className="flex items-center justify-between text-sm">
                    <span className="text-slate">{meta.total} registros</span>
                    <div className="flex items-center gap-2">
                        <button type="button" disabled={page <= 1} onClick={() => setPage((p) => p - 1)} className="btn-secondary disabled:opacity-50">Anterior</button>
                        <span className="font-semibold tabular-nums text-slate">{meta.current_page} / {meta.last_page}</span>
                        <button type="button" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)} className="btn-secondary disabled:opacity-50">Siguiente</button>
                    </div>
                </div>
            )}
        </div>
    );
}

/* --------------------------------- Raíz --------------------------------- */

const VIEWS = [
    ['documentos', 'Documentos', 'fa-folder-open'],
    ['preguntar', 'Preguntar', 'fa-comments'],
    ['historial', 'Historial', 'fa-clock-rotate-left'],
];

export default function ConocimientoTab() {
    const [view, setView] = useState('documentos');

    return (
        <div>
            <div role="tablist" aria-label="Base de conocimiento" className="mb-5 inline-flex rounded-xl bg-fog p-1">
                {VIEWS.map(([key, label, icon]) => (
                    <button key={key} type="button" role="tab" aria-selected={view === key} onClick={() => setView(key)} className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-bold transition-colors ${view === key ? 'bg-white text-blue shadow-sm' : 'text-earth hover:text-navy'}`}>
                        <i className={`fas ${icon}`} aria-hidden="true" /> {label}
                    </button>
                ))}
            </div>
            {view === 'documentos' && <DocumentsPanel />}
            {view === 'preguntar' && <AskPanel />}
            {view === 'historial' && <HistoryPanel />}
        </div>
    );
}
