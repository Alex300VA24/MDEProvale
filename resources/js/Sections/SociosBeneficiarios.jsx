import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import SociosTab from './socios/SociosTab';
import BeneficiariosTab from './socios/BeneficiariosTab';
import PersonasTab from './socios/PersonasTab';
import PadronModal from './socios/PadronModal';

const BASE = '/api/dashboard/socios-beneficiarios';

const TABS = [
    { key: 'socios', label: 'Socios', icon: 'fa-user-friends' },
    { key: 'beneficiarios', label: 'Beneficiarios', icon: 'fa-hand-holding-heart' },
    { key: 'personas', label: 'Personas', icon: 'fa-users' },
];

const HEADERS = {
    socios: { icon: 'fa-user-friends', title: 'Socios y Beneficiarios' },
    beneficiarios: { icon: 'fa-hand-holding-heart', title: 'Gestión de Beneficiarios' },
    personas: { icon: 'fa-users', title: 'Personas Registradas' },
};

export default function SociosBeneficiarios() {
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
    const personasRef = useRef(null);

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

    const header = HEADERS[tab];

    return (
        <>
            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
                    <h3 className="font-extrabold text-charcoal text-lg sm:text-xl flex items-center gap-3">
                        <i className={`fas ${header.icon} text-leaf`} /> {header.title}
                    </h3>
                    <div className="flex flex-wrap items-center gap-2">
                        {can.create && tab === 'socios' && (
                            <button
                                type="button"
                                onClick={() => sociosRef.current?.openCreate()}
                                className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                            >
                                <i className="fas fa-plus" /> Nuevo Socio
                            </button>
                        )}
                        {can.create && tab === 'beneficiarios' && (
                            <button
                                type="button"
                                onClick={() => beneficiariosRef.current?.openCreate()}
                                className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                            >
                                <i className="fas fa-plus" /> Nuevo Beneficiario
                            </button>
                        )}
                        {can.create && tab === 'personas' && (
                            <button
                                type="button"
                                onClick={() => personasRef.current?.openCreate()}
                                className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                            >
                                <i className="fas fa-plus" /> Nueva Persona
                            </button>
                        )}
                        <a
                            href="/socios-beneficiarios/beneficiarios-imprimir"
                            target="_blank"
                            rel="noreferrer"
                            className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"
                        >
                            <i className="fas fa-print" /> Ficha
                        </a>
                        <button
                            type="button"
                            onClick={() => setPadronOpen(true)}
                            className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"
                        >
                            <i className="fas fa-clipboard-list" /> Padrón
                        </button>
                    </div>
                </div>

                <div className="flex border-b-2 border-wheat bg-cream px-2 sm:px-4 gap-1 overflow-x-auto">
                    {TABS.map((t) => (
                        <button
                            key={t.key}
                            type="button"
                            onClick={() => setTab(t.key)}
                            className={`flex items-center gap-2 px-3 sm:px-5 py-3 text-xs sm:text-sm font-bold border-b-2 -mb-px whitespace-nowrap transition-all ${
                                tab === t.key
                                    ? 'text-leaf border-leaf'
                                    : 'text-earth border-transparent hover:text-charcoal'
                            }`}
                        >
                            <i className={`fas ${t.icon}`} /> {t.label}
                        </button>
                    ))}
                </div>

                <div className="p-4 sm:p-6">
                    {optionsError ? (
                        <div className="empty-state">
                            <i className="fas fa-exclamation-triangle" />
                            <p>No se pudieron cargar los datos de la sección. Recarga la página.</p>
                        </div>
                    ) : !options ? (
                        <div className="flex items-center justify-center py-10 text-earth">
                            <i className="fas fa-spinner fa-spin mr-2" /> Cargando...
                        </div>
                    ) : (
                        <>
                            <div className={tab === 'socios' ? '' : 'hidden'}>
                                <SociosTab ref={sociosRef} options={options} can={can} />
                            </div>
                            <div className={tab === 'beneficiarios' ? '' : 'hidden'}>
                                <BeneficiariosTab ref={beneficiariosRef} options={options} can={can} />
                            </div>
                            <div className={tab === 'personas' ? '' : 'hidden'}>
                                <PersonasTab ref={personasRef} options={options} can={can} />
                            </div>
                        </>
                    )}
                </div>
            </div>

            <PadronModal open={padronOpen} onClose={() => setPadronOpen(false)} options={options ?? {}} />
        </>
    );
}
