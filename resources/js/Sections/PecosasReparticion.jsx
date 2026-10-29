import { useEffect, useRef, useState } from 'react';
import { useRuntimeState } from '../RuntimeState';
import http from '../http';
import PecosasTab from './productos/PecosasTab';
import PecosasPdfMensualModal from './productos/PecosasPdfMensualModal';
import ReparticionTab from './movimientos/ReparticionTab';
import PdfLinkButton from '../Components/PdfLinkButton';

const BASE = '/api/dashboard/productos-pecosas';
const HEADERS = {
    pecosas: { icon: 'fa-file-lines', title: 'Pecosas', description: 'Administra las pecosas registradas y sus documentos.' },
    reparticion: { icon: 'fa-truck-ramp-box', title: 'Repartición', description: 'Calcula, guarda y exporta la repartición mensual.' },
};

export default function PecosasReparticion({ initialAction, onNavigate }) {
    const { modules } = useRuntimeState();
    const pecosaModule = (modules ?? []).find((module) => module.slug === 'pecosas');
    const movementModule = (modules ?? []).find((module) => module.slug === 'movimientos');
    const canPecosas = { view: !!pecosaModule?.can_view, create: !!pecosaModule?.can_create, edit: !!pecosaModule?.can_edit, del: !!pecosaModule?.can_delete };
    const canReparticion = { view: !!movementModule?.can_view, create: !!movementModule?.can_create, edit: !!movementModule?.can_edit, del: !!movementModule?.can_delete };
    const [tab, setTab] = useState(initialAction === 'reparticion' && canReparticion.view ? 'reparticion' : canPecosas.view ? 'pecosas' : 'reparticion');
    const [options, setOptions] = useState(null);
    const [optionsError, setOptionsError] = useState(false);
    const [pdfMensualOpen, setPdfMensualOpen] = useState(false);
    const pecosasRef = useRef(null);

    useEffect(() => {
        if (!canPecosas.view) return;
        let active = true;
        http.get(`${BASE}/pecosas/options`)
            .then((response) => { if (active) setOptions(response.data); })
            .catch(() => { if (active) setOptionsError(true); });
        return () => { active = false; };
    }, [canPecosas.view]);

    useEffect(() => {
        if (!options || initialAction !== 'new-pecosa' || !canPecosas.create) return;
        setTab('pecosas');
        pecosasRef.current?.openCreate();
    }, [options, initialAction, canPecosas.create]);

    const header = HEADERS[tab];

    return (
        <>
            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
                <div className="px-4 sm:px-6 py-4 sm:py-5">
                    <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3"><i className={`fas ${header.icon} text-leaf`} />{header.title}</h3>
                    <p className="text-earth text-xs sm:text-sm mt-1">{header.description}</p>
                </div>

                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 border-b-2 border-wheat gap-3">
                    <div className="flex items-center gap-1 overflow-x-auto max-w-full">
                        {canPecosas.view && <TabButton active={tab === 'pecosas'} icon="fa-file-lines" onClick={() => setTab('pecosas')}>Pecosas</TabButton>}
                        {canReparticion.view && <TabButton active={tab === 'reparticion'} icon="fa-truck-ramp-box" onClick={() => setTab('reparticion')}>Repartición</TabButton>}
                    </div>
                    {tab === 'pecosas' && <div className="flex flex-wrap items-center gap-2">
                        <PdfLinkButton href={`${window.APP_URL || ''}/productos-pecosas/acta-supervision`} loadingTitle="Generando Acta de Supervisión" icon="fa-clipboard-check" className="btn-secondary flex items-center gap-2 text-xs sm:text-sm disabled:opacity-60">Acta de Supervisión</PdfLinkButton>
                        <button type="button" onClick={() => setPdfMensualOpen(true)} className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"><i className="fas fa-file-pdf" />Generar Pecosas</button>
                        {canPecosas.create && <button type="button" onClick={() => pecosasRef.current?.openCreate()} className="btn-primary flex items-center gap-2 text-xs sm:text-sm"><i className="fas fa-plus" />Registrar Pecosa</button>}
                    </div>}
                </div>

                <div className="p-4 sm:p-6">
                    {optionsError && <div className="mb-3 text-xs text-clay bg-clay-light rounded-lg px-3 py-2"><i className="fas fa-exclamation-triangle mr-1" />No se pudieron cargar algunas opciones.</div>}
                    {canPecosas.view && <div className={tab === 'pecosas' ? '' : 'hidden'}><PecosasTab ref={pecosasRef} options={options ?? {}} can={canPecosas} /></div>}
                    {canReparticion.view && tab === 'reparticion' && <ReparticionTab can={canReparticion} onGoToMovimientos={() => onNavigate?.('productos', 'ingresos')} />}
                </div>
            </div>
            {canPecosas.view && <PecosasPdfMensualModal open={pdfMensualOpen} onClose={() => setPdfMensualOpen(false)} />}
        </>
    );
}

function TabButton({ active, icon, onClick, children }) {
    return <button type="button" onClick={onClick} className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${active ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'}`}><i className={`fas ${icon}`} />{children}</button>;
}
