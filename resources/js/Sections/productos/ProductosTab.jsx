import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Pagination from '../../Components/Pagination';
import { useDebounced } from '../socios/hooks';
import { fmtDate, money } from './format';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/productos-pecosas';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

function productStatus(dp) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const end = dp.end_date ? new Date(`${dateValue(dp.end_date)}T00:00:00`) : null;
    const start = dp.start_date ? new Date(`${dateValue(dp.start_date)}T00:00:00`) : null;
    if (end && end < today) return { label: 'Vencido', cls: 'bg-red-100 text-red-800' };
    if (start && start > today) return { label: 'Por venir', cls: 'bg-yellow-100 text-yellow-800' };
    return { label: 'Vigente', cls: 'bg-green-100 text-green-800' };
}

function dateValue(d) {
    if (!d) return '';
    return String(d).split('T')[0].split(' ')[0];
}

function ProductFormModal({ mode, product, options, onClose, onSaved }) {
    const toast = useToast();
    const [title, setTitle] = useState(mode === 'edit' && product ? product.title : '');
    const [code, setCode] = useState(mode === 'edit' && product ? product.code : '');
    const [abbreviation, setAbbreviation] = useState(mode === 'edit' && product ? product.abbreviation : '');
    const [uomId, setUomId] = useState(mode === 'edit' && product ? product.uom_id : '');
    const [stateId, setStateId] = useState(mode === 'edit' && product ? product.state_id : '');
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!title || !code || !uomId || !stateId) {
            toast.error('Complete los campos obligatorios del producto.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = { title, code, abbreviation, uom_id: uomId, state_id: stateId };
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
                        <label className={labelCls}>Código *</label>
                        <input type="text" value={code} onChange={(e) => setCode(e.target.value)} className={inputCls} required />
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
                    <div>
                        <label className={labelCls}>Estado *</label>
                        <select value={stateId} onChange={(e) => setStateId(e.target.value)} className={inputCls} required>
                            <option value="">Seleccione</option>
                            {(options.states || []).map((s) => (
                                <option key={s.id} value={s.id}>{s.title}</option>
                            ))}
                        </select>
                    </div>
                </div>
                <div className="flex justify-end gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onClick={onClose} className="btn-secondary">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />
                        {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function ProductViewModal({ product, onClose, onEdit }) {
    if (!product) return null;
    return (
        <Modal open onClose={onClose} title="Detalle del Producto" icon="fa-eye" iconClass="text-[#0284C7]" maxWidth="sm:max-w-lg">
            <div className="p-6 grid grid-cols-2 gap-4">
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">ID</p>
                    <p className="font-semibold text-charcoal">#{product.id}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Nombre</p>
                    <p className="font-semibold text-charcoal">{product.title}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Código</p>
                    <p className="font-semibold text-charcoal">{product.code || '-'}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Abreviatura</p>
                    <p className="font-semibold text-charcoal">{product.abbreviation || '-'}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Stock</p>
                    <p className="font-semibold text-charcoal">{product.stock} {product.uom?.title ?? ''}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Precio Unitario</p>
                    <p className="font-semibold text-charcoal">S/ {money(product.unit_price)}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Unidad de Medida</p>
                    <p className="font-semibold text-charcoal">{product.uom?.title || '-'}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Estado</p>
                    <span className={`px-3 py-1 rounded-full text-xs font-bold ${product.state && product.state.title === 'Activo' ? 'badge-active' : 'badge-inactive'}`}>
                        {product.state?.title || 'Sin estado'}
                    </span>
                </div>
            </div>
            <div className="flex justify-end gap-3 px-6 pb-6">
                <button type="button" onClick={onClose} className="btn-secondary">Cerrar</button>
                <button type="button" onClick={() => { onClose(); onEdit(); }} className="btn-primary">
                    <i className="fas fa-edit mr-2" /> Editar
                </button>
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
    const [detFilters, setDetFilters] = useState({ search_detalle: '', product_id: '', periodo: '' });
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
            if (debouncedDetFilters.search_detalle) params.search = debouncedDetFilters.search_detalle;
            if (debouncedDetFilters.product_id) params.product_id = debouncedDetFilters.product_id;
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
            <form onSubmit={(e) => e.preventDefault()} className="mb-4 sm:mb-6 flex flex-col sm:flex-row gap-2 sm:gap-4 flex-wrap">
                <div className="w-full sm:w-72">
                    <input
                        type="text"
                        value={filters.search}
                        onChange={(e) => setFilter('search', e.target.value)}
                        placeholder="Buscar por nombre o abreviatura..."
                        className={inputCls}
                    />
                </div>
                <div className="flex-1 min-w-36">
                    <select value={filters.state_id} onChange={(e) => setFilter('state_id', e.target.value)} className={inputCls}>
                        <option value="">Todos los Estados</option>
                        {(options.states || []).map((s) => (
                            <option key={s.id} value={s.id}>{s.title}</option>
                        ))}
                    </select>
                </div>
                <div className="flex-1 min-w-36">
                    <select value={filters.uom_id} onChange={(e) => setFilter('uom_id', e.target.value)} className={inputCls}>
                        <option value="">Todas las UOM</option>
                        {(options.uoms || []).map((u) => (
                            <option key={u.id} value={u.id}>{u.title}</option>
                        ))}
                    </select>
                </div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', state_id: '', uom_id: '' });
                            setPage(1);
                        }}
                        className="btn-secondary text-xs sm:text-sm"
                    >
                        <i className="fas fa-broom mr-1 sm:mr-2" /> Limpiar
                    </button>
                </div>
            </form>

            {loading && !data && (
                <div className="flex items-center justify-center py-10 text-earth">
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando productos...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[600px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">ID</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Nombre</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Abreviatura</th>
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
                                        <td className="px-3 sm:px-4 py-3 text-earth font-mono">#{product.id}</td>
                                        <td className="px-3 sm:px-4 py-3 font-semibold">{product.title || 'Sin nombre'}</td>
                                        <td className="px-3 sm:px-4 py-3 text-earth">{product.abbreviation || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3">
                                            <span className={`badge ${product.state && product.state.title === 'Activo' ? 'badge-active' : 'badge-inactive'}`}>
                                                {product.state?.title || 'Sin estado'}
                                            </span>
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-1 sm:gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setViewing(product)}
                                                    className="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver"
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>
                                                {can.edit && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(product)}
                                                        className="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                        title="Editar"
                                                    >
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                )}
                                                {can.del && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleting(product)}
                                                        className="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white"
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

            {data && (
                <div className="flex items-center justify-between px-1 sm:px-2 py-3 border-t-2 border-wheat mt-2">
                    <span className="text-xs sm:text-sm text-earth font-medium">
                        Mostrando {data.meta?.from ?? 0} - {data.meta?.to ?? 0} de {data.meta?.total ?? 0} registros
                    </span>
                    <Pagination links={data.meta?.links} meta={data.meta} onPage={setPage} loading={loading} />
                </div>
            )}

            <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mt-6">
                    <div className="px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat">
                        <h3 className="font-extrabold text-charcoal text-lg sm:text-xl flex items-center gap-3">
                            <i className="fas fa-boxes text-leaf" /> Detalle Productos
                        </h3>
                    </div>
                    <div className="p-4 sm:p-6">
                        <form onSubmit={(e) => e.preventDefault()} className="mb-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="md:col-span-2">
                                <label className={labelCls}>Buscar Producto</label>
                                <input
                                    type="text"
                                    value={detFilters.search_detalle}
                                    onChange={(e) => setDetFilters((p) => ({ ...p, search_detalle: e.target.value }))}
                                    placeholder="Nombre o abreviatura..."
                                    className={inputCls}
                                />
                            </div>
                            <div>
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
                            <div>
                                <label className={labelCls}>Período</label>
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
                        </form>

                        {detLoading && !detData && (
                            <div className="flex items-center justify-center py-10 text-earth">
                                <i className="fas fa-spinner fa-spin mr-2" /> Cargando detalle de productos...
                            </div>
                        )}

                        {detData && (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-bold text-earth">Producto</th>
                                            <th className="px-4 py-3 text-left font-bold text-earth">Período</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth">Stock Inicial</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth">Precio Unit.</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth">Total Entrada</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth">Stock Usado</th>
                                            <th className="px-4 py-3 text-right font-bold text-earth">Stock Actual</th>
                                            <th className="px-4 py-3 text-left font-bold text-earth">Estado</th>
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
                                                        <td className="px-4 py-3">
                                                            <div className="font-medium text-charcoal">{dp.product_title}</div>
                                                            <div className="text-xs text-gray-500">{dp.product_abbreviation}</div>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <div className="text-xs">
                                                                <div className="font-medium">Desde: {fmtDate(dp.start_date)}</div>
                                                                <div className="font-medium">Hasta: {fmtDate(dp.end_date)}</div>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-3 text-right font-bold">{money(stockInicial)}</td>
                                                        <td className="px-4 py-3 text-right">S/ {money(dp.unit_price)}</td>
                                                        <td className="px-4 py-3 text-right">S/ {money(totalEntrada)}</td>
                                                        <td className="px-4 py-3 text-right text-red-600">
                                                            {stockUsado > 0 ? `-${money(stockUsado)}` : '-'}
                                                        </td>
                                                        <td className={`px-4 py-3 text-right font-bold ${stockActual > 0 ? 'text-green-600' : 'text-red-600'}`}>
                                                            {money(stockActual)}
                                                        </td>
                                                        <td className="px-4 py-3">
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
                onEdit={() => viewing && openEdit(viewing)}
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
