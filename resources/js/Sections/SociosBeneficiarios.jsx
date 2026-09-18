import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import SociosTab from './socios/SociosTab';
import BeneficiariosTab from './socios/BeneficiariosTab';
import PadronModal from './socios/PadronModal';
import PdfLinkButton from '../Components/PdfLinkButton';

const BASE = '/api/dashboard/socios-beneficiarios';

const TABS = [
    { key: 'socios', label: 'Socios', icon: 'fa-user-friends' },
    { key: 'beneficiarios', label: 'Beneficiarios', icon: 'fa-hand-holding-heart' },
];

const HEADERS = {
    socios: { icon: 'fa-user-friends', title: 'Socios y Beneficiarios', description: 'Administra los socios registrados y sus beneficiarios.' },
    beneficiarios: { icon: 'fa-hand-holding-heart', title: 'Gestión de Beneficiarios', description: 'Administra los beneficiarios registrados en el sistema.' },
};

export default function SociosBeneficiarios({ initialAction }) {
    const { modules } = usePage().props;
    const module = (modules ?? []).find((m) => m.slug === 'socios-beneficiarios');
    const can = {
        create: !!module?.can_create,
        edit: !!module?.can_edit,
        del: !!module?.can_delete,
    };

    const [tab, setTab] = useState('socios');
    const [options, setOptions] = useState(null);
    const [optionsError, setOptionsError] = useState(false);
    const [padronOpen, setPadronOpen] = useState(false);
    const sociosRef = useRef(null);
    const beneficiariosRef = useRef(null);

    useEffect(() => {
        let active = true;
        (async () => {
            try {
                const [sociosRes, personasRes, beneficiariosRes] = await Promise.all([
                    http.get(`${BASE}/partners/options`),
                    http.get(`${BASE}/personas/options`),
                    http.get(`${BASE}/beneficiarios/options`),
                ]);
                if (!active) return;
                setOptions({
                    associations: sociosRes.data?.associations ?? [],
                    states: sociosRes.data?.states ?? [],
                    type_benefits: sociosRes.data?.type_benefits ?? [],
                    reason_disqualifications: sociosRes.data?.reason_disqualifications ?? [],
                    place_sectors: personasRes.data?.place_sectors ?? [],
                    partners: beneficiariosRes.data?.partners ?? [],
                    relationships: beneficiariosRes.data?.relationships ?? [],
                });
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

    const header = HEADERS[tab] || HEADERS.socios;

    const handlePartnerSaved = (partner) => {
        if (!partner?.id) return;

        setOptions((previous) => {
            if (!previous) return previous;
            const updatedPartner = { id: partner.id, name: partner.name };
            return {
                ...previous,
                partners: [
                    updatedPartner,
                    ...(previous.partners ?? []).filter((item) => item.id !== partner.id),
                ],
            };
        });
    };

    return (
        <>
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
                        {tab === 'socios' && (
                            <>
                                <button
                                    type="button"
                                    onClick={() => setPadronOpen(true)}
                                    className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"
                                >
                                    <i className="fas fa-clipboard-list" aria-hidden="true" /> Generar Padrón
                                </button>
                                {can.create && (
                                    <button
                                        type="button"
                                        onClick={() => sociosRef.current?.openCreate()}
                                        className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                                    >
                                        <i className="fas fa-plus" aria-hidden="true" /> Registrar Socio
                                    </button>
                                )}
                            </>
                        )}
                        {tab === 'beneficiarios' && (
                            <>
                                <PdfLinkButton
                                    href={`${window.APP_URL}/socios-beneficiarios/beneficiarios-imprimir`}
                                    loadingTitle="Generando Ficha de Beneficiario"
                                    icon="fa-print"
                                    className="btn-secondary flex items-center gap-2 text-xs sm:text-sm disabled:opacity-60"
                                >
                                    Ficha Beneficiario
                                </PdfLinkButton>
                                {can.create && (
                                    <button
                                        type="button"
                                        onClick={() => beneficiariosRef.current?.openCreate()}
                                        className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                                    >
                                        <i className="fas fa-plus" aria-hidden="true" /> Registrar Beneficiario
                                    </button>
                                )}
                            </>
                        )}
                    </div>
                </div>

                <div className="p-4 sm:p-6">
                    {optionsError && (
                        <div className="mb-3 text-xs text-clay bg-clay-light rounded-lg px-3 py-2">
                            <i className="fas fa-exclamation-triangle mr-1" /> No se pudieron cargar algunas opciones de filtro.
                        </div>
                    )}
                    <div className={tab === 'socios' ? '' : 'hidden'}>
                        <SociosTab
                            ref={sociosRef}
                            options={options ?? {}}
                            can={can}
                            onPartnerSaved={handlePartnerSaved}
                        />
                    </div>
                    <div className={tab === 'beneficiarios' ? '' : 'hidden'}>
                        <BeneficiariosTab ref={beneficiariosRef} options={options ?? {}} can={can} />
                    </div>
                </div>
            </div>

            <PadronModal open={padronOpen} onClose={() => setPadronOpen(false)} options={options ?? {}} />
        </>
    );
}
