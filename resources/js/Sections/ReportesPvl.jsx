import { useCallback, useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import errorMessage from '../errorMessage';
import { useToast } from '../Components/Toast';
import ConfirmDialog from '../Components/ConfirmDialog';

const BASE = '/api/dashboard/reportes-pvl';
const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const TYPE_LABELS = {
    factura: 'Factura', comprobante: 'Comprobante', orden_compra: 'Orden de compra', financiamiento: 'Financiamiento',
    donacion: 'Donación', proveedor: 'Proveedor', distribucion: 'Distribución / entrega',
    certificado_calidad: 'Certificado de calidad', certificado_microbiologico: 'Certificado microbiológico',
    ficha_tecnica: 'Ficha técnica', lote: 'Lote / vencimiento', beneficiarios: 'Beneficiarios',
    constancia_envio: 'Constancia de envío a Contraloría', otro: 'Otro respaldo',
};
const REPORT_LABELS = { PVL: 'Formato PVL', RACION_A: 'Ración A', AMBOS: 'Ambos informes' };
const FILE_LABELS = { pvl: 'Formato PVL', 'racion-a': 'Ración A', informe: 'Informe sustentatorio' };
const STATUS_LABELS = {
    BORRADOR: 'Borrador', ANALIZANDO: 'Analizando', REQUIERE_REVISION: 'Requiere revisión',
    LISTO_PARA_GENERAR: 'Listo para generar', GENERADO: 'Generado', ERROR: 'Error',
};

const labelClass = 'block mb-1 text-xs font-bold uppercase tracking-wider text-slate';
const inputClass = 'w-full border-2 border-mist bg-white px-3 py-2.5 text-sm font-semibold text-charcoal focus:border-blue focus:outline-none focus:ring-2 focus:ring-blue/15';

function StatusBadge({ status }) {
    const style = status === 'GENERADO' || status === 'LISTO_PARA_GENERAR'
        ? 'bg-teal-light text-teal'
        : status === 'REQUIERE_REVISION'
            ? 'bg-amber-light text-amber-dark'
            : status === 'ERROR'
                ? 'bg-coral-light text-coral'
                : 'bg-blue-light text-blue';
    return <span className={`badge ${style}`}>{STATUS_LABELS[status] || status}</span>;
}

function FindingList({ findings = [] }) {
    if (!findings.length) {
        return <p className="py-5 text-sm text-earth">No se registraron hallazgos.</p>;
    }

    const icon = { CRITICO: 'fa-circle-xmark', ADVERTENCIA: 'fa-triangle-exclamation', INFORMATIVO: 'fa-circle-info' };
    const color = { CRITICO: 'text-coral', ADVERTENCIA: 'text-amber-dark', INFORMATIVO: 'text-blue' };

    return (
        <ul className="divide-y divide-mist">
            {findings.map((finding, index) => (
                <li key={`${finding.code}-${finding.field}-${index}`} className="flex gap-3 py-3">
                    <i className={`fas ${icon[finding.severity] || icon.INFORMATIVO} mt-0.5 ${color[finding.severity] || color.INFORMATIVO}`} aria-hidden="true" />
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-xs font-extrabold text-charcoal">{finding.severity}</span>
                            <code className="break-all text-[11px] text-slate">{finding.field}</code>
                        </div>
                        <p className="mt-1 text-sm text-earth">{finding.message}</p>
                    </div>
                </li>
            ))}
        </ul>
    );
}

function SourcesTable({ sources = [] }) {
    if (!sources.length) return <p className="py-5 text-sm text-earth">No se encontraron fuentes para este análisis.</p>;

    return (
        <div className="overflow-x-auto">
            <table className="data-table min-w-[760px] w-full text-sm">
                <thead><tr><th className="px-3 py-3 text-left">Campo</th><th className="px-3 py-3 text-left">Origen</th><th className="px-3 py-3 text-left">Referencia</th><th className="px-3 py-3 text-left">Página / fragmento</th><th className="px-3 py-3 text-right">Confianza</th></tr></thead>
                <tbody>
                    {sources.map((source, index) => (
                        <tr key={`${source.campo}-${source.document_id || 'db'}-${index}`}>
                            <td className="px-3 py-3 font-semibold text-charcoal">{source.campo || 'Dato relacionado'}</td>
                            <td className="px-3 py-3"><span className="badge bg-blue-light text-blue">{source.origen || 'RAG'}</span></td>
                            <td className="px-3 py-3 text-earth">{source.archivo || source.referencia || source.entidad || '—'}</td>
                            <td className="px-3 py-3 text-earth">{source.pagina ? `Pág. ${source.pagina}` : '—'}{source.chunk ? ` · Frag. ${source.chunk}` : ''}</td>
                            <td className="px-3 py-3 text-right tabular-nums text-earth">{source.confianza != null ? `${Math.round(Number(source.confianza) * 100)} %` : '—'}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function DataPreview({ data }) {
    if (!data) return <p className="py-5 text-sm text-earth">El análisis aún no tiene datos validados.</p>;

    return (
        <div className="space-y-3">
            {Object.entries(data).map(([report, values]) => (
                <details key={report} open className="border border-mist bg-base">
                    <summary className="cursor-pointer px-4 py-3 font-extrabold text-navy focus:outline-none focus-visible:ring-2 focus-visible:ring-blue">
                        {report === 'pvl' ? 'Valores del Formato PVL' : 'Valores de Ración A'}
                    </summary>
                    <div className="border-t border-mist px-4 py-3">
                        <dl className="grid gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                            {Object.entries(values || {}).map(([key, value]) => (
                                <div key={key} className="min-w-0 border-b border-mist/70 py-2">
                                    <dt className="text-[11px] font-bold uppercase tracking-wide text-slate">{key.replaceAll('_', ' ')}</dt>
                                    <dd className="mt-1 break-words text-sm font-semibold text-charcoal">
                                        {Array.isArray(value) ? `${value.length} registro(s)` : value && typeof value === 'object' ? JSON.stringify(value) : value ?? 'Sin dato'}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </details>
            ))}
        </div>
    );
}

function ReviewPanel({ run, onGenerate, generating, files, canGenerate }) {
    const [reviewTab, setReviewTab] = useState('hallazgos');
    const counts = useMemo(() => ({
        critical: (run.findings || []).filter((item) => item.severity === 'CRITICO').length,
        warning: (run.findings || []).filter((item) => item.severity === 'ADVERTENCIA').length,
        info: (run.findings || []).filter((item) => item.severity === 'INFORMATIVO').length,
    }), [run]);

    return (
        <section className="border-t-2 border-mist" aria-labelledby="review-title">
            <div className="flex flex-col gap-4 px-4 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h3 id="review-title" className="font-heading text-lg font-extrabold text-navy">Revisión del periodo {MONTHS[run.month - 1]} {run.year}</h3>
                        <StatusBadge status={run.status} />
                    </div>
                    <p className="mt-1 text-sm text-earth">{REPORT_LABELS[run.report_type]} · IA {run.model_used || 'sin modelo registrado'} · {run.created_at}</p>
                </div>
                <div className="max-w-sm text-right">
                    <button type="button" onClick={onGenerate} disabled={!canGenerate || !run.can_generate || generating} className="btn-primary inline-flex min-h-11 items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <i className={`fas ${generating ? 'fa-spinner fa-spin' : 'fa-file-pdf'}`} aria-hidden="true" />
                        {generating ? 'Preparando documentos…' : run.report_type === 'AMBOS' ? 'Generar 3 documentos' : 'Generar PDF'}
                    </button>
                    {!run.can_generate && <p className="mt-2 text-xs text-coral">Resuelva los hallazgos críticos, agregue los respaldos faltantes y vuelva a analizar.</p>}
                </div>
            </div>

            <div className="grid border-y border-mist sm:grid-cols-3">
                <div className="px-5 py-4 sm:border-r sm:border-mist"><strong className="block text-2xl font-extrabold tabular-nums text-coral">{counts.critical}</strong><span className="text-xs font-bold uppercase tracking-wide text-slate">Críticos</span></div>
                <div className="border-t border-mist px-5 py-4 sm:border-r sm:border-t-0"><strong className="block text-2xl font-extrabold tabular-nums text-amber-dark">{counts.warning}</strong><span className="text-xs font-bold uppercase tracking-wide text-slate">Advertencias</span></div>
                <div className="border-t border-mist px-5 py-4 sm:border-t-0"><strong className="block text-2xl font-extrabold tabular-nums text-blue">{run.sources?.length || 0}</strong><span className="text-xs font-bold uppercase tracking-wide text-slate">Fuentes trazables</span></div>
            </div>

            {files.length > 0 && (
                <div className="flex flex-wrap gap-2 border-b border-mist bg-teal-light/40 px-4 py-3 sm:px-6" aria-live="polite">
                    {files.map((file) => (
                        <div key={file.type} className="flex items-center gap-2">
                            <a href={file.preview_url} target="_blank" rel="noreferrer" className="btn-secondary inline-flex items-center gap-2 text-sm"><i className="fas fa-eye" /> Vista previa: {FILE_LABELS[file.type] || file.type}</a>
                            <a href={file.download_url} className="btn-primary inline-flex items-center gap-2 text-sm" aria-label={`Descargar ${FILE_LABELS[file.type] || file.type}`}><i className="fas fa-download" /> Descargar</a>
                        </div>
                    ))}
                </div>
            )}

            <div className="flex gap-1 overflow-x-auto border-b border-mist px-4 pt-2 sm:px-6" role="tablist" aria-label="Detalle de revisión">
                {[['hallazgos', 'Hallazgos'], ['fuentes', 'Fuentes'], ['valores', 'Valores validados']].map(([key, label]) => (
                    <button key={key} type="button" role="tab" aria-selected={reviewTab === key} onClick={() => setReviewTab(key)} className={`whitespace-nowrap border-b-2 px-3 py-2 text-sm font-bold ${reviewTab === key ? 'border-blue text-blue' : 'border-transparent text-earth hover:text-navy'}`}>{label}</button>
                ))}
            </div>
            <div className="px-4 py-4 sm:px-6">
                {reviewTab === 'hallazgos' && <FindingList findings={run.findings} />}
                {reviewTab === 'fuentes' && <SourcesTable sources={run.sources} />}
                {reviewTab === 'valores' && <DataPreview data={run.validated_data} />}
            </div>
        </section>
    );
}

export default function ReportesPvl() {
    const toast = useToast();
    const { modules } = usePage().props;
    const permission = (modules || []).find((item) => item.slug === 'reportes');
    const can = { view: !!permission?.can_view, create: !!permission?.can_create, edit: !!permission?.can_edit, del: !!permission?.can_delete };
    const today = new Date();
    const [tab, setTab] = useState('preparar');
    const [reportType, setReportType] = useState('AMBOS');
    const [month, setMonth] = useState(today.getMonth() + 1);
    const [year, setYear] = useState(today.getFullYear());
    const [runs, setRuns] = useState([]);
    const [documents, setDocuments] = useState([]);
    const [products, setProducts] = useState([]);
    const [documentTypes, setDocumentTypes] = useState([]);
    const [currentRun, setCurrentRun] = useState(null);
    const [loading, setLoading] = useState(true);
    const [analyzing, setAnalyzing] = useState(false);
    const [generating, setGenerating] = useState(false);
    const [files, setFiles] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [documentType, setDocumentType] = useState('factura');
    const [productId, setProductId] = useState('');
    const [providerReference, setProviderReference] = useState('');
    const [selectedFile, setSelectedFile] = useState(null);
    const [workflowError, setWorkflowError] = useState(null);
    const [runToDelete, setRunToDelete] = useState(null);
    const [deletingRun, setDeletingRun] = useState(false);
    const [reportMetadata, setReportMetadata] = useState({
        report_number: '', recipient_name: '', recipient_role: '', sender_name: '', sender_role: '', place: 'La Esperanza',
    });

    const updateReportMetadata = (field, value) => setReportMetadata((current) => ({ ...current, [field]: value }));

    const openRun = (run) => {
        setCurrentRun(run);
        setFiles([]);
        setWorkflowError(null);
        setReportType(run.report_type);
        setMonth(run.month);
        setYear(run.year);
        setReportMetadata({
            report_number: '', recipient_name: '', recipient_role: '', sender_name: '', sender_role: '', place: 'La Esperanza',
            ...(run.report_metadata || {}),
        });
        setTab('preparar');
    };

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const response = await http.get(BASE);
            setRuns(response.data.runs || []);
            setDocuments(response.data.documents || []);
            setProducts(response.data.products || []);
            setDocumentTypes(response.data.document_types || []);
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo cargar el módulo de reportes.'));
        } finally {
            setLoading(false);
        }
    }, [toast]);

    useEffect(() => { load(); }, [load]);

    const analyze = async (event) => {
        event.preventDefault();
        setAnalyzing(true);
        setFiles([]);
        setWorkflowError(null);
        try {
            const response = await http.post(`${BASE}/analizar`, {
                report_type: reportType,
                month: Number(month),
                year: Number(year),
                report_metadata: reportType === 'AMBOS' ? reportMetadata : {},
            }, { timeout: 150000 });
            setCurrentRun(response.data.run);
            toast.success(response.data.message);
            await load();
        } catch (error) {
            const run = error.response?.data?.run;
            if (run) setCurrentRun(run);
            const message = errorMessage(error, 'No se pudo completar el análisis.');
            setWorkflowError({ code: error.response?.data?.error_code, status: error.response?.status, message });
            toast.error(message);
        } finally {
            setAnalyzing(false);
        }
    };

    const upload = async (event) => {
        event.preventDefault();
        const formElement = event.currentTarget;
        if (!selectedFile) return toast.error('Seleccione un archivo para indexar.');
        const form = new FormData();
        form.append('document_type', documentType);
        form.append('period', `${year}-${String(month).padStart(2, '0')}`);
        if (productId) form.append('product_id', productId);
        if (providerReference.trim()) form.append('provider_reference', providerReference.trim());
        form.append('file', selectedFile);

        setUploading(true);
        try {
            const response = await http.post(`${BASE}/documents`, form, { headers: { 'Content-Type': 'multipart/form-data' }, timeout: 150000 });
            toast.success(response.data.message);
            setSelectedFile(null);
            setProviderReference('');
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

    const confirmDeleteRun = async () => {
        if (!runToDelete) return;
        setDeletingRun(true);
        try {
            const response = await http.delete(`${BASE}/runs/${runToDelete.id}`);
            toast.success(response.data.message);
            if (currentRun?.id === runToDelete.id) setCurrentRun(null);
            setRunToDelete(null);
            await load();
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo eliminar el análisis.'));
        } finally {
            setDeletingRun(false);
        }
    };

    const generate = async () => {
        if (!currentRun) return;
        setGenerating(true);
        setWorkflowError(null);
        try {
            const response = await http.post(`${BASE}/runs/${currentRun.id}/generar`);
            setFiles(response.data.files || []);
            setCurrentRun(response.data.run);
            toast.success(response.data.message);
            await load();
        } catch (error) {
            const message = errorMessage(error, 'No se pudieron preparar los documentos.');
            setWorkflowError({ code: error.response?.data?.error_code, status: error.response?.status, message });
            toast.error(message);
        } finally {
            setGenerating(false);
        }
    };

    if (!can.view) return <div className="empty-state"><i className="fas fa-lock" /><p>No tiene acceso a este módulo.</p></div>;

    return (
        <div className="overflow-hidden rounded-2xl border-2 border-mist bg-white shadow-sm">
            <header className="px-4 py-5 sm:px-6">
                <h1 className="flex items-center gap-3 font-heading text-xl font-extrabold text-navy sm:text-2xl"><i className="fas fa-file-shield text-blue" /> Generación Inteligente de Reportes PVL</h1>
                <p className="mt-1 max-w-3xl text-sm text-earth">Consolida datos del sistema y documentos trazables. Laravel valida cada cálculo antes de habilitar los anexos oficiales.</p>
            </header>

            <nav className="flex gap-1 overflow-x-auto border-y-2 border-mist px-4 pt-2 sm:px-6" role="tablist" aria-label="Secciones de reportes">
                {[['preparar', 'Preparar informe', 'fa-wand-magic-sparkles'], ['documentos', 'Fuentes documentales', 'fa-folder-open'], ['historial', 'Historial', 'fa-clock-rotate-left']].map(([key, label, icon]) => (
                    <button key={key} type="button" role="tab" aria-selected={tab === key} onClick={() => setTab(key)} className={`flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-bold ${tab === key ? 'border-blue text-blue' : 'border-transparent text-earth hover:text-navy'}`}><i className={`fas ${icon}`} aria-hidden="true" /> {label}</button>
                ))}
            </nav>

            {loading ? <div className="flex items-center justify-center gap-2 py-16 text-earth"><i className="fas fa-spinner fa-spin" /> Cargando reportes…</div> : (
                <>
                    {tab === 'preparar' && (
                        <>
                            <form onSubmit={analyze} className="space-y-5 px-4 py-6 sm:px-6">
                                <div className="grid gap-4 md:grid-cols-[minmax(220px,1.3fr)_minmax(150px,.8fr)_minmax(130px,.65fr)_auto] md:items-end">
                                    <div><label className={labelClass} htmlFor="pvl-report-type">Tipo de informe</label><select id="pvl-report-type" value={reportType} onChange={(event) => setReportType(event.target.value)} className={inputClass}><option value="PVL">Formato PVL</option><option value="RACION_A">Ración A</option><option value="AMBOS">Ambos anexos + informe sustentatorio</option></select></div>
                                    <div><label className={labelClass} htmlFor="pvl-month">Mes</label><select id="pvl-month" value={month} onChange={(event) => setMonth(event.target.value)} className={inputClass}>{MONTHS.map((name, index) => <option key={name} value={index + 1}>{name}</option>)}</select></div>
                                    <div><label className={labelClass} htmlFor="pvl-year">Año</label><input id="pvl-year" type="number" min="2000" max="2100" value={year} onChange={(event) => setYear(event.target.value)} className={inputClass} /></div>
                                    <button type="submit" disabled={!can.create || analyzing} className="btn-primary inline-flex min-h-11 items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-50"><i className={`fas ${analyzing ? 'fa-spinner fa-spin' : 'fa-magnifying-glass-chart'}`} /> {analyzing ? 'Analizando…' : 'Analizar información'}</button>
                                </div>
                                {reportType === 'AMBOS' && (
                                    <fieldset className="border-t border-mist pt-5">
                                        <legend className="pr-3 font-heading text-base font-extrabold text-navy">Datos del informe sustentatorio</legend>
                                        <p className="mb-4 mt-1 max-w-3xl text-sm text-earth">Estos datos identifican el tercer PDF. Si un campo queda vacío, el sistema dejará una línea pendiente y no inventará nombres ni numeración.</p>
                                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div><label className={labelClass} htmlFor="report-number">Número de informe</label><input id="report-number" value={reportMetadata.report_number} onChange={(event) => updateReportMetadata('report_number', event.target.value)} maxLength={80} className={inputClass} placeholder="Ej. 123-2026-MDE/GDS/SGPS" /></div>
                                            <div><label className={labelClass} htmlFor="recipient-name">Destinatario</label><input id="recipient-name" value={reportMetadata.recipient_name} onChange={(event) => updateReportMetadata('recipient_name', event.target.value)} maxLength={160} className={inputClass} placeholder="Nombre completo" /></div>
                                            <div><label className={labelClass} htmlFor="recipient-role">Cargo del destinatario</label><input id="recipient-role" value={reportMetadata.recipient_role} onChange={(event) => updateReportMetadata('recipient_role', event.target.value)} maxLength={160} className={inputClass} placeholder="Cargo o dependencia" /></div>
                                            <div><label className={labelClass} htmlFor="sender-name">Remitente</label><input id="sender-name" value={reportMetadata.sender_name} onChange={(event) => updateReportMetadata('sender_name', event.target.value)} maxLength={160} className={inputClass} placeholder="Nombre completo" /></div>
                                            <div><label className={labelClass} htmlFor="sender-role">Cargo del remitente</label><input id="sender-role" value={reportMetadata.sender_role} onChange={(event) => updateReportMetadata('sender_role', event.target.value)} maxLength={160} className={inputClass} placeholder="Programa del Vaso de Leche" /></div>
                                            <div><label className={labelClass} htmlFor="report-place">Lugar</label><input id="report-place" value={reportMetadata.place} onChange={(event) => updateReportMetadata('place', event.target.value)} maxLength={100} className={inputClass} /></div>
                                        </div>
                                    </fieldset>
                                )}
                            </form>
                            {workflowError && (
                                <div role="alert" className="mx-4 mb-5 border border-coral/40 bg-coral-light px-4 py-3 text-sm text-coral sm:mx-6">
                                    <div className="flex items-start gap-3">
                                        <i className="fas fa-circle-exclamation mt-0.5" aria-hidden="true" />
                                        <div className="min-w-0"><strong className="block">No se completó la operación{workflowError.status ? ` (HTTP ${workflowError.status})` : ''}</strong><p className="mt-1 break-words">{workflowError.message}</p>{workflowError.code && <code className="mt-2 block text-xs">{workflowError.code}</code>}</div>
                                    </div>
                                </div>
                            )}
                            <div className="border-t border-mist bg-base px-4 py-3 text-xs text-earth sm:px-6" aria-live="polite"><i className="fas fa-shield-halved mr-2 text-teal" />La IA configurada extrae y concilia. No modifica plantillas ni calcula totales finales.</div>
                            {currentRun ? <ReviewPanel run={currentRun} onGenerate={generate} generating={generating} files={files} canGenerate={can.edit} /> : <div className="empty-state border-t border-mist py-14"><i className="fas fa-file-circle-check" /><p>Seleccione periodo y tipo de informe para iniciar revisión.</p></div>}
                        </>
                    )}

                    {tab === 'documentos' && (
                        <div className="grid lg:grid-cols-[minmax(290px,.8fr)_minmax(0,1.7fr)]">
                            <form onSubmit={upload} className="space-y-4 border-b border-mist p-4 sm:p-6 lg:border-b-0 lg:border-r">
                                <div><h2 className="font-heading text-lg font-extrabold text-navy">Indexar respaldo</h2><p className="mt-1 text-sm text-earth">Periodo activo: {MONTHS[month - 1]} {year}. Agregue también la constancia de envío para que el informe pueda acreditar la remisión a Contraloría.</p></div>
                                <div><label className={labelClass} htmlFor="document-type">Tipo documental</label><select id="document-type" value={documentType} onChange={(event) => setDocumentType(event.target.value)} className={inputClass}>{documentTypes.map((type) => <option key={type} value={type}>{TYPE_LABELS[type] || type}</option>)}</select></div>
                                <div><label className={labelClass} htmlFor="document-product">Producto relacionado</label><select id="document-product" value={productId} onChange={(event) => setProductId(event.target.value)} className={inputClass}><option value="">No aplica</option>{products.map((product) => <option key={product.id} value={product.id}>{product.title}</option>)}</select></div>
                                <div><label className={labelClass} htmlFor="provider-reference">Proveedor / referencia</label><input id="provider-reference" value={providerReference} onChange={(event) => setProviderReference(event.target.value)} maxLength={160} className={inputClass} placeholder="Opcional" /></div>
                                <div><label className={labelClass} htmlFor="document-file">Archivo</label><input id="document-file" type="file" accept=".pdf,.txt,.csv,.json,.png,.jpg,.jpeg" onChange={(event) => setSelectedFile(event.target.files?.[0] || null)} className={`${inputClass} file:mr-3 file:border-0 file:bg-blue-light file:px-3 file:py-1 file:font-bold file:text-blue`} /></div>
                                <button type="submit" disabled={!can.create || uploading} className="btn-primary inline-flex w-full items-center justify-center gap-2 disabled:opacity-50"><i className={`fas ${uploading ? 'fa-spinner fa-spin' : 'fa-cloud-arrow-up'}`} /> {uploading ? 'Indexando…' : 'Guardar e indexar'}</button>
                            </form>
                            <div className="min-w-0 p-4 sm:p-6">
                                <h2 className="font-heading text-lg font-extrabold text-navy">Documentos disponibles</h2>
                                <div className="mt-4 overflow-x-auto">
                                    <table className="data-table min-w-[720px] w-full text-sm"><thead><tr><th className="px-3 py-3 text-left">Archivo</th><th className="px-3 py-3 text-left">Periodo</th><th className="px-3 py-3 text-left">Tipo</th><th className="px-3 py-3 text-left">Indexación</th><th className="px-3 py-3 text-right">Acciones</th></tr></thead><tbody>
                                        {documents.length ? documents.map((document) => <tr key={document.id}><td className="px-3 py-3"><strong className="block text-charcoal">{document.file_name}</strong><span className="text-xs text-slate">{document.product || document.provider_reference || `${Math.ceil(document.file_size / 1024)} KB`}</span></td><td className="px-3 py-3 tabular-nums">{document.period}</td><td className="px-3 py-3">{TYPE_LABELS[document.document_type] || document.document_type}</td><td className="px-3 py-3"><span className={`badge ${document.index_status === 'INDEXADO' ? 'bg-teal-light text-teal' : 'bg-coral-light text-coral'}`}>{document.index_status}</span></td><td className="px-3 py-3 text-right"><div className="inline-flex gap-2"><a href={document.download_url} target="_blank" rel="noreferrer" className="btn-action bg-blue-light text-blue" title="Ver documento" aria-label={`Ver ${document.file_name}`}><i className="fas fa-eye" /></a>{can.del && <button type="button" onClick={() => removeDocument(document)} className="btn-action bg-clay-light text-clay" title="Eliminar documento" aria-label={`Eliminar ${document.file_name}`}><i className="fas fa-trash" /></button>}</div></td></tr>) : <tr><td colSpan="5"><div className="empty-state py-10"><i className="fas fa-folder-open" /><p>No hay documentos PVL indexados.</p></div></td></tr>}
                                    </tbody></table>
                                </div>
                            </div>
                        </div>
                    )}

                    {tab === 'historial' && (
                        <div className="p-4 sm:p-6">
                            <div className="overflow-x-auto"><table className="data-table min-w-[760px] w-full text-sm"><thead><tr><th className="px-3 py-3 text-left">Periodo</th><th className="px-3 py-3 text-left">Informe</th><th className="px-3 py-3 text-left">Estado</th><th className="px-3 py-3 text-left">Fecha</th><th className="px-3 py-3 text-right">Acciones</th></tr></thead><tbody>
                                {runs.length ? runs.map((run) => <tr key={run.id}><td className="px-3 py-3 font-bold text-charcoal">{MONTHS[run.month - 1]} {run.year}</td><td className="px-3 py-3">{REPORT_LABELS[run.report_type]}</td><td className="px-3 py-3"><StatusBadge status={run.status} /></td><td className="px-3 py-3 text-earth">{run.created_at}</td><td className="px-3 py-3 text-right"><div className="inline-flex gap-2"><button type="button" onClick={() => openRun(run)} className="btn-action bg-blue-light text-blue" title="Abrir análisis" aria-label={`Abrir análisis de ${MONTHS[run.month - 1]} ${run.year}`}><i className="fas fa-eye" /></button>{can.del && <button type="button" onClick={() => setRunToDelete(run)} className="btn-action bg-clay-light text-clay" title="Eliminar análisis" aria-label={`Eliminar análisis de ${MONTHS[run.month - 1]} ${run.year}`}><i className="fas fa-trash" /></button>}</div></td></tr>) : <tr><td colSpan="5"><div className="empty-state py-10"><i className="fas fa-clock-rotate-left" /><p>No hay análisis registrados.</p></div></td></tr>}
                            </tbody></table></div>
                        </div>
                    )}
                </>
            )}

            <ConfirmDialog
                open={!!runToDelete}
                onCancel={() => setRunToDelete(null)}
                onConfirm={confirmDeleteRun}
                loading={deletingRun}
                title="Eliminar análisis"
                message="Se eliminará este análisis del historial de forma permanente."
                details={runToDelete ? [
                    { label: 'Periodo', value: `${MONTHS[runToDelete.month - 1]} ${runToDelete.year}` },
                    { label: 'Informe', value: REPORT_LABELS[runToDelete.report_type] },
                ] : []}
            />
        </div>
    );
}
