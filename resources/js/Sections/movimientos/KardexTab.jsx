import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Pagination from '../../Components/Pagination';
import { useDebounced } from '../socios/hooks';
import { fmtDate, dateValue, money, typeBadgeClass } from './format';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/movimientos';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const readonlyCls = inputCls.replace('bg-white', 'bg-gray-100');

const MONTHS = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

function IngresoFormModal({ options, onClose, onSaved }) {
    const toast = useToast();
    const [productId, setProductId] = useState('');
    const [quantity, setQuantity] = useState('');
    const [unitPrice, setUnitPrice] = useState('');
    const [documentNumber, setDocumentNumber] = useState('');
    const [transactionDate, setTransactionDate] = useState(new Date().toISOString().split('T')[0]);
    const [startDate, setStartDate] = useState(new Date().toISOString().split('T')[0]);
    const [endDate, setEndDate] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const ingresoType = (options.types || []).find((t) => String(t.title).toLowerCase() === 'ingreso');

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!ingresoType) {
            toast.error('No se encontró el tipo de movimiento "Ingreso".');
            return;
        }
        if (!productId || !quantity || Number(quantity) <= 0 || unitPrice === '' || !transactionDate || !startDate || !endDate) {
            toast.error('Complete todos los campos obligatorios.');
            return;
        }
        setSubmitting(true);
        try {
            await http.post(`${BASE}/transactions`, {
                type_transaction_id: ingresoType.id,
                product_id: productId,
                quantity: Number(quantity),
                unit_price: Number(unitPrice),
                document_number: documentNumber || null,
                transaction_date: transactionDate,
                start_date: startDate,
                end_date: endDate,
            });
            toast.success('Ingreso registrado correctamente.');
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al registrar el ingreso.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open onClose={onClose} title="Nuevo Ingreso" icon="fa-plus-circle" iconClass="text-leaf" maxWidth="sm:max-w-2xl">
            <form onSubmit={handleSubmit} className="p-4 sm:p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Producto *</label>
                        <select value={productId} onChange={(e) => setProductId(e.target.value)} className={inputCls} required>
                            <option value="">Seleccionar producto...</option>
                            {(options.products || []).map((p) => (
                                <option key={p.id} value={p.id}>{p.title}{p.abbreviation ? ` (${p.abbreviation})` : ''}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className={labelCls}>Cantidad *</label>
                        <input type="number" step="0.01" min="0.01" value={quantity} onChange={(e) => setQuantity(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>P. Unitario (S/) *</label>
                        <input type="number" step="0.01" min="0" value={unitPrice} onChange={(e) => setUnitPrice(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>N° Documento</label>
                        <input type="text" value={documentNumber} onChange={(e) => setDocumentNumber(e.target.value)} className={inputCls} placeholder="Guía, factura, etc." />
                    </div>
                    <div>
                        <label className={labelCls}>Fecha de Movimiento *</label>
                        <input type="date" value={transactionDate} onChange={(e) => setTransactionDate(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Vigencia del Lote - Desde *</label>
                        <input type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Vigencia del Lote - Hasta *</label>
                        <input type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} className={inputCls} required />
                    </div>
                </div>
                <div className="flex gap-3">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1 text-xs sm:text-sm">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary flex-1 text-xs sm:text-sm">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} /> Guardar Ingreso
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function EditTransactionModal({ transaction, onClose, onSaved }) {
    const toast = useToast();
    const isIngreso = String(transaction.type?.title).toLowerCase() === 'ingreso';
    const [quantity, setQuantity] = useState(transaction.quantity);
    const [unitPrice, setUnitPrice] = useState(transaction.unit_price);
    const [documentNumber, setDocumentNumber] = useState(transaction.document_number || '');
    const [transactionDate, setTransactionDate] = useState(dateValue(transaction.transaction_date));
    const [startDate, setStartDate] = useState(dateValue(transaction.detail_product?.start_date));
    const [endDate, setEndDate] = useState(dateValue(transaction.detail_product?.end_date));
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!quantity || Number(quantity) <= 0 || unitPrice === '' || !transactionDate) {
            toast.error('Complete todos los campos obligatorios.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                quantity: Number(quantity),
                unit_price: Number(unitPrice),
                document_number: documentNumber || null,
                transaction_date: transactionDate,
            };
            if (isIngreso) {
                payload.start_date = startDate || null;
                payload.end_date = endDate || null;
            }
            await http.put(`${BASE}/transactions/${transaction.id}`, payload);
            toast.success('Movimiento actualizado correctamente.');
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al actualizar el movimiento.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open onClose={onClose} title={`Editar ${transaction.type?.title || 'Movimiento'}`} icon="fa-edit" iconClass="text-sun" maxWidth="sm:max-w-2xl">
            <form onSubmit={handleSubmit} className="p-4 sm:p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Producto</label>
                        <input type="text" readOnly value={transaction.product_name || '-'} className={readonlyCls} />
                    </div>
                    <div>
                        <label className={labelCls}>Cantidad *</label>
                        <input type="number" step="0.01" min="0.01" value={quantity} onChange={(e) => setQuantity(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>P. Unitario (S/) *</label>
                        <input type="number" step="0.01" min="0" value={unitPrice} onChange={(e) => setUnitPrice(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>N° Documento</label>
                        <input type="text" value={documentNumber} onChange={(e) => setDocumentNumber(e.target.value)} className={inputCls} />
                    </div>
                    <div>
                        <label className={labelCls}>Fecha de Movimiento *</label>
                        <input type="date" value={transactionDate} onChange={(e) => setTransactionDate(e.target.value)} className={inputCls} required />
                    </div>
                    {isIngreso && (
                        <>
                            <div>
                                <label className={labelCls}>Vigencia del Lote - Desde</label>
                                <input type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} className={inputCls} />
                            </div>
                            <div>
                                <label className={labelCls}>Vigencia del Lote - Hasta</label>
                                <input type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} className={inputCls} />
                            </div>
                        </>
                    )}
                </div>
                <div className="flex gap-3">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1 text-xs sm:text-sm">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary flex-1 text-xs sm:text-sm">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} /> Guardar Cambios
                    </button>
                </div>
            </form>
        </Modal>
    );
}

const KardexTab = forwardRef(function KardexTab({ options, can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', type_transaction_id: '', year: '', month: '' });
    const [page, setPage] = useState(1);
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);

    const debouncedFilters = useDebounced(filters, 400);

    useEffect(() => {
        setPage(1);
    }, [debouncedFilters]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = { per_page: 10, page };
            if (debouncedFilters.search) params.search = debouncedFilters.search;
            if (debouncedFilters.type_transaction_id) params.type_transaction_id = debouncedFilters.type_transaction_id;
            if (debouncedFilters.year) params.year = debouncedFilters.year;
            if (debouncedFilters.month) params.month = debouncedFilters.month;
            const res = await http.get(`${BASE}/transactions`, { params });
            setData(res.data);
        } catch {
            toast.error('No se pudieron cargar los movimientos.');
        } finally {
            setLoading(false);
        }
    }, [debouncedFilters, page, toast]);

    useEffect(() => {
        load();
    }, [load]);

    useImperativeHandle(ref, () => ({
        openCreate: () => setCreating(true),
    }));

    const setFilter = (key, value) => setFilters((prev) => ({ ...prev, [key]: value }));

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/transactions/${deleting.id}`);
            toast.success('Movimiento eliminado correctamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar el movimiento.'));
            setDeleting(null);
        }
    };

    return (
        <>
            <div className="bg-gray-50 rounded-xl p-3 sm:p-4 mb-4 sm:mb-6">
            <form
                onSubmit={(e) => e.preventDefault()}
                className="flex flex-col lg:flex-row flex-wrap items-end gap-2 sm:gap-3"
            >
                <div className="w-full lg:flex-[1_1_15rem] lg:max-w-[22rem] min-w-0">
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
                            placeholder="Buscar por producto..."
                            className="w-full pl-10 pr-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                        />
                    </div>
                </div>
                <div className="w-full sm:w-40">
                    <label className={labelCls}>Tipo</label>
                    <select value={filters.type_transaction_id} onChange={(e) => setFilter('type_transaction_id', e.target.value)} className={inputCls}>
                        <option value="">Todos los Tipos</option>
                        {(options.types || []).map((t) => (
                            <option key={t.id} value={t.id}>{t.title}</option>
                        ))}
                    </select>
                </div>
                <div className="w-full sm:w-28">
                    <label className={labelCls}>Año</label>
                    <select value={filters.year} onChange={(e) => setFilter('year', e.target.value)} className={inputCls}>
                        <option value="">Todos los Años</option>
                        {(options.years || []).map((year) => (
                            <option key={year} value={year}>{year}</option>
                        ))}
                    </select>
                </div>
                <div className="w-full sm:w-32">
                    <label className={labelCls}>Mes</label>
                    <select value={filters.month} onChange={(e) => setFilter('month', e.target.value)} className={inputCls}>
                        <option value="">Todos los Meses</option>
                        {MONTHS.map((month, index) => (
                            <option key={month} value={index + 1}>{month}</option>
                        ))}
                    </select>
                </div>
                <button
                    type="button"
                    onClick={() => {
                        setFilters({ search: '', type_transaction_id: '', year: '', month: '' });
                        setPage(1);
                    }}
                    className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf hover:opacity-80 whitespace-nowrap shrink-0 self-end"
                >
                    <i className="fa-solid fa-sliders" /> Limpiar filtros
                </button>
            </form>
            </div>

            {loading && !data && (
                <div className="flex items-center justify-center py-10 text-earth">
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando movimientos...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[840px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">Tipo</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Producto</th>
                                <th className="px-3 sm:px-4 py-3 text-right">Cantidad</th>
                                <th className="px-3 sm:px-4 py-3 text-right">P. Unit.</th>
                                <th className="px-3 sm:px-4 py-3 text-right">Total</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Documento</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Período</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8}>
                                        <div className="empty-state">
                                            <i className="fas fa-right-left" />
                                            <p>No hay movimientos registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((tx) => (
                                    <tr key={tx.id} className="row-enter">
                                        <td className="px-3 sm:px-4 py-3">
                                            <span className={`px-2 py-1 rounded-lg text-xs font-bold ${typeBadgeClass(tx.type?.title)}`}>
                                                {tx.type?.title || '-'}
                                            </span>
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 font-semibold">{tx.product_name || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3 text-right">{tx.quantity} {tx.uom_title || ''}</td>
                                        <td className="px-3 sm:px-4 py-3 text-right">S/ {money(tx.unit_price)}</td>
                                        <td className="px-3 sm:px-4 py-3 text-right font-bold">S/ {money(tx.total_price)}</td>
                                        <td className="px-3 sm:px-4 py-3 text-earth">{tx.document_number || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3 text-earth whitespace-nowrap">
                                            <div><span className="font-semibold text-charcoal">Desde:</span> {fmtDate(tx.detail_product?.start_date) || '-'}</div>
                                            <div><span className="font-semibold text-charcoal">Hasta:</span> {fmtDate(tx.detail_product?.end_date) || '-'}</div>
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="inline-grid grid-cols-[repeat(2,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                                {can.edit && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setEditing(tx)}
                                                        className="btn-action col-start-1 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                        title="Editar"
                                                    >
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                )}
                                                {can.del && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleting(tx)}
                                                        className="btn-action col-start-2 bg-clay-light text-clay hover:bg-clay hover:text-white"
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

            {creating && (
                <IngresoFormModal
                    options={options}
                    onClose={() => setCreating(false)}
                    onSaved={() => {
                        setCreating(false);
                        load();
                    }}
                />
            )}

            {editing && (
                <EditTransactionModal
                    transaction={editing}
                    onClose={() => setEditing(null)}
                    onSaved={() => {
                        setEditing(null);
                        load();
                    }}
                />
            )}

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Movimiento"
                message="Se eliminará este movimiento de forma permanente. Si es una salida, se revertirá el stock afectado."
                details={deleting ? [
                    { label: 'Producto', value: deleting.product_name },
                    { label: 'Tipo', value: deleting.type?.title },
                    { label: 'Cantidad', value: `${deleting.quantity ?? ''} ${deleting.uom_title || ''}`.trim() },
                    { label: 'Fecha', value: fmtDate(deleting.transaction_date) },
                ] : []}
            />
        </>
    );
});

export default KardexTab;
