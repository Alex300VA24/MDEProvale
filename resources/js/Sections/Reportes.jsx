import ReportGeneratorTab from './reportes/ReportGeneratorTab';

export default function Reportes() {
    return (
        <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
            <div className="px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat">
                <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                    <i className="fas fa-chart-bar text-leaf" /> Generador de Reportes
                </h3>
                <p className="text-earth text-xs sm:text-sm mt-1">
                    Genera reportes en formato padrón, personalizando entidades, columnas y filtros del sistema.
                </p>
            </div>

            <div className="p-4 sm:p-6">
                <ReportGeneratorTab />
            </div>
        </div>
    );
}
