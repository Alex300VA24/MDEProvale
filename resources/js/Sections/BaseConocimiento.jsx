import { useCallback, useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import errorMessage from '../errorMessage';
import MarkdownContent from '../Components/MarkdownContent';
import { useToast } from '../Components/Toast';

const BASE = '/api/dashboard/base-conocimiento';

const labelClass = 'block mb-1 text-xs font-bold uppercase tracking-wider text-slate';
const inputClass = 'w-full border-2 border-mist bg-white px-3 py-2.5 text-sm font-semibold text-charcoal focus:border-blue focus:outline-none focus:ring-2 focus:ring-blue/15';

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
    const [loading, setLoading] = useState(true);
    const [title, setTitle] = useState('');
    const [selectedFile, setSelectedFile] = useState(null);
    const [uploading, setUploading] = useState(false);

    const [question, setQuestion] = useState('');
    const [asking, setAsking] = useState(false);
    const [conversation, setConversation] = useState([]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const response = await http.get(BASE);
            setDocuments(response.data.documents || []);
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
                                                                <span className="font-semibold text-charcoal">{source.archivo}</span>
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
                <div className="grid lg:grid-cols-[minmax(290px,.8fr)_minmax(0,1.7fr)]">
                    <form onSubmit={upload} className="space-y-4 border-b border-mist p-4 sm:p-6 lg:border-b-0 lg:border-r">
                        <div><h2 className="font-heading text-lg font-extrabold text-navy">Indexar documento</h2><p className="mt-1 text-sm text-earth">El documento se procesa y fragmenta automáticamente para responder preguntas sobre su contenido.</p></div>
                        <div><label className={labelClass} htmlFor="kb-title">Título (opcional)</label><input id="kb-title" value={title} onChange={(event) => setTitle(event.target.value)} maxLength={255} className={inputClass} placeholder="Ej. Reglamento interno 2026" /></div>
                        <div><label className={labelClass} htmlFor="kb-file">Archivo</label><input id="kb-file" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx,.xls,.xlsx" onChange={(event) => setSelectedFile(event.target.files?.[0] || null)} className={`${inputClass} file:mr-3 file:border-0 file:bg-blue-light file:px-3 file:py-1 file:font-bold file:text-blue`} /></div>
                        <p className="text-xs text-earth">Formatos admitidos: PDF (también escaneado), JPG, PNG, Word (.docx) y Excel (.xls, .xlsx).</p>
                        <button type="submit" disabled={!can.create || uploading} className="btn-primary inline-flex w-full items-center justify-center gap-2 disabled:opacity-50"><i className={`fas ${uploading ? 'fa-spinner fa-spin' : 'fa-cloud-arrow-up'}`} /> {uploading ? 'Indexando…' : 'Guardar e indexar'}</button>
                    </form>
                    <div className="min-w-0 p-4 sm:p-6">
                        <h2 className="font-heading text-lg font-extrabold text-navy">Documentos indexados</h2>
                        {loading ? (
                            <div className="flex items-center justify-center gap-2 py-16 text-earth"><i className="fas fa-spinner fa-spin" /> Cargando documentos…</div>
                        ) : (
                            <div className="mt-4 overflow-x-auto">
                                <table className="data-table min-w-[720px] w-full text-sm"><thead><tr><th className="px-3 py-3 text-left">Documento</th><th className="px-3 py-3 text-left">Tamaño</th><th className="px-3 py-3 text-left">Indexación</th><th className="px-3 py-3 text-left">Cargado</th><th className="px-3 py-3 text-right">Acciones</th></tr></thead><tbody>
                                    {documents.length ? documents.map((document) => (
                                        <tr key={document.id}>
                                            <td className="px-3 py-3"><strong className="block text-charcoal">{document.title}</strong><span className="text-xs text-slate">{document.file_name}</span></td>
                                            <td className="px-3 py-3 tabular-nums">{Math.ceil(document.file_size / 1024)} KB</td>
                                            <td className="px-3 py-3"><StatusBadge status={document.index_status} />{document.index_error && <p className="mt-1 max-w-[220px] text-xs text-coral">{document.index_error}</p>}</td>
                                            <td className="px-3 py-3 text-earth">{document.created_at}</td>
                                            <td className="px-3 py-3 text-right">
                                                <div className="inline-flex gap-2">
                                                    <a href={document.download_url} target="_blank" rel="noreferrer" className="btn-action bg-blue-light text-blue" title="Ver documento" aria-label={`Ver ${document.file_name}`}><i className="fas fa-eye" /></a>
                                                    {can.del && <button type="button" onClick={() => removeDocument(document)} className="btn-action bg-clay-light text-clay" title="Eliminar documento" aria-label={`Eliminar ${document.file_name}`}><i className="fas fa-trash" /></button>}
                                                </div>
                                            </td>
                                        </tr>
                                    )) : <tr><td colSpan="5"><div className="empty-state py-10"><i className="fas fa-folder-open" /><p>No hay documentos indexados.</p></div></td></tr>}
                                </tbody></table>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
