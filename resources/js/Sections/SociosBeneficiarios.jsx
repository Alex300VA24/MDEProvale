import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import SociosTab from './socios/SociosTab';
import PadronModal from './socios/PadronModal';
import PdfLinkButton from '../Components/PdfLinkButton';

const BASE = '/api/dashboard/socios-beneficiarios';

export default function SociosBeneficiarios({ initialAction }) {
    const { modules } = usePage().props;
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
                <div className="px-4 sm:px-6 py-4 sm:py-5">
                    <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                        <i className="fas fa-people-roof text-leaf" /> Socios y Beneficiarios
                    </h3>
                    <p className="text-earth text-xs sm:text-sm mt-1">Administra cada familia desde una ficha única de socio y beneficiario.</p>
                </div>

                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 border-b-2 border-wheat gap-3">
                    <div>
                        <p className="font-bold text-charcoal text-sm">Padrón familiar</p>
                        <p className="text-xs text-earth">Socio titular con sus beneficiarios asociados</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <button type="button" onClick={() => setPadronOpen(true)} className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"><i className="fas fa-clipboard-list" aria-hidden="true" />Generar Padrón</button>
                        <PdfLinkButton href={`${window.APP_URL}/socios-beneficiarios/beneficiarios-imprimir`} loadingTitle="Generando Ficha de Beneficiario" icon="fa-print" className="btn-secondary flex items-center gap-2 text-xs sm:text-sm disabled:opacity-60">Ficha Beneficiario</PdfLinkButton>
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
