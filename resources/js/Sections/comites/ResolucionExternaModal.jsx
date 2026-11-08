import { useEffect, useRef, useState } from 'react';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';

const BASE = '/api/dashboard/club-madres';
const PORTAL_URL = 'https://www.muniesperanza.gob.pe';
const SEARCH_TIMEOUT_MS = 40000;
const PDF_TIMEOUT_MS = 60000;
const SLOW_AFTER_MS = 8000;

const OFFLINE_ERROR = {
    reason: 'offline',
    message: 'Tu equipo no tiene conexión a internet.',
    hint: 'Revisa tu Wi-Fi o cable de red y vuelve a intentarlo.',
    retryable: true,
};

// fetch con tiempo máximo: evita que el modal quede cargando para siempre.
async function fetchWithTimeout(url, ms) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), ms);
    try {
        return await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
    } finally {
        clearTimeout(timer);
    }
}

function networkError(e) {
    if (typeof navigator !== 'undefined' && navigator.onLine === false) return OFFLINE_ERROR;
    if (e?.name === 'AbortError') {
        return {
            reason: 'timeout',
            message: 'La consulta tardó demasiado y se canceló.',
            hint: 'Tu internet o el portal municipal están lentos. Inténtalo de nuevo en unos minutos.',
            retryable: true,
        };
    }
    return {
        reason: 'connection',
        message: 'No se pudo comunicar con el sistema.',
        hint: 'Verifica tu conexión a internet e inténtalo de nuevo.',
        retryable: true,
    };
}

const REASON_ICON = {
    offline: 'fa-wifi',
    timeout: 'fa-hourglass-end',
    dns: 'fa-globe',
    ssl: 'fa-lock',
    blocked: 'fa-ban',
    server_error: 'fa-server',
    not_found: 'fa-file-circle-question',
};

export default function ResolucionExternaModal({ open, resolution, onClose }) {
    const toast = useToast();
    const [status, setStatus] = useState('idle');
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);
    const [slow, setSlow] = useState(false);
    const [pdfLoading, setPdfLoading] = useState(null);
    const activeRef = useRef(true);

    const buscar = async () => {
        if (!resolution) return;
        setStatus('loading');
        setError(null);
        setSlow(false);
        setPdfLoading(null);

        if (navigator.onLine === false) {
            setStatus('error');
            setError(OFFLINE_ERROR);
            return;
        }

        const slowTimer = setTimeout(() => activeRef.current && setSlow(true), SLOW_AFTER_MS);
        try {
            const res = await fetchWithTimeout(
                `${window.APP_URL || ''}${BASE}/reconocimientos/${resolution.id}/buscar-externa`,
                SEARCH_TIMEOUT_MS,
            );
            const data = await res.json().catch(() => ({}));
            if (!activeRef.current) return;
            if (!res.ok) {
                setStatus('error');
                setError({
                    reason: data.reason || (res.status === 404 ? 'not_found' : 'server_error'),
                    message: data.message || 'No se encontró esta resolución en el portal de la Municipalidad de La Esperanza.',
                    hint: data.hint || null,
                    retryable: data.retryable !== false,
                });
                return;
            }
            setResult(data);
            setStatus('success');
        } catch (e) {
            if (!activeRef.current) return;
            setStatus('error');
            setError(networkError(e));
        } finally {
            clearTimeout(slowTimer);
            if (activeRef.current) setSlow(false);
        }
    };

    useEffect(() => {
        activeRef.current = true;
        if (open) {
            setResult(null);
            buscar();
        }
        return () => {
            activeRef.current = false;
        };
    }, [open, resolution?.id]);

    const loadPdf = async (mode) => {
        if (pdfLoading) return;
        setPdfLoading(mode);
        try {
            if (navigator.onLine === false) throw { alert: OFFLINE_ERROR };
            const endpoint = mode === 'preview' ? 'preview-externa' : 'descargar-externa';
            const res = await fetchWithTimeout(
                `${window.APP_URL || ''}${BASE}/reconocimientos/${resolution.id}/${endpoint}`,
                PDF_TIMEOUT_MS,
            );
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw {
                    alert: {
                        message: data.message || `No se pudo ${mode === 'preview' ? 'ver' : 'descargar'} la resolución desde el portal.`,
                        hint: data.hint || null,
                    },
                };
            }
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            if (mode === 'preview') {
                window.open(url, '_blank');
            } else {
                // Descarga directa: ancla temporal con atributo download, sin abrir pestaña.
                const link = document.createElement('a');
                link.href = url;
                link.download = `Resolucion-${resolution.document || resolution.id}-MDE.pdf`;
                document.body.appendChild(link);
                link.click();
                link.remove();
            }
            setTimeout(() => URL.revokeObjectURL(url), 60000);
        } catch (e) {
            const a = e?.alert || networkError(e);
            toast.error(a.hint ? `${a.message} ${a.hint}` : a.message);
        } finally {
            setPdfLoading(null);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Resolución en el Portal Municipal"
            icon="fa-external-link-alt"
            iconClass="text-[#1E5799]"
            maxWidth="sm:max-w-lg"
        >
            <div className="p-6">
                <p className="text-sm text-earth mb-4">
                    Documento: <span className="font-bold text-charcoal">{resolution ? resolution.document : ''}</span>
                </p>

                {status === 'idle' || status === 'loading' ? (
                    <div className="flex flex-col items-center justify-center py-10 text-earth gap-3">
                        <i className="fas fa-spinner fa-spin text-3xl text-[#1E5799]" />
                        <p className="text-sm font-semibold">Consultando el portal de la Municipalidad de La Esperanza...</p>
                        <p className="text-xs">Esto puede tomar unos segundos.</p>
                        {slow && (
                            <div role="alert" className="mt-2 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-left text-xs text-amber-800">
                                <i className="fas fa-triangle-exclamation mt-0.5" aria-hidden="true" />
                                <span>La respuesta está tardando más de lo normal. Puede ser lentitud de tu internet o del portal municipal; sigue esperando o cancela y reintenta.</span>
                            </div>
                        )}
                    </div>
                ) : status === 'error' ? (
                    <div className="empty-state py-8" role="alert">
                        <i className={`fas ${REASON_ICON[error?.reason] || 'fa-unlink'}`} aria-hidden="true" />
                        <p className="max-w-xs font-semibold">{error?.message}</p>
                        {error?.hint && <p className="max-w-xs text-xs mt-2">{error.hint}</p>}
                        <div className="flex flex-wrap justify-center gap-2 mt-4">
                            {error?.retryable !== false && (
                                <button type="button" onClick={buscar} className="btn-primary text-xs">
                                    <i className="fas fa-rotate mr-2" /> Reintentar
                                </button>
                            )}
                            {error?.reason !== 'offline' && (
                                <a href={PORTAL_URL} target="_blank" rel="noopener noreferrer" className="btn-secondary text-xs">
                                    <i className="fas fa-external-link-alt mr-2" aria-hidden="true" /> Abrir portal
                                </a>
                            )}
                        </div>
                    </div>
                ) : (
                    <div>
                        <div className="bg-cream border-2 border-wheat rounded-xl p-4 space-y-2">
                            <div>
                                <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Título encontrado</p>
                                <p className="text-base font-bold text-charcoal">{result?.titulo || '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Fecha de publicación</p>
                                <p className="text-base font-bold text-charcoal">{result?.fecha || '-'}</p>
                            </div>
                        </div>
                        <div className="flex flex-col sm:flex-row gap-3 mt-6">
                            <button
                                type="button"
                                onClick={() => loadPdf('descargar')}
                                disabled={!!pdfLoading}
                                className="btn-secondary flex-1 text-xs sm:text-sm disabled:opacity-60"
                            >
                                <i className={`fas ${pdfLoading === 'descargar' ? 'fa-spinner fa-spin' : 'fa-download'} mr-2`} aria-hidden="true" />
                                Descargar PDF
                            </button>
                            <button
                                type="button"
                                onClick={() => loadPdf('preview')}
                                disabled={!!pdfLoading}
                                className="btn-primary flex-1 text-xs sm:text-sm disabled:opacity-60"
                            >
                                <i className={`fas ${pdfLoading === 'preview' ? 'fa-spinner fa-spin' : 'fa-eye'} mr-2`} aria-hidden="true" />
                                Ver PDF
                            </button>
                        </div>
                        <p className="text-xs text-earth text-center mt-4">
                            El documento se descarga desde el portal municipal y puede tardar unos segundos.
                        </p>
                    </div>
                )}
            </div>
        </Modal>
    );
}
