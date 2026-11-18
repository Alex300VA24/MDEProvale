import { useEffect, useMemo, useState } from 'react';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import Combobox from '../../Components/Combobox';
import { pdfLoadingHtml } from '../../Components/pdfLoadingScreen';
import { associationLabel } from '../comites/format';

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

export default function PadronModal({ open, onClose, options, rosters = [] }) {
    const toast = useToast();
    const now = new Date();
    const [associationId, setAssociationId] = useState(null);
    const [month, setMonth] = useState(String(now.getMonth() + 1));
    const [year, setYear] = useState(String(now.getFullYear()));
    const [loading, setLoading] = useState(false);

    const closedPeriods = useMemo(() => (Array.isArray(rosters) ? rosters : [])
        .filter((roster) => roster?.status === 'CLOSED' && /^\d{4}-\d{2}/.test(roster?.period || ''))
        .map((roster) => ({
            year: String(roster.period).slice(0, 4),
            month: String(Number(String(roster.period).slice(5, 7))),
        }))
        .sort((a, b) => `${b.year}-${b.month.padStart(2, '0')}`.localeCompare(`${a.year}-${a.month.padStart(2, '0')}`)), [rosters]);
    const years = [...new Set(closedPeriods.map((period) => period.year))];
    const months = closedPeriods.filter((period) => period.year === year);
    const selectedPeriodExists = months.some((period) => period.month === month);

    useEffect(() => {
        if (!open || closedPeriods.length === 0) return;
        const currentExists = closedPeriods.some((period) => period.year === year && period.month === month);
        if (!currentExists) {
            setYear(closedPeriods[0].year);
            setMonth(closedPeriods[0].month);
        }
    }, [closedPeriods, month, open, year]);

    const handleGenerate = async () => {
        if (!associationId) {
            toast.error('Seleccione un comité para generar el padrón.');
            return;
        }
        if (!selectedPeriodExists) {
            toast.error('Seleccione un padrón mensual que ya esté cerrado.');
            return;
        }
        setLoading(true);
        const preview = window.open('', '_blank');
        if (!preview) {
            setLoading(false);
            toast.error('Habilita las ventanas emergentes para este sitio e inténtalo de nuevo.');
            return;
        }
        preview.document.write(pdfLoadingHtml(
            'Generando Padrón de Beneficiarios',
            `${MESES[month]} ${year} — Procesando datos del padrón, esto puede tardar unos segundos.`
        ));
        preview.document.close();
        try {
            const params = new URLSearchParams({
                association_id: associationId,
                month,
                year,
            });
            const res = await fetch(`${window.APP_URL || ''}/socios-beneficiarios/beneficiarios-padron?${params.toString()}`, {
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

    const associationOptions = (options.associations || []).map((a) => ({
        id: a.id,
        label: associationLabel(a, { includeCode: true, includeAddress: false }),
    }));

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Padrón de Beneficiarios del PVL"
            icon="fa-clipboard-list"
            iconClass="text-leaf"
            maxWidth="sm:max-w-2xl"
        >
            <div className="p-6 space-y-5">
                <p className="text-sm text-earth">Seleccione el comité y un padrón mensual cerrado. Los borradores no pueden emitirse como PDF oficial.</p>
                {closedPeriods.length === 0 && (
                    <div role="alert" className="rounded-xl border border-sun/40 bg-sun-light px-3 py-2 text-sm font-semibold text-[#8A4A00]">
                        <i className="fas fa-triangle-exclamation mr-2" aria-hidden="true" />Aún no hay padrones cerrados disponibles para generar.
                    </div>
                )}
                <div>
                    <label className="block text-sm font-bold text-charcoal mb-2">
                        <i className="fas fa-building mr-1" /> Comité / Club de Madres *
                    </label>
                    <Combobox
                        value={associationId}
                        onChange={setAssociationId}
                        options={associationOptions}
                        placeholder="Seleccione un comité"
                    />
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-bold text-charcoal mb-2">
                            <i className="fas fa-calendar-alt mr-1" /> Mes *
                        </label>
                        <select value={month} onChange={(e) => setMonth(e.target.value)} disabled={months.length === 0} className={`${selectCls} disabled:cursor-not-allowed disabled:bg-fog disabled:opacity-60`}>
                            {months.map((period) => <option key={`${period.year}-${period.month}`} value={period.month}>{MESES[Number(period.month)]}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-charcoal mb-2">
                            <i className="fas fa-calendar mr-1" /> Año *
                        </label>
                        <select value={year} onChange={(e) => {
                            const nextYear = e.target.value;
                            setYear(nextYear);
                            const firstMonth = closedPeriods.find((period) => period.year === nextYear)?.month;
                            if (firstMonth) setMonth(firstMonth);
                        }} disabled={years.length === 0} className={`${selectCls} disabled:cursor-not-allowed disabled:bg-fog disabled:opacity-60`}>
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
                        Cancelar
                    </button>
                    <button
                        type="button"
                        onClick={handleGenerate}
                        disabled={loading || !selectedPeriodExists}
                        className="btn-primary flex-1 disabled:opacity-60"
                    >
                        <i className={`fas ${loading ? 'fa-spinner fa-spin' : 'fa-file-pdf'} mr-2`} />
                        Generar Padrón de Beneficiarios
                    </button>
                </div>
            </div>
        </Modal>
    );
}
