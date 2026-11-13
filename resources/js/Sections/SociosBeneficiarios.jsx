import { useEffect, useRef, useState } from 'react';
import { useRuntimeState } from '../RuntimeState';
import http from '../http';
import SociosTab from './socios/SociosTab';
import PadronModal from './socios/PadronModal';
import RowActionsMenu from '../Components/RowActionsMenu';

const BASE = '/api/dashboard/socios-beneficiarios';

export default function SociosBeneficiarios({ initialAction }) {
    const { modules } = useRuntimeState();
    const module = (modules ?? []).find((m) => m.slug === 'socios-beneficiarios');
    const can = {
        create: !!module?.can_create,
        edit: !!module?.can_edit,
        del: !!module?.can_delete,
    };

    const [options, setOptions] = useState(null);
    const [optionsError, setOptionsError] = useState(false);
    const [padronOpen, setPadronOpen] = useState(false);
    const sociosRef = useRef(null);

    useEffect(() => {
        let active = true;
        (async () => {
            try {
                const sociosRes = await http.get(`${BASE}/partners/options`);
                if (!active) return;
                setOptions(sociosRes.data ?? {});
            } catch {
                if (active) setOptionsError(true);
            }
        })();
        return () => {
            active = false;
        };
    }, []);

    useEffect(() => {
        if (options && initialAction === 'beneficiarios-padron') setPadronOpen(true);
    }, [options, initialAction]);

    return (
        <>
            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat">
                    <div>
                        <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                            <i className="fas fa-people-roof text-leaf" /> Socios y Beneficiarios
                        </h3>
                        <p className="text-earth text-xs sm:text-sm mt-1">Administra cada familia desde una ficha única de socio y beneficiario.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <RowActionsMenu
                            label="Acciones"
                            triggerText="Acciones"
                            triggerClassName="btn-secondary text-xs sm:text-sm"
                            items={[
                                { label: 'Generar Padrón', icon: 'fa-clipboard-list', onClick: () => setPadronOpen(true) },
                                { label: 'Abrir Ficha Beneficiario', icon: 'fa-print', pdf: { href: `${window.APP_URL}/socios-beneficiarios/beneficiarios-imprimir`, loadingTitle: 'Generando Ficha de Beneficiario' } },
                            ]}
                        />
                        {can.create && <button type="button" onClick={() => sociosRef.current?.openCreate()} className="btn-primary flex items-center gap-2 text-xs sm:text-sm"><i className="fas fa-plus" aria-hidden="true" />Registrar Socio y Beneficiario</button>}
                    </div>
                </div>

                <div className="p-4 sm:p-6">
                    {optionsError && (
                        <div className="mb-3 text-xs text-clay bg-clay-light rounded-lg px-3 py-2">
                            <i className="fas fa-exclamation-triangle mr-1" /> No se pudieron cargar algunas opciones de filtro.
                        </div>
                    )}
                    <SociosTab ref={sociosRef} options={options ?? {}} can={can} />
                </div>
            </div>

            <PadronModal open={padronOpen} onClose={() => setPadronOpen(false)} options={options ?? {}} />
        </>
    );
}
