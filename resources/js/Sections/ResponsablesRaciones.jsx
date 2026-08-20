import { useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import ResponsablesTab from './responsables-raciones/ResponsablesTab';
import RacionesTab from './responsables-raciones/RacionesTab';

const TABS = [
    { key: 'responsables', label: 'Responsables', icon: 'fa-user-tie' },
    { key: 'raciones', label: 'Raciones', icon: 'fa-utensils' },
];

const HEADERS = {
    responsables: { icon: 'fa-user-tie', title: 'Responsables de Almacén', description: 'Administra los responsables del programa.' },
    raciones: { icon: 'fa-utensils', title: 'Raciones por Año', description: 'Administra las raciones registradas por año.' },
};

export default function ResponsablesRaciones() {
    const { modules } = usePage().props;
    const mod = (modules ?? []).find((m) => m.slug === 'responsables-raciones');
    const can = {
        view: !!mod?.can_view,
        create: !!mod?.can_create,
        edit: !!mod?.can_edit,
        del: !!mod?.can_delete,
    };

    const [tab, setTab] = useState('responsables');
    const racionesRef = useRef(null);

    if (!can.view) {
        return (
            <div className="empty-state">
                <i className="fas fa-lock" />
                <p>No tiene acceso a este módulo.</p>
            </div>
        );
    }

    const header = HEADERS[tab];

    return (
        <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
            <div className="px-4 sm:px-6 py-4 sm:py-5">
                <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                    <i className={`fas ${header.icon} text-leaf`} /> {header.title}
                </h3>
                <p className="text-earth text-xs sm:text-sm mt-1">{header.description}</p>
            </div>

            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 border-b-2 border-wheat gap-3">
                <div className="flex items-center gap-1 overflow-x-auto">
                    {TABS.map((t) => (
                        <button
                            key={t.key}
                            type="button"
                            onClick={() => setTab(t.key)}
                            className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${
                                tab === t.key
                                    ? 'text-leaf border-leaf'
                                    : 'text-earth border-transparent hover:text-charcoal'
                            }`}
                        >
                            <i className={`fas ${t.icon}`} /> {t.label}
                        </button>
                    ))}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {can.create && tab === 'raciones' && (
                        <button
                            type="button"
                            onClick={() => racionesRef.current?.openCreate()}
                            className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                        >
                            <i className="fas fa-plus" /> Nueva Ración
                        </button>
                    )}
                </div>
            </div>

            <div className="p-4 sm:p-6">
                <div className={tab === 'responsables' ? '' : 'hidden'}>
                    <ResponsablesTab can={can} />
                </div>
                <div className={tab === 'raciones' ? '' : 'hidden'}>
                    <RacionesTab ref={racionesRef} can={can} />
                </div>
            </div>
        </div>
    );
}
