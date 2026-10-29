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
    const productModule = (modules ?? []).find((module) => module.slug === 'productos');
    const movementModule = (modules ?? []).find((module) => module.slug === 'movimientos');
    const canProducts = { view: !!productModule?.can_view, create: !!productModule?.can_create, edit: !!productModule?.can_edit, del: !!productModule?.can_delete };
    const canMovements = { view: !!movementModule?.can_view, create: !!movementModule?.can_create, edit: !!movementModule?.can_edit, del: !!movementModule?.can_delete };
    const initialTab = initialAction === 'ingresos' && canMovements.view
        ? 'ingresos'
        : initialAction === 'programacion' && canProducts.view
            ? 'programacion'
            : canProducts.view ? 'productos' : 'ingresos';
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
        if (initialAction === 'ingresos' && canMovements.view) setTab('ingresos');
        if (initialAction === 'programacion' && canProducts.view) setTab('programacion');
    }, [initialAction, canMovements.view, canProducts.view]);

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
                    {canProducts.view && <TabButton active={tab === 'programacion'} icon="fa-calendar-check" onClick={() => setTab('programacion')}>Programación de Entrega</TabButton>}
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
                {canMovements.view && <div className={tab === 'ingresos' ? '' : 'hidden'}><KardexTab ref={kardexRef} options={movementOptions ?? {}} can={canMovements} /></div>}
                {canProducts.view && tab === 'programacion' && <ProgramacionEntregaTab can={canProducts} onGoToIngresos={() => canMovements.view && setTab('ingresos')} />}
            </div>
        </div>
    );
}

function TabButton({ active, icon, onClick, children }) {
    return <button type="button" onClick={onClick} className={`flex items-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition-all ${active ? 'text-leaf border-leaf' : 'text-earth border-transparent hover:text-charcoal'}`}><i className={`fas ${icon}`} />{children}</button>;
}
