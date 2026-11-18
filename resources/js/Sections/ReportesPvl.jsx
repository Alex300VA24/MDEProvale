import { useRuntimeState } from '../RuntimeState';
import InformeTrimestral from './reportes/InformeTrimestral';

export default function ReportesPvl() {
    const { modules } = useRuntimeState();
    const permission = (modules || []).find((item) => item.slug === 'reportes');
    const canView = !!permission?.can_view;

    if (!canView) return <div className="empty-state"><i className="fas fa-lock" /><p>No tiene acceso a este módulo.</p></div>;

    return (
        <div className="overflow-hidden rounded-2xl border-2 border-mist bg-white shadow-sm">
            <header className="px-4 py-5 sm:px-6">
                <h1 className="flex items-center gap-3 font-heading text-xl font-extrabold text-navy sm:text-2xl"><i className="fas fa-file-shield text-blue" /> Reportes</h1>
                <p className="mt-1 max-w-3xl text-sm text-earth">Informe trimestral con gráficos y análisis de IA, en formato de informe para Contraloría, construido con lo que registra el sistema. Descárgalo en Word o PDF.</p>
            </header>
            <div className="border-t-2 border-mist"><InformeTrimestral canView={canView} /></div>
        </div>
    );
}
