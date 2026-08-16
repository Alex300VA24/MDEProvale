import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import KardexTab from './movimientos/KardexTab';
import ReparticionTab from './movimientos/ReparticionTab';

const BASE = '/api/dashboard/movimientos';

const HEADERS = {
    kardex: { icon: 'fa-right-left', title: 'Kardex de Movimientos' },
    reparticion: { icon: 'fa-truck', title: 'Repartición Mensual' },
};

export default function Movimientos() {
    const { modules } = usePage().props;
    const mod = (modules ?? []).find((m) => m.slug === 'movimientos');

    const can = {
        view: !!mod?.can_view,
        create: !!mod?.can_create,
        edit: !!mod?.can_edit,
        del: !!mod?.can_delete,
    };

    const [tab, setTab] = useState('kardex');
    const [options, setOptions] = useState(null);
    const [optionsError, setOptionsError] = useState(false);
    const kardexRef = useRef(null);

    useEffect(() => {
        if (!can.view) return;
        let active = true;
        (async () => {
            try {
                const res = await http.get(`${BASE}/transactions/options`);
                if (!active) return;
                setOptions(res.data);
            } catch {
                if (active) setOptionsError(true);
            }
        })();
        return () => {
            active = false;
        };
    }, [can.view]);

    const header = HEADERS[tab];

    if (!can.view) {
        return (
            <div className="empty-state">
                <i className="fas fa-lock" />
                <p>No tiene acceso a este módulo.</p>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
            <div className="px-4 sm:px-6 py-4 sm:py-5">
                <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                    <i className={`fas ${header.icon} text-leaf`} /> {header.title}
                </h3>
            </div>

            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 border-b-2 border-wheat gap-3">
                <div className="flex flex-wrap items-center gap-2">
                    {tab === 'kardex' && can.create && (
                        <button
                            type="button"
                            onClick={() => kardexRef.current?.openCreate()}
                            className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                        >
                            <i className="fas fa-plus" /> Nuevo Ingreso
                        </button>
                    )}
                </div>

                <div className="flex items-center gap-1 overflow-x-auto">
                    <button
                        type="button"
                        onClick={() => setTab('kardex')}
                        className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${
                            tab === 'kardex' ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'
                        }`}
                    >
                        <i className="fas fa-right-left" /> Kardex
                    </button>
                    <button
                        type="button"
                        onClick={() => setTab('reparticion')}
                        className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${
                            tab === 'reparticion' ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'
                        }`}
                    >
                        <i className="fas fa-truck" /> Repartición
                    </button>
                </div>
            </div>

            <div className="p-4 sm:p-6">
                {tab === 'kardex' && (
                    optionsError ? (
                        <div className="empty-state">
                            <i className="fas fa-exclamation-triangle" />
                            <p>No se pudieron cargar los datos de la sección. Recarga la página.</p>
                        </div>
                    ) : !options ? (
                        <div className="flex items-center justify-center py-10 text-earth">
                            <i className="fas fa-spinner fa-spin mr-2" /> Cargando...
                        </div>
                    ) : (
                        <KardexTab ref={kardexRef} options={options} can={can} />
                    )
                )}

                {tab === 'reparticion' && <ReparticionTab />}
            </div>
        </div>
    );
}
