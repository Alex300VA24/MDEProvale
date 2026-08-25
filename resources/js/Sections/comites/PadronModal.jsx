import { useState } from 'react';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';

const MESES = [
    '',
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

const selectCls =
    'w-full px-3 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

export default function PadronModal({ open, onClose }) {
    const toast = useToast();
    const now = new Date();
    const [month, setMonth] = useState(String(now.getMonth() + 1));
    const [year, setYear] = useState(String(now.getFullYear()));
    const [loading, setLoading] = useState(false);

    const years = [];
    for (let y = now.getFullYear() - 10; y <= now.getFullYear() + 1; y++) years.push(y);

    const handleGenerate = async () => {
        setLoading(true);
        const preview = window.open('', '_blank');
        if (!preview) {
            setLoading(false);
            toast.error('Habilita las ventanas emergentes para este sitio e inténtalo de nuevo.');
            return;
        }
        try {
            const params = new URLSearchParams({ month, year });
            const res = await fetch(`/club-reconocimientos/club-padron?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || 'No se pudo generar el padrón para el periodo seleccionado.');
            }
            const blob = await res.blob();
            preview.location = URL.createObjectURL(blob);
        } catch (e) {
            preview.close();
            toast.error(e.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Padrón de Clubes de Madres"
            icon="fa-clipboard-list"
            iconClass="text-leaf"
            maxWidth="sm:max-w-2xl"
        >
            <div className="p-6 space-y-5">
                <p className="text-sm text-earth">
                    Seleccione el mes y año para generar el padrón de clubes de madres vigentes en el periodo.
                </p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-bold text-charcoal mb-2">
                            <i className="fas fa-calendar-alt mr-1" /> Mes *
                        </label>
                        <select value={month} onChange={(e) => setMonth(e.target.value)} className={selectCls}>
                            {MESES.map((m, i) => i > 0 && <option key={i} value={String(i)}>{m}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-charcoal mb-2">
                            <i className="fas fa-calendar mr-1" /> Año *
                        </label>
                        <select value={year} onChange={(e) => setYear(e.target.value)} className={selectCls}>
                            {years.map((y) => (
                                <option key={y} value={String(y)}>
                                    {y}
                                </option>
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
                        Generar Padrón
                    </button>
                </div>
            </div>
        </Modal>
    );
}
