import { useCallback, useEffect, useRef, useState } from 'react';
import { useRuntimeState } from '../RuntimeState';
import http from '../http';
import ProductosTab from './productos/ProductosTab';
import KardexTab from './movimientos/KardexTab';
import ProgramacionEntregaTab from './productos/ProgramacionEntregaTab';

const PRODUCT_BASE = '/api/dashboard/productos-pecosas';
const MOVEMENT_BASE = '/api/dashboard/movimientos';

const HEADERS = {
    productos: { icon: 'fa-box', title: 'Productos', description: 'Administra productos, unidades de medida y presentaciones.' },
    ingresos: { icon: 'fa-arrow-right-to-bracket', title: 'Registro de Ingreso', description: 'Registra y consulta ingresos de productos al almacén.' },
    programacion: { icon: 'fa-calendar-check', title: 'Programación de Entrega', description: 'Consulta la programación mensual de entrega por comité.' },
};

export default function ProductosProgramacion({ initialAction }) {
    const { modules } = useRuntimeState();
    // Cada pestaña es un submódulo con su propio permiso.
    const canOf = (slug) => {
        const module = (modules ?? []).find((m) => m.slug === slug);
        return { view: !!module?.can_view, create: !!module?.can_create, edit: !!module?.can_edit, del: !!module?.can_delete };
    };
    const canProducts = canOf('productos');
    const canMovements = canOf('registro-ingreso');
    const canProgramacion = canOf('programacion-entrega');
    const initialTab = (initialAction === 'ingresos' || initialAction === 'new-ingreso') && canMovements.view
        ? 'ingresos'
        : initialAction === 'programacion' && canProgramacion.view
            ? 'programacion'
            : canProducts.view ? 'productos' : canMovements.view ? 'ingresos' : 'programacion';
    const [tab, setTab] = useState(initialTab);
    const [productOptions, setProductOptions] = useState(null);
    const [movementOptions, setMovementOptions] = useState(null);
    const [optionsError, setOptionsError] = useState(false);
    const productsRef = useRef(null);
    const kardexRef = useRef(null);

    const loadOptions = useCallback(async () => {
        try {
            const [products, movements] = await Promise.all([
            canProducts.view ? http.get(`${PRODUCT_BASE}/products/options`) : Promise.resolve(null),
            canMovements.view ? http.get(`${MOVEMENT_BASE}/transactions/options`) : Promise.resolve(null),
            ]);
            setProductOptions(products?.data ?? {});
            setMovementOptions(movements?.data ?? {});
            setOptionsError(false);
        } catch {
            setOptionsError(true);
        }
    }, [canProducts.view, canMovements.view]);

    useEffect(() => {
        loadOptions();
    }, [loadOptions]);

    useEffect(() => {
        if ((initialAction === 'ingresos' || initialAction === 'new-ingreso') && canMovements.view) setTab('ingresos');
        if (initialAction === 'programacion' && canProgramacion.view) setTab('programacion');
    }, [initialAction, canMovements.view, canProgramacion.view]);

    // Acción rápida "Nuevo ingreso": abre el modal de registro una vez cargadas las opciones.
    useEffect(() => {
        if (initialAction !== 'new-ingreso' || !canMovements.create || !movementOptions) return;
        setTab('ingresos');
        kardexRef.current?.openCreate();
    }, [initialAction, canMovements.create, movementOptions]);

    const header = HEADERS[tab];

    return (
        <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
            <div className="px-4 sm:px-6 py-4 sm:py-5">
                <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3"><i className={`fas ${header.icon} text-leaf`} />{header.title}</h3>
                <p className="text-earth text-xs sm:text-sm mt-1">{header.description}</p>
            </div>

            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 border-b-2 border-wheat gap-3">
                <div className="flex items-center gap-1 overflow-x-auto max-w-full">
                    {canProducts.view && <TabButton active={tab === 'productos'} icon="fa-box" onClick={() => setTab('productos')}>Productos</TabButton>}
                    {canMovements.view && <TabButton active={tab === 'ingresos'} icon="fa-arrow-right-to-bracket" onClick={() => setTab('ingresos')}>Registro de Ingreso</TabButton>}
                    {canProgramacion.view && <TabButton active={tab === 'programacion'} icon="fa-calendar-check" onClick={() => setTab('programacion')}>Programación de Entrega</TabButton>}
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    {tab === 'productos' && canProducts.create && <button type="button" onClick={() => productsRef.current?.openCreate()} className="btn-primary flex items-center gap-2 text-xs sm:text-sm"><i className="fas fa-plus" />Registrar Producto</button>}
                    {tab === 'ingresos' && <button type="button" onClick={() => kardexRef.current?.openControlSheet()} className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"><i className="fas fa-clipboard-check" />Ficha de Control Interno</button>}
                    {tab === 'ingresos' && canMovements.create && <button type="button" onClick={() => kardexRef.current?.openCreate()} className="btn-primary flex items-center gap-2 text-xs sm:text-sm"><i className="fas fa-plus" />Registrar Ingreso</button>}
                </div>
            </div>

            <div className="p-4 sm:p-6">
                {optionsError && <div className="mb-3 text-xs text-clay bg-clay-light rounded-lg px-3 py-2"><i className="fas fa-exclamation-triangle mr-1" />No se pudieron cargar algunas opciones.</div>}
                {canProducts.view && <div className={tab === 'productos' ? '' : 'hidden'}><ProductosTab ref={productsRef} options={productOptions ?? {}} can={canProducts} onProductsChanged={loadOptions} /></div>}
                {canMovements.view && <div className={tab === 'ingresos' ? '' : 'hidden'}><KardexTab ref={kardexRef} options={movementOptions ?? {}} can={canMovements} onlyIngresos /></div>}
                {canProgramacion.view && tab === 'programacion' && <ProgramacionEntregaTab can={canProgramacion} onGoToIngresos={() => canMovements.view && setTab('ingresos')} />}
            </div>
        </div>
    );
}

function TabButton({ active, icon, onClick, children }) {
    return <button type="button" onClick={onClick} className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${active ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'}`}><i className={`fas ${icon}`} />{children}</button>;
}
