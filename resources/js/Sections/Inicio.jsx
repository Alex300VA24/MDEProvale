import { useEffect, useState } from 'react';
import http from '../http';

const BASE = '/api/dashboard/inicio';

function KpiCard({ icon, iconClass, label, value, hint }) {
    return (
        <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm p-5 flex items-start gap-4">
            <div className={`w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 ${iconClass}`}>
                <i className={`fas ${icon} text-lg`} />
            </div>
            <div>
                <p className="text-[11px] font-bold text-earth uppercase tracking-wider">{label}</p>
                <p className="text-2xl font-extrabold text-charcoal">{value}</p>
                {hint && <p className="text-xs text-earth mt-1">{hint}</p>}
            </div>
        </div>
    );
}

export default function Inicio() {
    const [kpis, setKpis] = useState(null);
    const [error, setError] = useState(false);

    useEffect(() => {
        let active = true;
        (async () => {
            try {
                const res = await http.get(`${BASE}/kpis`);
                if (active) setKpis(res.data);
            } catch {
                if (active) setError(true);
            }
        })();
        return () => {
            active = false;
        };
    }, []);

    if (error) {
        return (
            <div className="empty-state">
                <i className="fas fa-exclamation-triangle" />
                <p>No se pudieron cargar los indicadores. Recarga la página.</p>
            </div>
        );
    }

    if (!kpis) {
        return (
            <div className="flex items-center justify-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin mr-2" /> Cargando indicadores...
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <h3 className="font-extrabold text-charcoal text-lg sm:text-xl flex items-center gap-3 mb-4">
                    <i className="fas fa-chart-line text-leaf" /> Panel General
                </h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <KpiCard
                        icon="fa-user-friends"
                        iconClass="bg-leaf-light text-leaf"
                        label="Socios Activos"
                        value={kpis.socios_activos}
                    />
                    <KpiCard
                        icon="fa-child"
                        iconClass="bg-sky-light text-[#0284C7]"
                        label="Beneficiarios Activos"
                        value={kpis.beneficiarios_activos}
                    />
                    <KpiCard
                        icon="fa-people-roof"
                        iconClass="bg-sun-light text-[#D97706]"
                        label="Comités Activos"
                        value={kpis.comites_activos}
                    />
                    <KpiCard
                        icon="fa-triangle-exclamation"
                        iconClass="bg-clay-light text-clay"
                        label="Productos en Stock Crítico"
                        value={kpis.productos_stock_critico}
                        hint="Disponibilidad ≤ 10 unidades"
                    />
                    <KpiCard
                        icon="fa-file-invoice"
                        iconClass="bg-leaf-light text-leaf"
                        label="Pecosas del Mes"
                        value={kpis.pecosas_mes_actual}
                    />
                    <KpiCard
                        icon="fa-utensils"
                        iconClass="bg-sky-light text-[#0284C7]"
                        label="Ración Vigente"
                        value={kpis.racion_activa ? `${kpis.racion_activa.racion_leche_militros} ml / ${kpis.racion_activa.racion_hojuelas_gramos} g` : 'Sin configurar'}
                        hint={kpis.racion_activa ? `Año ${kpis.racion_activa.year}` : 'Configure una ración en Mantenimiento'}
                    />
                </div>
            </div>
        </div>
    );
}
