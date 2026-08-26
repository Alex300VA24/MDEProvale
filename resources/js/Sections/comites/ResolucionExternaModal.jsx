import { useEffect, useRef, useState } from 'react';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';

const BASE = '/api/dashboard/club-madres';

export default function ResolucionExternaModal({ open, resolution, onClose }) {
    const toast = useToast();
    const [status, setStatus] = useState('idle');
    const [result, setResult] = useState(null);
    const [errorMessage, setErrorMessage] = useState('');
    const [pdfLoading, setPdfLoading] = useState(null);
    const activeRef = useRef(true);

    const buscar = async () => {
        if (!resolution) return;
        setStatus('loading');
        setErrorMessage('');
        setPdfLoading(null);
        try {
            const res = await fetch(`${BASE}/reconocimientos/${resolution.id}/buscar-externa`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            if (!activeRef.current) return;
            if (!res.ok) {
                setStatus('error');
                setErrorMessage(data.message || 'No se encontró esta resolución en el portal de la Municipalidad de La Esperanza.');
                return;
            }
            setResult(data);
            setStatus('success');
        } catch {
            if (!activeRef.current) return;
            setStatus('error');
            setErrorMessage('No se pudo conectar con el portal de la Municipalidad. Verifica tu conexión e inténtalo de nuevo.');
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
            const endpoint = mode === 'preview' ? 'preview-externa' : 'descargar-externa';
            const res = await fetch(`${BASE}/reconocimientos/${resolution.id}/${endpoint}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || `No se pudo ${mode === 'preview' ? 'ver' : 'descargar'} la resolución desde el portal.`);
            }
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            window.open(url, '_blank');
            setTimeout(() => URL.revokeObjectURL(url), 60000);
        } catch (e) {
            toast.error(e.message || 'No se pudo obtener el documento desde el portal municipal.');
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
                    </div>
                ) : status === 'error' ? (
                    <div className="empty-state py-8">
                        <i className="fas fa-unlink" />
                        <p className="max-w-xs">{errorMessage}</p>
                        <button type="button" onClick={buscar} className="btn-primary mt-4 text-xs">
                            <i className="fas fa-rotate mr-2" /> Reintentar
                        </button>
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
                            <button type="button" onClick={onClose} disabled={!!pdfLoading} className="btn-secondary sm:flex-1">
                                Cerrar
                            </button>
                            <button
                                type="button"
                                onClick={() => loadPdf('descargar')}
                                disabled={!!pdfLoading}
                                className="btn-secondary sm:flex-1 disabled:opacity-60"
                            >
                                <i className={`fas ${pdfLoading === 'descargar' ? 'fa-spinner fa-spin' : 'fa-download'} mr-2`} />
                                Descargar PDF
                            </button>
                            <button
                                type="button"
                                onClick={() => loadPdf('preview')}
                                disabled={!!pdfLoading}
                                className="btn-primary sm:flex-1 disabled:opacity-60"
                            >
                                <i className={`fas ${pdfLoading === 'preview' ? 'fa-spinner fa-spin' : 'fa-eye'} mr-2`} />
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
