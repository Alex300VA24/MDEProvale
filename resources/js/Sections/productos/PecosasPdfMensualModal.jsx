import { useRef, useState } from 'react';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import { pdfLoadingHtml } from '../../Components/pdfLoadingScreen';

const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const selectCls =
    'w-full px-3 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

export default function PecosasPdfMensualModal({ open, onClose }) {
    const toast = useToast();
    const now = new Date();
    const [month, setMonth] = useState(now.getMonth() + 1);
    const [year, setYear] = useState(now.getFullYear());
    const [loading, setLoading] = useState(false);
    const generationId = useRef(0);

    const years = [];
    for (let y = now.getFullYear() + 1; y >= now.getFullYear() - 6; y--) years.push(y);

    const handleGenerate = async () => {
        const currentGeneration = ++generationId.current;
        setLoading(true);
        const preview = window.open('', '_blank');
        if (!preview) {
            setLoading(false);
            toast.error('Habilita las ventanas emergentes para este sitio e inténtalo de nuevo.');
            return;
        }
        preview.document.write(pdfLoadingHtml(
            'Generando PDF de Pecosas',
            `${MONTHS[month - 1]} ${year} — Procesando comprobantes, esto puede tardar unos segundos.`
        ));
        preview.document.close();

        const controller = new AbortController();
        let cancelled = false;
        const closeWatcher = window.setInterval(() => {
            if (preview.closed) {
                cancelled = true;
                controller.abort();
                if (generationId.current === currentGeneration) setLoading(false);
                window.clearInterval(closeWatcher);
            }
        }, 300);

        try {
            const params = new URLSearchParams({ month: String(month), year: String(year) });
            const res = await fetch(`${window.APP_URL || ''}/productos-pecosas/pecosas-pdf-mensual?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!res.ok) {
                const body = await res.json().catch(() => ({}));
                throw new Error(body.message || 'No se pudo generar el PDF del período seleccionado.');
            }
            const blob = await res.blob();
            if (!preview.closed) preview.location = URL.createObjectURL(blob);
            onClose();
        } catch (e) {
            if (!cancelled) {
                if (!preview.closed) preview.close();
                toast.error(e.message);
            }
        } finally {
            window.clearInterval(closeWatcher);
            if (generationId.current === currentGeneration) setLoading(false);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="PDF Mensual de Pecosas"
            icon="fa-file-pdf"
            iconClass="text-leaf"
            maxWidth="sm:max-w-lg"
        >
            <div className="p-6 space-y-5">
                <p className="text-sm text-earth">
                    Selecciona el mes y año para generar en un solo PDF los comprobantes de salida sin valores de todas las pecosas de ese período.
                </p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-bold text-charcoal mb-2">
                            <i className="fas fa-calendar-alt mr-1" /> Mes *
                        </label>
                        <select value={month} onChange={(e) => setMonth(Number(e.target.value))} className={selectCls}>
                            {MONTHS.map((m, i) => (
                                <option key={m} value={i + 1}>{m}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-charcoal mb-2">
                            <i className="fas fa-calendar mr-1" /> Año *
                        </label>
                        <select value={year} onChange={(e) => setYear(Number(e.target.value))} className={selectCls}>
                            {years.map((y) => (
                                <option key={y} value={y}>{y}</option>
                            ))}
                        </select>
                    </div>
                </div>
                <div className="flex gap-3 pt-2">
                    <button type="button" onClick={onClose} disabled={loading} className="btn-secondary flex-1">
                        Cerrar
                    </button>
                    <button
                        type="button"
                        onClick={handleGenerate}
                        disabled={loading}
                        className="btn-primary flex-1 disabled:opacity-60"
                    >
                        <i className={`fas ${loading ? 'fa-spinner fa-spin' : 'fa-file-pdf'} mr-2`} />
                        {loading ? 'Generando...' : 'Generar Pecosas'}
                    </button>
                </div>
            </div>
        </Modal>
    );
}
