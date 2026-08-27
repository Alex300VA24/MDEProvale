import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import ComitesTab from './comites/ComitesTab';
import ReconocimientosTab from './comites/ReconocimientosTab';
import PadronModal from './comites/PadronModal';
import MoreActionsMenu from '../Components/MoreActionsMenu';

const BASE = '/api/dashboard/club-madres';

const HEADERS = {
    comites: { icon: 'fa-people-roof', title: 'Gestión de Clubes de Madres', description: 'Administra los clubes de madres y comités registrados.' },
    reconocimientos: { icon: 'fa-scroll', title: 'Gestión de Reconocimientos', description: 'Administra los reconocimientos otorgados a los clubes.' },
};

export default function ClubReconocimientos({ initialAction }) {
    const { modules } = usePage().props;
    const modComites = (modules ?? []).find((m) => m.slug === 'club-madres');
    const modReconocimientos = (modules ?? []).find((m) => m.slug === 'reconocimientos');

    const can = {
        comites: {
            view: !!modComites?.can_view,
            create: !!modComites?.can_create,
            edit: !!modComites?.can_edit,
            del: !!modComites?.can_delete,
        },
        reconocimientos: {
            view: !!modReconocimientos?.can_view,
            create: !!modReconocimientos?.can_create,
            edit: !!modReconocimientos?.can_edit,
            del: !!modReconocimientos?.can_delete,
        },
    };

    const defaultTab = can.comites.view ? 'comites' : 'reconocimientos';
    const [tab, setTab] = useState(defaultTab);
    const [options, setOptions] = useState(null);
    const [optionsError, setOptionsError] = useState(false);
    const [padronOpen, setPadronOpen] = useState(false);
    const comitesRef = useRef(null);
    const reconocimientosRef = useRef(null);

    useEffect(() => {
        let active = true;
        (async () => {
            try {
                const reqs = [];
                if (can.comites.view) reqs.push(http.get(`${BASE}/clubs/options`));
                if (can.reconocimientos.view) reqs.push(http.get(`${BASE}/reconocimientos/options`));
                const results = await Promise.all(reqs);
                if (!active) return;
                const comitesOpts = can.comites.view ? results.shift() : null;
                const reconOpts = can.reconocimientos.view ? results.shift() : null;
                setOptions({
                    states: comitesOpts?.data?.states ?? reconOpts?.data?.states ?? [],
                    place_sectors: comitesOpts?.data?.place_sectors ?? [],
                    type_premises: comitesOpts?.data?.type_premises ?? [],
                    resolutions: comitesOpts?.data?.resolutions ?? [],
                    years: reconOpts?.data?.years ?? [],
                });
            } catch {
                if (active) setOptionsError(true);
            }
        })();
        return () => {
            active = false;
        };
    }, [can.comites.view, can.reconocimientos.view]);

    useEffect(() => {
        if (initialAction !== 'comites-padron' || !can.comites.view) return;
        setTab('comites');
        setPadronOpen(true);
    }, [initialAction, can.comites.view]);

    const header = HEADERS[tab];

    return (
        <>
            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
                <div className="px-4 sm:px-6 py-4 sm:py-5">
                    <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                        <i className={`fas ${header.icon} text-leaf`} /> {header.title}
                    </h3>
                    <p className="text-earth text-xs sm:text-sm mt-1">{header.description}</p>
                </div>

                {(can.comites.view || can.reconocimientos.view) && (
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 border-b-2 border-wheat gap-3">
                        <div className="flex items-center gap-1 overflow-x-auto">
                            {can.comites.view && (
                                <button
                                    type="button"
                                    onClick={() => setTab('comites')}
                                    className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${
                                        tab === 'comites' ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'
                                    }`}
                                >
                                    <i className="fas fa-people-roof" /> Comités
                                </button>
                            )}
                            {can.reconocimientos.view && (
                                <button
                                    type="button"
                                    onClick={() => setTab('reconocimientos')}
                                    className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${
                                        tab === 'reconocimientos' ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'
                                    }`}
                                >
                                    <i className="fas fa-scroll" /> Reconocimientos
                                </button>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            {can.comites.view && tab === 'comites' && can.comites.create && (
                                <button
                                    type="button"
                                    onClick={() => comitesRef.current?.openCreate()}
                                    className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                                >
                                    <i className="fas fa-plus" /> Registrar Comité
                                </button>
                            )}
                            {can.reconocimientos.view && tab === 'reconocimientos' && can.reconocimientos.create && (
                                <button
                                    type="button"
                                    onClick={() => reconocimientosRef.current?.openCreate()}
                                    className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                                >
                                    <i className="fas fa-plus" /> Registrar Reconocimiento
                                </button>
                            )}
                            {can.comites.view && (
                                <MoreActionsMenu
                                    items={[{ icon: 'fa-file-pdf', label: 'Padrón', onClick: () => setPadronOpen(true) }]}
                                />
                            )}
                        </div>
                    </div>
                )}

                <div className="p-4 sm:p-6">
                    {optionsError && (
                        <div className="mb-3 text-xs text-clay bg-clay-light rounded-lg px-3 py-2">
                            <i className="fas fa-exclamation-triangle mr-1" /> No se pudieron cargar algunas opciones de filtro.
                        </div>
                    )}
                    {can.comites.view && (
                        <div className={tab === 'comites' ? '' : 'hidden'}>
                            <ComitesTab ref={comitesRef} options={options ?? {}} can={can.comites} />
                        </div>
                    )}
                    {can.reconocimientos.view && (
                        <div className={tab === 'reconocimientos' ? '' : 'hidden'}>
                            <ReconocimientosTab ref={reconocimientosRef} options={options ?? {}} can={can.reconocimientos} />
                        </div>
                    )}
                </div>
            </div>

            <PadronModal open={padronOpen} onClose={() => setPadronOpen(false)} />
        </>
    );
}
