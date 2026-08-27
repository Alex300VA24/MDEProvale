import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import DetailModal, { DetailGroup, Field, FieldGrid } from '../../Components/DetailModal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Combobox from '../../Components/Combobox';
import Pagination from '../../Components/Pagination';
import { useDebounced } from '../socios/hooks';
import { dateValue, detailOptionLabel, fmtDate } from './format';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/productos-pecosas';

const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const readonlyCls = inputCls.replace('bg-white', 'bg-gray-100');

function emptyDetailRow(key) {
    return { key, detail_product_id: '', quantity: '', unit_price: '' };
}

function detailRowFromApi(d, options) {
    const dp = (options.detail_products || []).find((x) => Number(x.id) === Number(d.detail_product_id));
    return {
        key: `existing-${d.id}`,
        detail_pecosa_id: d.id,
        detail_product_id: d.detail_product_id,
        quantity: d.quantity,
        unit_price: dp ? dp.unit_price : d.unit_price,
    };
}

function PecosaFormModal({ mode, pecosa, options, onClose, onSaved }) {
    const toast = useToast();
    const [pecosaNumber, setPecosaNumber] = useState(mode === 'edit' && pecosa ? pecosa.pecosa_number : '');
    const [associationId, setAssociationId] = useState(
        mode === 'edit' && pecosa ? pecosa.association_id ?? pecosa.association?.id ?? '' : ''
    );
    const [deliveryDate, setDeliveryDate] = useState(
        mode === 'edit' && pecosa ? dateValue(pecosa.delivery_date) : new Date().toISOString().split('T')[0]
    );
    const [presidentName, setPresidentName] = useState(
        mode === 'edit' && pecosa
            ? pecosa.managing_partner_name || pecosa.president_name || ''
            : ''
    );
    const [managingPartnerId, setManagingPartnerId] = useState(
        mode === 'edit' && pecosa ? pecosa.managing_partner_id ?? pecosa.managing_partner?.id ?? '' : ''
    );
    const [stateId, setStateId] = useState(
        mode === 'edit' && pecosa ? pecosa.state_id ?? pecosa.state?.id ?? ''
        : (options.states || []).find((s) => s.abbreviation === 'ACT')?.id ?? ''
    );
    const [observation, setObservation] = useState(mode === 'edit' && pecosa ? pecosa.observation : '');
    const [details, setDetails] = useState(() =>
        mode === 'edit' && pecosa ? (pecosa.detail_pecosas || []).map((d) => detailRowFromApi(d, options)) : []
    );
    const [submitting, setSubmitting] = useState(false);

    const activeChief = (options.responsibles || []).find((r) => r.type === 'chief');
    const activeStorekeeper = (options.responsibles || []).find((r) => r.type === 'storekeeper');
    const chiefId = mode === 'edit'
        ? (pecosa?.chief_name ? pecosa?.chief_id ?? null : null)
        : activeChief?.id;
    const storekeeperId = mode === 'edit'
        ? (pecosa?.storekeeper_name ? pecosa?.storekeeper_id ?? null : null)
        : activeStorekeeper?.id;
    const chiefName = mode === 'edit'
        ? pecosa?.chief_name || ''
        : activeChief?.name;
    const storekeeperName = mode === 'edit'
        ? pecosa?.storekeeper_name || ''
        : activeStorekeeper?.name;

    const associations = (options.associations || []).filter((a) => a && a.id !== undefined && a.id !== null);
    const clubOptions = associations.map((a) => ({ id: a.id, label: a.name }));
    const detailOptions = (options.detail_products || []).filter((dp) => Number(dp.available_stock || 0) > 0);

    const handleAssociationChange = (value) => {
        setAssociationId(value ?? '');
        const assoc = associations.find((a) => String(a.id) === String(value));
        const pId = assoc?.president_partner_id;
        setManagingPartnerId(pId ?? '');
        if (assoc?.president_name) setPresidentName(assoc.president_name);
        else if (pId) setPresidentName(`Presidenta seleccionada (ID: ${pId})`);
        else setPresidentName('Sin presidenta asignada');
    };

    const addDetail = () => setDetails((prev) => [...prev, emptyDetailRow(`new-${Date.now()}-${prev.length}`)]);

    const updateDetail = (index, field, value) =>
        setDetails((prev) => prev.map((d, i) => (i === index ? { ...d, [field]: value } : d)));

    const removeDetail = (index) => setDetails((prev) => prev.filter((_, i) => i !== index));

    const handleDetailProductChange = (index, value) => {
        const dp = (options.detail_products || []).find((x) => String(x.id) === String(value));
        setDetails((prev) =>
            prev.map((d, i) =>
                i === index ? { ...d, detail_product_id: value, unit_price: dp ? dp.unit_price : '' } : d
            )
        );
    };

    const rowOptions = (row) => {
        const base = detailOptions;
        const current = (options.detail_products || []).find((x) => String(x.id) === String(row.detail_product_id));
        if (current && !base.some((x) => String(x.id) === String(current.id))) {
            return [...base, current];
        }
        return base;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!pecosaNumber || !associationId || !deliveryDate || !managingPartnerId || !stateId) {
            toast.error('Complete los campos obligatorios de la pecosa.');
            return;
        }
        const invalid = details.filter((d) => !d.detail_product_id || Number(d.quantity) <= 0);
        if (details.length === 0 || invalid.length > 0) {
            toast.error('Agregue al menos un producto con cantidad válida.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                pecosa_number: pecosaNumber,
                association_id: associationId,
                delivery_date: deliveryDate,
                managing_partner_id: managingPartnerId,
                chief_id: chiefId ?? null,
                storekeeper_id: storekeeperId ?? null,
                state_id: stateId,
                observation: observation || null,
                details: details.map((d) => ({
                    detail_product_id: d.detail_product_id,
                    quantity: Number(d.quantity),
                })),
            };
            if (mode === 'edit') {
                await http.put(`${BASE}/pecosas/${pecosa.id}`, payload);
                toast.success('Pecosa actualizada correctamente.');
            } else {
                await http.post(`${BASE}/pecosas`, payload);
                toast.success('Pecosa creada exitosamente.');
            }
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al guardar la pecosa.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Pecosa' : 'Registrar Pecosa'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-plus-circle'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-4xl"
        >
            <form onSubmit={handleSubmit} className="p-4 sm:p-6">
                <h4 className="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                    <i className="fas fa-file-invoice text-leaf" /> Información de la Pecosa
                </h4>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                    <div>
                        <label className={labelCls}>Número de Pecosa *</label>
                        <input type="text" value={pecosaNumber} onChange={(e) => setPecosaNumber(e.target.value)} placeholder="000-000" className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Club de Madres *</label>
                        <Combobox
                            value={associationId}
                            onChange={handleAssociationChange}
                            options={clubOptions}
                            placeholder="Buscar club..."
                            allowClear
                        />
                    </div>
                    <div>
                        <label className={labelCls}>Fecha de Entrega *</label>
                        <input type="date" value={deliveryDate} onChange={(e) => setDeliveryDate(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Presidenta del Comité</label>
                        <input type="text" value={presidentName} readOnly placeholder="Se autocompletará al seleccionar club" className={readonlyCls} />
                    </div>
                    <div>
                        <label className={labelCls}>Subgerencia de Programas Sociales</label>
                        <input type="text" readOnly value={chiefName || ''} className={readonlyCls} />
                    </div>
                    <div>
                        <label className={labelCls}>Programa Vaso de Leche</label>
                        <input type="text" readOnly value={storekeeperName || ''} className={readonlyCls} />
                    </div>
                    {mode === 'edit' && (
                    <div>
                        <label className={labelCls}>Estado *</label>
                        <select value={stateId} onChange={(e) => setStateId(e.target.value)} className={inputCls} required>
                            <option value="">Seleccionar...</option>
                            {(options.states || []).map((s) => (
                                <option key={s.id} value={s.id}>{s.title}</option>
                            ))}
                        </select>
                    </div>
                    )}
                    <div className="md:col-span-2">
                        <label className={labelCls}>Observaciones</label>
                        <textarea rows="2" value={observation} onChange={(e) => setObservation(e.target.value)} placeholder="Detalles adicionales de la entrega..." className={inputCls} />
                    </div>
                </div>

                <div className="mt-8 border-t-2 border-wheat pt-6">
                    <div className="flex items-center justify-between mb-4">
                        <h4 className="font-extrabold text-charcoal text-lg flex items-center gap-2">
                            <i className="fas fa-list text-leaf" /> Detalle de Productos
                        </h4>
                        <button type="button" onClick={addDetail} className="btn-secondary text-sm">
                            <i className="fas fa-plus mr-1" /> Agregar Producto
                        </button>
                    </div>

                    {details.length === 0 && (
                        <p className="text-sm text-earth bg-base rounded-lg px-4 py-3 mb-4">
                            No hay productos agregados. Use "Agregar Producto" para incluir ítems.
                        </p>
                    )}

                    <div className="space-y-4">
                        {details.map((d, i) => (
                            <div key={d.key} className="grid grid-cols-1 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat mb-4 animate-fade-in">
                                <div className="flex items-center justify-between border-b border-wheat pb-2 mb-2">
                                    <span className="text-xs font-bold text-leaf uppercase">Ítem #{i + 1}</span>
                                    <button type="button" onClick={() => removeDetail(i)} className="text-red-500 hover:text-red-700">
                                        <i className="fas fa-times-circle" />
                                    </button>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div className="md:col-span-2">
                                        <label className={labelCls}>Producto (Detalle) *</label>
                                        <select
                                            value={d.detail_product_id}
                                            onChange={(e) => handleDetailProductChange(i, e.target.value)}
                                            className={inputCls}
                                            required
                                        >
                                            <option value="">Seleccionar producto...</option>
                                            {rowOptions(d).map((dp) => (
                                                <option key={dp.id} value={dp.id}>{detailOptionLabel(dp)}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className={labelCls}>Cantidad *</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            value={d.quantity}
                                            onChange={(e) => updateDetail(i, 'quantity', e.target.value)}
                                            className={inputCls}
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className={labelCls}>Precio unitario (S/)</label>
                                        <input type="number" step="0.01" min="0" value={d.unit_price} onChange={(e) => updateDetail(i, 'unit_price', e.target.value)} className={inputCls} />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="flex gap-3 mt-10">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1 text-xs sm:text-sm">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary flex-1 text-xs sm:text-sm">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : mode === 'edit' ? 'fa-rotate' : 'fa-save'} mr-2`} />
                        {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function PecosaViewModal({ pecosa, onClose }) {
    if (!pecosa) return null;
    const details = pecosa.detail_pecosas || [];
    return (
        <DetailModal open onClose={onClose} title="Detalle de la pecosa" icon="fa-file-lines" maxWidth="sm:max-w-lg">
            <DetailGroup>
                <FieldGrid>
                    <Field label="Número" value={pecosa.pecosa_number} />
                    <Field label="Estado">
                        <span className={`badge ${pecosa.state?.abbreviation === 'VIG' ? 'badge-current' : pecosa.state?.abbreviation === 'VEN' ? 'badge-expired' : 'badge-unknown'}`}>
                            {pecosa.state?.title || 'N/A'}
                        </span>
                    </Field>
                    <Field label="Club de madres" value={pecosa.association_name || pecosa.association?.name} wide />
                    <Field label="Fecha de entrega" value={fmtDate(pecosa.delivery_date)} />
                    <Field label="Presidenta" value={pecosa.managing_partner_name || pecosa.president_name} />
                    <Field label="Productos">
                        <span className="text-lg font-bold text-leaf">{details.length}</span>
                    </Field>
                </FieldGrid>
                {pecosa.observation && <Field label="Observación" value={pecosa.observation} wide />}
            </DetailGroup>

            {details.length > 0 && (
                <DetailGroup title="Lista de productos" icon="fa-boxes-stacked">
                    <div className="space-y-2">
                        {details.map((d) => (
                            <div key={d.id} className="flex items-center justify-between rounded-xl border border-mist bg-base/60 p-3">
                                <div>
                                    <p className="text-sm font-bold text-charcoal">{d.product_name || d.product?.title || `Producto #${d.detail_product_id}`}</p>
                                    <p className="text-xs text-earth">{d.product_abbreviation || d.product?.abbreviation || ''}</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-bold text-charcoal">{Number(d.quantity)}</p>
                                    <p className="text-xs text-earth">S/ {Number(d.unit_price || 0).toFixed(2)}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </DetailGroup>
            )}
        </DetailModal>
    );
}

const PecosasTab = forwardRef(function PecosasTab({ options, can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', association_id: '', state_id: '' });
    const [page, setPage] = useState(1);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [viewing, setViewing] = useState(null);
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
            if (debouncedFilters.association_id) params.association_id = debouncedFilters.association_id;
            if (debouncedFilters.state_id) params.state_id = debouncedFilters.state_id;
            const res = await http.get(`${BASE}/pecosas`, { params });
            setData(res.data);
        } catch {
            toast.error('No se pudo cargar la lista de pecosas.');
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

    useImperativeHandle(ref, () => ({
        openCreate: () => {
            setFormMode('create');
            setEditing(null);
            setFormOpen(true);
        },
    }));

    const setFilter = (key, value) => setFilters((prev) => ({ ...prev, [key]: value }));

    const openEdit = (pecosa) => {
        setEditing(pecosa);
        setFormMode('edit');
        setFormOpen(true);
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/pecosas/${deleting.id}`);
            toast.success('Pecosa y stock eliminados correctamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar la pecosa.'));
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
                            placeholder="Buscar por número de pecosa..."
                            className="w-full pl-10 pr-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                        />
                    </div>
                </div>
                <div className="w-full sm:w-[300px] lg:w-[300px] shrink-0">
                    <label className={labelCls}>Club de Madres</label>
                    <Combobox
                        value={filters.association_id}
                        onChange={(v) => setFilter('association_id', v ?? '')}
                        options={(options.associations || []).map((a) => ({ id: a.id, label: a.name }))}
                        placeholder="Todos los Clubes"
                        allowClear
                    />
                </div>
                <div className="w-full sm:w-40 lg:w-40 shrink 0">
                    <label className={labelCls}>Estado</label>
                    <select value={filters.state_id} onChange={(e) => setFilter('state_id', e.target.value)} className={inputCls}>
                        <option value="">Estados</option>
                        {(options.states || []).map((s) => (
                            <option key={s.id} value={s.id}>{s.title}</option>
                        ))}
                    </select>
                </div>
                <div className="w-full sm:w-auto shrink-0 flex flex-col">
                    <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', association_id: '', state_id: '' });
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

            {loading && !data && (
                <div className="flex items-center justify-center py-10 text-earth">
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando pecosas...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[720px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">Número Pecosa</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Club de Madres</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Fecha Entrega</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Responsable</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Estado</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6}>
                                        <div className="empty-state">
                                            <i className="fas fa-file-alt" />
                                            <p>No hay pecosas registradas</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((pecosa) => (
                                    <tr key={pecosa.id} className="row-enter">
                                        <td className="px-3 sm:px-4 py-3 font-semibold">{pecosa.pecosa_number || 'Sin número'}</td>
                                        <td className="px-3 sm:px-4 py-3">
                                            {pecosa.association_name || pecosa.association?.name ? (
                                                <span className="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">{pecosa.association_name || pecosa.association.name}</span>
                                            ) : '-'}
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 text-earth">{fmtDate(pecosa.delivery_date) || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3">{pecosa.managing_partner_name || pecosa.president_name || ''}</td>
                                        <td className="px-3 sm:px-4 py-3">
                                            {pecosa.state ? (
                                                pecosa.state.abbreviation === 'VIG' ? (
                                                    <span className="badge badge-current">Vigente</span>
                                                ) : (
                                                    <span className="badge badge-expired">{pecosa.state.title}</span>
                                                )
                                            ) : (
                                                <span className="badge badge-unknown">Sin estado</span>
                                            )}
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="inline-grid grid-cols-[repeat(4,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                                <a
                                                    href={`/productos-pecosas/pecosas/${pecosa.id}/comprobante`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="btn-action col-start-1 bg-leaf-light text-leaf hover:bg-leaf hover:text-white"
                                                    title="Generar Comprobante"
                                                >
                                                    <i className="fas fa-file-pdf" />
                                                </a>
                                                <button
                                                    type="button"
                                                    onClick={() => setViewing(pecosa)}
                                                    className="btn-action col-start-2 bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver"
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>
                                                {can.edit && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(pecosa)}
                                                        className="btn-action col-start-3 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                        title="Editar"
                                                    >
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                )}
                                                {can.del && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleting(pecosa)}
                                                        className="btn-action col-start-4 bg-clay-light text-clay hover:bg-clay hover:text-white"
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

            {formOpen && (
                <PecosaFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    pecosa={editing}
                    options={options}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                    }}
                />
            )}

            <PecosaViewModal pecosa={viewing} onClose={() => setViewing(null)} />

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Pecosa"
                message="Se eliminará esta pecosa, se revertirá el stock y se borrarán sus movimientos de forma permanente."
                details={deleting ? [
                    { label: 'N° Pecosa', value: deleting.pecosa_number },
                    { label: 'Comité', value: deleting.association?.name },
                    { label: 'Fecha', value: fmtDate(deleting.delivery_date) },
                ] : []}
            />
        </>
    );
});

export default PecosasTab;
