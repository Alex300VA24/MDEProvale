import { useEffect, useRef, useState } from 'react';
import { useRuntimeState } from '../RuntimeState';
import http from '../http';
import SociosTab from './socios/SociosTab';
import PadronModal from './socios/PadronModal';
import PdfLinkButton from '../Components/PdfLinkButton';
import MonthlyRosterPanel from './socios/MonthlyRosterPanel';

const BASE = '/api/dashboard/socios-beneficiarios';

const HEADERS = {
    registro: {
        icon: 'fa-people-roof',
        title: 'Registro de Socios y Beneficiarios',
        description: 'Administra cada familia desde una ficha única de socio y beneficiario.',
    },
    padrones: {
        icon: 'fa-clipboard-list',
        title: 'Padrones',
        description: 'Genera, cierra y consulta la fotografía histórica de cada mes.',
    },
};

export default function SociosBeneficiarios({ initialAction }) {
    const { modules } = useRuntimeState();
    const permissionOf = (slug) => {
        const permission = (modules ?? []).find((item) => item.slug === slug);
        return {
            view: !!permission?.can_view,
            create: !!permission?.can_create,
            edit: !!permission?.can_edit,
            del: !!permission?.can_delete,
        };
    };
    const canRegistro = permissionOf('registro-socios-beneficiarios');
    const canPadrones = permissionOf('padrones');

    const [options, setOptions] = useState(null);
    const [optionsError, setOptionsError] = useState(false);
    const [padronOpen, setPadronOpen] = useState(false);
    const [activeRoster, setActiveRoster] = useState(null);
    const [rosters, setRosters] = useState([]);
    const [rosterRefreshKey, setRosterRefreshKey] = useState(0);
    const sociosRef = useRef(null);
    const initialTab = initialAction === 'beneficiarios-padron' && canPadrones.view
        ? 'padrones'
        : canRegistro.view ? 'registro' : 'padrones';
    const [tab, setTab] = useState(initialTab);

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
        if (options && initialAction === 'beneficiarios-padron' && canPadrones.view) {
            setTab('padrones');
            setPadronOpen(true);
        }
    }, [options, initialAction, canPadrones.view]);

    const header = HEADERS[tab];

    return (
        <>
            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
                <div className="px-4 sm:px-6 py-4 sm:py-5">
                    <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                        <i className={`fas ${header.icon} text-leaf`} aria-hidden="true" /> {header.title}
                    </h3>
                    <p className="text-earth text-xs sm:text-sm mt-1">{header.description}</p>
                </div>

                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 border-b-2 border-wheat gap-3">
                    <div className="flex items-center gap-1 overflow-x-auto max-w-full">
                        {canRegistro.view && <TabButton active={tab === 'registro'} icon="fa-people-roof" onClick={() => setTab('registro')}>Socios y Beneficiarios</TabButton>}
                        {canPadrones.view && <TabButton active={tab === 'padrones'} icon="fa-clipboard-list" onClick={() => setTab('padrones')}>Padrones</TabButton>}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {tab === 'registro' && canRegistro.view && (
                            <PdfLinkButton
                                href={`${window.APP_URL}/socios-beneficiarios/beneficiarios-imprimir`}
                                loadingTitle="Generando Ficha de Beneficiario"
                                icon="fa-print"
                                className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"
                            >
                                Abrir Ficha Beneficiario
                            </PdfLinkButton>
                        )}
                        {tab === 'registro' && canRegistro.create && (
                            <button type="button" onClick={() => sociosRef.current?.openCreate()} className="btn-primary flex items-center gap-2 text-xs sm:text-sm">
                                <i className="fas fa-plus" aria-hidden="true" />Registrar Socio y Beneficiario
                            </button>
                        )}
                        {tab === 'padrones' && canPadrones.view && (
                            <button type="button" onClick={() => setPadronOpen(true)} className="btn-secondary flex items-center gap-2 text-xs sm:text-sm">
                                <i className="fas fa-file-pdf" aria-hidden="true" />Generar Padrón
                            </button>
                        )}
                    </div>
                </div>

                <div className="p-4 sm:p-6">
                    {optionsError && (
                        <div className="mb-3 text-xs text-clay bg-clay-light rounded-lg px-3 py-2">
                            <i className="fas fa-exclamation-triangle mr-1" /> No se pudieron cargar algunas opciones de filtro.
                        </div>
                    )}
                    {canRegistro.view && (
                        <div className={tab === 'registro' ? '' : 'hidden'}>
                            <SociosTab
                                ref={sociosRef}
                                options={options ?? {}}
                                can={canRegistro}
                                monthlyRoster={activeRoster}
                                onRosterChanged={() => setRosterRefreshKey((current) => current + 1)}
                            />
                        </div>
                    )}
                    {canPadrones.view && (
                        <div className={tab === 'padrones' ? '' : 'hidden'}>
                            <MonthlyRosterPanel can={canPadrones} onActiveChange={setActiveRoster} onRosterDataChange={setRosters} refreshKey={rosterRefreshKey} />
                        </div>
                    )}
                </div>
            </div>

            <PadronModal open={padronOpen} onClose={() => setPadronOpen(false)} options={options ?? {}} rosters={rosters} />
        </>
    );
}

function TabButton({ active, icon, onClick, children }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${active ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'}`}
        >
            <i className={`fas ${icon}`} aria-hidden="true" />{children}
        </button>
    );
}
