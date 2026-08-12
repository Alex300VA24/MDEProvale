import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import ProductosTab from './productos/ProductosTab';
import PecosasTab from './productos/PecosasTab';

const BASE = '/api/dashboard/productos-pecosas';

const HEADERS = {
    pecosas: { icon: 'fa-file-alt', title: 'Gestión de Pecosas' },
    productos: { icon: 'fa-box', title: 'Gestión de Productos' },
};

export default function ProductosPecosas() {
    const { modules } = usePage().props;
    const modProductos = (modules ?? []).find((m) => m.slug === 'productos');
    const modPecosas = (modules ?? []).find((m) => m.slug === 'pecosas');

    const can = {
        productos: {
            view: !!modProductos?.can_view,
            create: !!modProductos?.can_create,
            edit: !!modProductos?.can_edit,
            del: !!modProductos?.can_delete,
        },
        pecosas: {
            view: !!modPecosas?.can_view,
            create: !!modPecosas?.can_create,
            edit: !!modPecosas?.can_edit,
            del: !!modPecosas?.can_delete,
        },
    };

    const defaultTab = can.pecosas.view ? 'pecosas' : 'productos';
    const [tab, setTab] = useState(defaultTab);
    const [options, setOptions] = useState(null);
    const [optionsError, setOptionsError] = useState(false);
    const pecosasRef = useRef(null);
    const productosRef = useRef(null);

    useEffect(() => {
        let active = true;
        (async () => {
            try {
                const reqs = [];
                if (can.productos.view) reqs.push(http.get(`${BASE}/products/options`));
                if (can.pecosas.view) reqs.push(http.get(`${BASE}/pecosas/options`));
                const results = await Promise.all(reqs);
                if (!active) return;
                const productsOpts = can.productos.view ? results.shift() : null;
                const pecosasOpts = can.pecosas.view ? results.shift() : null;
                setOptions({
                    states: productsOpts?.data?.states ?? pecosasOpts?.data?.states ?? [],
                    uoms: productsOpts?.data?.uoms ?? [],
                    associations: pecosasOpts?.data?.associations ?? [],
                    responsibles: pecosasOpts?.data?.responsibles ?? [],
                    detail_products: pecosasOpts?.data?.detail_products ?? [],
                });
            } catch {
                if (active) setOptionsError(true);
            }
        })();
        return () => {
            active = false;
        };
    }, [can.productos.view, can.pecosas.view]);

    const header = HEADERS[tab];

    return (
        <>
            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
                    <h3 className="font-extrabold text-charcoal text-lg sm:text-xl flex items-center gap-3">
                        <i className={`fas ${header.icon} text-leaf`} /> {header.title}
                    </h3>
                    <div className="flex flex-wrap items-center gap-2">
                        {can.pecosas.view && tab === 'pecosas' && can.pecosas.create && (
                            <button
                                type="button"
                                onClick={() => pecosasRef.current?.openCreate()}
                                className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                            >
                                <i className="fas fa-plus" /> Nueva Pecosa
                            </button>
                        )}
                        {can.productos.view && tab === 'productos' && can.productos.create && (
                            <button
                                type="button"
                                onClick={() => productosRef.current?.openCreate()}
                                className="btn-primary flex items-center gap-2 text-xs sm:text-sm"
                            >
                                <i className="fas fa-plus" /> Nuevo Producto
                            </button>
                        )}
                        {can.pecosas.view && tab === 'productos' && (
                            <button
                                type="button"
                                onClick={() => setTab('pecosas')}
                                className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"
                            >
                                <i className="fas fa-file-alt" /> Ver Pecosas
                            </button>
                        )}
                        {can.productos.view && tab === 'pecosas' && (
                            <button
                                type="button"
                                onClick={() => setTab('productos')}
                                className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"
                            >
                                <i className="fas fa-clipboard-list" /> Detalle Productos
                            </button>
                        )}
                    </div>
                </div>

                {(can.pecosas.view || can.productos.view) && (
                    <div className="flex border-b-2 border-wheat bg-cream px-2 sm:px-4 gap-1 overflow-x-auto">
                        {can.pecosas.view && (
                            <button
                                type="button"
                                onClick={() => setTab('pecosas')}
                                className={`flex items-center gap-2 px-3 sm:px-5 py-3 text-xs sm:text-sm font-bold border-b-2 -mb-px whitespace-nowrap transition-all ${
                                    tab === 'pecosas' ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'
                                }`}
                            >
                                <i className="fas fa-file-alt" /> Pecosas
                            </button>
                        )}
                        {can.productos.view && (
                            <button
                                type="button"
                                onClick={() => setTab('productos')}
                                className={`flex items-center gap-2 px-3 sm:px-5 py-3 text-xs sm:text-sm font-bold border-b-2 -mb-px whitespace-nowrap transition-all ${
                                    tab === 'productos' ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'
                                }`}
                            >
                                <i className="fas fa-box" /> Productos
                            </button>
                        )}
                    </div>
                )}

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
                            {can.pecosas.view && (
                                <div className={tab === 'pecosas' ? '' : 'hidden'}>
                                    <PecosasTab ref={pecosasRef} options={options} can={can.pecosas} />
                                </div>
                            )}
                            {can.productos.view && (
                                <div className={tab === 'productos' ? '' : 'hidden'}>
                                    <ProductosTab ref={productosRef} options={options} can={can.productos} />
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </>
    );
}
