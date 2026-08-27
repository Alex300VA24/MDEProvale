import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Pagination from '../../Components/Pagination';
import { useDebounced } from '../socios/hooks';
import { fmtDate, money, stockInt } from './format';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/productos-pecosas';

const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

const MESES = [
    { value: '1', label: 'Enero' },
    { value: '2', label: 'Febrero' },
    { value: '3', label: 'Marzo' },
    { value: '4', label: 'Abril' },
    { value: '5', label: 'Mayo' },
    { value: '6', label: 'Junio' },
    { value: '7', label: 'Julio' },
    { value: '8', label: 'Agosto' },
    { value: '9', label: 'Setiembre' },
    { value: '10', label: 'Octubre' },
    { value: '11', label: 'Noviembre' },
    { value: '12', label: 'Diciembre' },
];

const currentYear = new Date().getFullYear();
const ANIOS = Array.from({ length: 15 }, (_, i) => currentYear - i).map((y) => ({ value: String(y), label: String(y) }));

function productStatus(dp) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const end = dp.end_date ? new Date(`${dateValue(dp.end_date)}T00:00:00`) : null;
    const start = dp.start_date ? new Date(`${dateValue(dp.start_date)}T00:00:00`) : null;
    if (end && end < today) return { label: 'Vencido', cls: 'badge-expired' };
    if (start && start > today) return { label: 'Por venir', cls: 'bg-yellow-100 text-yellow-800' };
    return { label: 'Vigente', cls: 'badge-current' };
}

function dateValue(d) {
    if (!d) return '';
    return String(d).split('T')[0].split(' ')[0];
}

function ProductFormModal({ mode, product, options, onClose, onSaved }) {
    const toast = useToast();
    const [title, setTitle] = useState(mode === 'edit' && product ? product.title : '');
    const [abbreviation, setAbbreviation] = useState(mode === 'edit' && product ? product.abbreviation : '');
    const [uomId, setUomId] = useState(mode === 'edit' && product ? product.uom_id : '');
    const [stateId, setStateId] = useState(mode === 'edit' && product ? product.state_id : ((options.states || []).find((s) => s.abbreviation === 'ACT')?.id ?? ''));
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!title || !uomId || !stateId) {
            toast.error('Complete los campos obligatorios del producto.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = { title, abbreviation, uom_id: uomId, state_id: stateId };
            if (mode === 'edit') {
                await http.put(`${BASE}/products/${product.id}`, payload);
                toast.success('Producto actualizado correctamente.');
            } else {
                await http.post(`${BASE}/products`, payload);
                toast.success('Producto creado exitosamente.');
            }
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al guardar el producto.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Producto' : 'Nuevo Producto'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-plus-circle'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-2xl"
        >
            <form onSubmit={handleSubmit} className="p-6">
                <div className="grid grid-cols-2 gap-4">
                    <div className="col-span-2">
                        <label className={labelCls}>Nombre del Producto *</label>
                        <input type="text" value={title} onChange={(e) => setTitle(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Abreviatura</label>
                        <input type="text" value={abbreviation} onChange={(e) => setAbbreviation(e.target.value)} className={inputCls} />
                    </div>
                    <div>
                        <label className={labelCls}>Unidad de Medida *</label>
                        <select value={uomId} onChange={(e) => setUomId(e.target.value)} className={inputCls} required>
                            <option value="">Seleccione</option>
                            {(options.uoms || []).map((u) => (
                                <option key={u.id} value={u.id}>{u.title}</option>
                            ))}
                        </select>
                    </div>
                    {mode === 'edit' && (
                    <div>
                        <label className={labelCls}>Estado *</label>
                        <select value={stateId} onChange={(e) => setStateId(e.target.value)} className={inputCls} required>
                            <option value="">Seleccione</option>
                            {(options.states || []).map((s) => (
                                <option key={s.id} value={s.id}>{s.title}</option>
                            ))}
                        </select>
                    </div>
                    )}
                </div>
                <div className="flex gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary flex-1">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />
                        {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function ProductViewModal({ product, onClose }) {
    if (!product) return null;
    return (
        <Modal open onClose={onClose} title="Detalle del Producto" icon="fa-eye" iconClass="text-[#0284C7]" maxWidth="sm:max-w-lg">
            <div className="p-6 grid grid-cols-2 gap-6">
                <div>
                    <p className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nombre</p>
                    <p className="text-sm font-semibold text-charcoal">{product.title}</p>
                </div>
                <div>
                    <p className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Abreviatura</p>
                    <p className="text-sm font-semibold text-charcoal">{product.abbreviation || '-'}</p>
                </div>
                <div>
                    <p className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Unidad de Medida</p>
                    <p className="text-sm font-semibold text-charcoal">{product.uom?.title || '-'}</p>
                </div>
                <div>
                    <p className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Estado</p>
                    <span className={`badge ${product.state?.abbreviation === 'VIG' ? 'badge-current' : product.state?.abbreviation === 'VEN' ? 'badge-expired' : 'badge-unknown'}`}>
                        {product.state?.title || 'Sin estado'}
                    </span>
                </div>
            </div>
        </Modal>
    );
}

const ProductosTab = forwardRef(function ProductosTab({ options, can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', state_id: '', uom_id: '' });
    const [page, setPage] = useState(1);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [viewing, setViewing] = useState(null);
    const [deleting, setDeleting] = useState(null);

    // Detalle Productos (endpoint paginado /products/detail-products)
    const [detFilters, setDetFilters] = useState({ product_id: '', year: '', month: '', periodo: '' });
    const [detData, setDetData] = useState(null);
    const [detLoading, setDetLoading] = useState(true);
    const [detPage, setDetPage] = useState(1);

    const debouncedFilters = useDebounced(filters, 400);
    const debouncedDetFilters = useDebounced(detFilters, 400);

    useEffect(() => {
        setPage(1);
    }, [debouncedFilters]);

    useEffect(() => {
        setDetPage(1);
    }, [debouncedDetFilters]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = { per_page: 10, page };
            if (debouncedFilters.search) params.search = debouncedFilters.search;
            if (debouncedFilters.state_id) params.state_id = debouncedFilters.state_id;
            if (debouncedFilters.uom_id) params.uom_id = debouncedFilters.uom_id;
            const res = await http.get(`${BASE}/products`, { params });
            setData(res.data);
        } catch {
            toast.error('No se pudo cargar la lista de productos.');
        } finally {
            setLoading(false);
        }
    }, [debouncedFilters, page, toast]);

    useEffect(() => {
        let active = true;
        (async () => {
            await load();
            active = false;
        })();
        return () => {
            active = false;
        };
    }, [load]);

    const detLoad = useCallback(async () => {
        setDetLoading(true);
        try {
            const params = { per_page: 15, page: detPage };
            if (debouncedDetFilters.product_id) params.product_id = debouncedDetFilters.product_id;
            if (debouncedDetFilters.year) params.year = debouncedDetFilters.year;
            if (debouncedDetFilters.month) params.month = debouncedDetFilters.month;
            if (debouncedDetFilters.periodo) params.periodo = debouncedDetFilters.periodo;
            const res = await http.get(`${BASE}/products/detail-products`, { params });
            setDetData(res.data);
        } catch {
            toast.error('No se pudo cargar el detalle de productos.');
        } finally {
            setDetLoading(false);
        }
    }, [debouncedDetFilters, detPage, toast]);

    useEffect(() => {
        let active = true;
        (async () => {
            await detLoad();
            active = false;
        })();
        return () => {
            active = false;
        };
    }, [detLoad]);

    useImperativeHandle(ref, () => ({
        openCreate: () => {
            setFormMode('create');
            setEditing(null);
            setFormOpen(true);
        },
    }));

    const setFilter = (key, value) => setFilters((prev) => ({ ...prev, [key]: value }));

    const openEdit = (product) => {
        setEditing(product);
        setFormMode('edit');
        setFormOpen(true);
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/products/${deleting.id}`);
            toast.success('Producto eliminado exitosamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar el producto.'));
            setDeleting(null);
        }
    };

    const detailProducts = detData?.data ?? [];

    return (
        <>
            <div className="bg-gray-50 rounded-xl p-3 sm:p-4 mb-4 sm:mb-6">
            <form
                onSubmit={(e) => e.preventDefault()}
                className="flex flex-col lg:flex-row flex-wrap items-end gap-2 sm:gap-3"
            >
                <div className="w-full lg:flex-1 min-w-[160px]">
                    <label className={labelCls}>Buscar</label>
                    <div className="relative">
                        <i
                            className="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-earth pointer-events-none"
                            aria-hidden="true"
                        />
                        <input
                            type="text"
                            value={filters.search}
                            onChange={(e) => setFilter('search', e.target.value)}
                            placeholder="Buscar por nombre o abreviatura..."
                            className="w-full pl-10 pr-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                        />
                    </div>
                </div>
                <div className="w-full sm:w-[300px] lg:w-[300px] shrink-0">
                    <label className={labelCls}>Estado</label>
                    <select value={filters.state_id} onChange={(e) => setFilter('state_id', e.target.value)} className={inputCls}>
                        <option value="">Estados</option>
                        {(options.states || []).map((s) => (
                            <option key={s.id} value={s.id}>{s.title}</option>
                        ))}
                    </select>
                </div>
                <div className="w-full sm:w-44 lg:w-40 shrink-0">
                    <label className={labelCls}>Unidad de Medida</label>
                    <select value={filters.uom_id} onChange={(e) => setFilter('uom_id', e.target.value)} className={inputCls}>
                        <option value="">Unidades de Medida</option>
                        {(options.uoms || []).map((u) => (
                            <option key={u.id} value={u.id}>{u.title}</option>
                        ))}
                    </select>
                </div>
                <div className="w-full sm:w-auto shrink-0 flex flex-col">
                    <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', state_id: '', uom_id: '' });
                            setPage(1);
                        }}
                        className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf border border-leaf rounded-md px-2.5 py-1.5 hover:opacity-80 whitespace-nowrap"
                    >
                        <i className="fa-solid fa-eraser" /> Limpiar
                    </button>
                    <p style={{ visibility: 'hidden', height: 6, margin: 0, padding: 0 }}>
                        {/* Ocupa espacio pero no se ve */}
                        Hola
                    </p>
                </div>
            </form>
            </div>

            {((loading && !data) || (detLoading && !detData)) && (
                <div className="flex items-center justify-center py-10 text-earth">
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando...
                </div>
            )}

            {data && detData && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[600px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">Nombre</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Abreviatura</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Unidad</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Estado</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5}>
                                        <div className="empty-state">
                                            <i className="fas fa-box" />
                                            <p>No hay productos registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((product) => (
                                    <tr key={product.id} className="row-enter">
                                        <td className="px-3 sm:px-4 py-3 font-semibold">{product.title || 'Sin nombre'}</td>
                                        <td className="px-3 sm:px-4 py-3 text-earth">{product.abbreviation || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3 text-earth">{product.uom?.title || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3">
                                            <span className={`badge ${product.state?.abbreviation === 'VIG' ? 'badge-current' : product.state?.abbreviation === 'VEN' ? 'badge-expired' : 'badge-unknown'}`}>
                                                {product.state?.title || 'Sin estado'}
                                            </span>
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="inline-grid grid-cols-[repeat(3,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setViewing(product)}
                                                    className="btn-action col-start-1 bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver"
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>
                                                {can.edit && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(product)}
                                                        className="btn-action col-start-2 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                        title="Editar"
                                                    >
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                )}
                                                {can.del && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleting(product)}
                                                        className="btn-action col-start-3 bg-clay-light text-clay hover:bg-clay hover:text-white"
                                                        title="Eliminar"
                                                    >
                                                        <i className="fas fa-trash" />
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {data && detData && (
                <div className="flex items-center justify-between px-1 sm:px-2 py-3 border-t-2 border-wheat mt-2">
                    <span className="text-xs sm:text-sm text-earth font-medium">
                        Mostrando {data.meta?.from ?? 0} - {data.meta?.to ?? 0} de {data.meta?.total ?? 0} registros
                    </span>
                    <Pagination links={data.meta?.links} meta={data.meta} onPage={setPage} loading={loading} />
                </div>
            )}

            {data && detData && (
            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mt-6">
                    <div className="px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat">
                        <h3 className="font-extrabold text-charcoal text-lg sm:text-xl flex items-center gap-3">
                            <i className="fas fa-boxes text-leaf" /> Detalle Productos
                        </h3>
                    </div>
                    <div className="p-4 sm:p-6">
<form onSubmit={(e) => e.preventDefault()} className="mb-5 flex flex-col lg:flex-row flex-wrap items-end gap-2 sm:gap-3">
                            <div className="w-full lg:flex-[1_1_15rem] lg:max-w-[22rem] min-w-0">
                                <label className={labelCls}>Producto</label>
                                <select
                                    value={detFilters.product_id}
                                    onChange={(e) => setDetFilters((p) => ({ ...p, product_id: e.target.value }))}
                                    className={inputCls}
                                >
                                    <option value="">Todos los productos</option>
                                    {(data?.data || []).map((p) => (
                                        <option key={p.id} value={p.id}>{p.title}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="w-full sm:w-40 lg:w-40 shrink-0">
                                <label className={labelCls}>Año</label>
                                <select
                                    value={detFilters.year}
                                    onChange={(e) => setDetFilters((p) => ({ ...p, year: e.target.value }))}
                                    className={inputCls}
                                >
                                    <option value="">Años</option>
                                    {ANIOS.map((y) => (
                                        <option key={y.value} value={y.value}>{y.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="w-full sm:w-40 lg:w-40 shrink-0">
                                <label className={labelCls}>Mes</label>
                                <select
                                    value={detFilters.month}
                                    onChange={(e) => setDetFilters((p) => ({ ...p, month: e.target.value }))}
                                    className={inputCls}
                                >
                                    <option value="">Meses</option>
                                    {MESES.map((m) => (
                                        <option key={m.value} value={m.value}>{m.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="w-full sm:w-32">
                                <label className={labelCls}>Estado</label>
                                <select
                                    value={detFilters.periodo}
                                    onChange={(e) => setDetFilters((p) => ({ ...p, periodo: e.target.value }))}
                                    className={inputCls}
                                >
                                    <option value="">Todos</option>
                                    <option value="vigente">Vigente</option>
                                    <option value="vencido">Vencido</option>
                                </select>
                            </div>
                            <div className="w-full sm:w-auto shrink-0 flex flex-col">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setDetFilters({ product_id: '', year: '', month: '', periodo: '' });
                                        setDetPage(1);
                                    }}
                                    className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf border border-leaf rounded-md px-2.5 py-1.5 hover:opacity-80 whitespace-nowrap"
                                >
                                    <i className="fa-solid fa-eraser" /> Limpiar
                                </button>
                                <p style={{ visibility: 'hidden', height: 6, margin: 0, padding: 0 }}>
                                    {/* Ocupa espacio pero no se ve */}
                                    Hola
                                </p>
                            </div>
</form>

                        {detData && (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-bold text-earth text-xs uppercase">Producto</th>
                                            <th className="px-4 py-3 text-left font-bold text-earth text-xs uppercase">Período</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth text-xs uppercase">Stock Inicial</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth text-xs uppercase">Precio Unit.</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth text-xs uppercase">Total Entrada</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth text-xs uppercase">Stock Usado</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth text-xs uppercase">Stock Actual</th>
                                            <th className="px-4 py-3 text-left font-bold text-earth text-xs uppercase">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {detailProducts.length === 0 ? (
                                            <tr>
                                                <td colSpan={8} className="px-4 py-8 text-center text-gray-500">
                                                    <i className="fas fa-boxes text-4xl mb-3" />
                                                    <p>No hay registros de productos</p>
                                                </td>
                                            </tr>
                                        ) : (
                                            detailProducts.map((dp) => {
                                                const status = productStatus(dp);
                                                const stockInicial = dp.quantity;
                                                const stockUsado = dp.used_quantity ?? 0;
                                                const stockActual = dp.available_stock;
                                                const totalEntrada = stockInicial * dp.unit_price;
                                                return (
                                                    <tr key={dp.id} className="hover:bg-gray-50">
                                                        <td className="px-4 py-4">
                                                            <div className="font-semibold text-sm text-charcoal">{dp.product_title}</div>
                                                            <div className="text-xs text-earth mt-0.5">{dp.product_abbreviation}</div>
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <div className="text-xs">
                                                                <div className="font-medium text-charcoal">Desde: {fmtDate(dp.start_date)}</div>
                                                                <div className="font-medium text-charcoal">Hasta: {fmtDate(dp.end_date)}</div>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4 text-right font-bold text-sm">{stockInt(stockInicial)}</td>
                                                        <td className="px-4 py-4 text-right text-sm">S/ {money(dp.unit_price)}</td>
                                                        <td className="px-4 py-4 text-right text-sm">S/ {money(totalEntrada)}</td>
                                                        <td className="px-4 py-4 text-right text-sm text-red-600">
                                                            {stockUsado > 0 ? stockInt(stockUsado) : '-'}
                                                        </td>
                                                        <td className={`px-4 py-4 text-right font-bold text-sm ${stockActual > 0 ? 'text-green-600' : 'text-red-600'}`}>
                                                            {stockInt(stockActual)}
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${status.cls}`}>
                                                                {status.label}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="flex items-center justify-between px-1 sm:px-2 py-3 border-t-2 border-wheat mt-4">
                                <span className="text-xs sm:text-sm text-earth font-medium">
                                    Mostrando {detData.meta?.from ?? 0} - {detData.meta?.to ?? 0} de {detData.meta?.total ?? 0} registros
                                </span>
                                <Pagination links={detData.meta?.links} meta={detData.meta} onPage={setDetPage} loading={detLoading} />
                            </div>
                            </>
                        )}
                    </div>
                </div>
            )}

            {formOpen && (
                <ProductFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    product={editing}
                    options={options}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                    }}
                />
            )}

            <ProductViewModal
                product={viewing}
                onClose={() => setViewing(null)}
            />

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Producto"
                message="Se eliminará este producto de forma permanente."
                details={deleting ? [
                    { label: 'Producto', value: deleting.title },
                    { label: 'Código', value: deleting.code },
                ] : []}
            />
        </>
    );
});

export default ProductosTab;
