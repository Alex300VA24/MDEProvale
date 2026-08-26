import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Combobox from '../../Components/Combobox';
import Pagination from '../../Components/Pagination';
import { useDebounced } from '../socios/hooks';
import { fmtDate, fmtDateTime, stateBadge } from './format';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/club-madres';

const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

function resolutionLabel(res) {
    if (!res) return '';
    return res.document ? `${res.document} (${fmtDate(res.date_document)})` : `Resolución #${res.id}`;
}

function ClubFormModal({ mode, club, options, onClose, onSaved }) {
    const toast = useToast();
    const [code, setCode] = useState(mode === 'edit' && club ? club.code || '' : '');
    const [name, setName] = useState(mode === 'edit' && club ? club.name || '' : '');
    const [companyName, setCompanyName] = useState(mode === 'edit' && club ? club.company_name || '' : '');
    const [address, setAddress] = useState(mode === 'edit' && club ? club.address || '' : '');
    const [phone, setPhone] = useState(mode === 'edit' && club ? club.phone || '' : '');
    const [observation, setObservation] = useState(mode === 'edit' && club ? club.observation || '' : '');
    const [resolutionId, setResolutionId] = useState(mode === 'edit' && club ? club.resolution_id ?? '' : '');
    const [placeSectorId, setPlaceSectorId] = useState(mode === 'edit' && club ? club.place_sector_id ?? '' : '');
    const [typePremisesId, setTypePremisesId] = useState(mode === 'edit' && club ? club.type_premises_id ?? '' : '');
    const [submitting, setSubmitting] = useState(false);

    const placeSectorOptions = (options.place_sectors || []).map((ps) => ({
        id: ps.id,
        label: `${ps.place ? ps.place.title : ''} - ${ps.sector ? ps.sector.title : ''}`,
    }));
    const resolutionOptions = (options.resolutions || []).map((r) => ({ id: r.id, label: resolutionLabel(r) }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!code || !name || !companyName || !address || !resolutionId || !placeSectorId || !typePremisesId) {
            toast.error('Complete los campos obligatorios del comité (incluye Razón Social).');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                code,
                name,
                company_name: companyName,
                address,
                phone: phone || null,
                observation: observation || null,
                resolution_id: resolutionId,
                place_sector_id: placeSectorId,
                type_premises_id: typePremisesId,
            };
            if (mode === 'edit') {
                await http.put(`${BASE}/clubs/${club.id}`, payload);
                toast.success('Comité actualizado correctamente.');
            } else {
                await http.post(`${BASE}/clubs`, payload);
                toast.success('Comité creado exitosamente.');
            }
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al guardar el comité.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Comité' : 'Nuevo Comité'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-plus-circle'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-3xl"
        >
            <form onSubmit={handleSubmit} className="p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label className={labelCls}>Código *</label>
                        <input type="text" value={code} onChange={(e) => setCode(e.target.value)} className={inputCls} required maxLength={20} />
                    </div>
                    <div>
                        <label className={labelCls}>Nombre del Comité *</label>
                        <input type="text" value={name} onChange={(e) => setName(e.target.value)} className={inputCls} required maxLength={100} />
                    </div>
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Razón Social *</label>
                        <input type="text" value={companyName} onChange={(e) => setCompanyName(e.target.value)} className={inputCls} required maxLength={150} />
                    </div>
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Dirección *</label>
                        <input type="text" value={address} onChange={(e) => setAddress(e.target.value)} className={inputCls} required maxLength={150} />
                    </div>
                    <div>
                        <label className={labelCls}>Teléfono</label>
                        <input type="text" value={phone} onChange={(e) => setPhone(e.target.value)} className={inputCls} maxLength={20} />
                    </div>
                    <div>
                        <label className={labelCls}>Tipo de Local *</label>
                        <select value={typePremisesId} onChange={(e) => setTypePremisesId(e.target.value)} className={inputCls} required>
                            <option value="">Seleccione</option>
                            {(options.type_premises || []).map((t) => (
                                <option key={t.id} value={t.id}>{t.title}</option>
                            ))}
                        </select>
                    </div>
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Zona / Sector *</label>
                        <Combobox
                            value={placeSectorId}
                            onChange={(v) => setPlaceSectorId(v ?? '')}
                            options={placeSectorOptions}
                            placeholder="Buscar zona / sector..."
                            allowClear
                        />
                    </div>
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Resolución Vigente *</label>
                        <Combobox
                            value={resolutionId}
                            onChange={(v) => setResolutionId(v ?? '')}
                            options={resolutionOptions}
                            placeholder="Buscar resolución..."
                            allowClear
                        />
                    </div>
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Observación</label>
                        <textarea
                            value={observation}
                            onChange={(e) => setObservation(e.target.value)}
                            className={`${inputCls} resize-none`}
                            rows={2}
                        />
                    </div>
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

function ClubViewModal({ club, onClose }) {
    if (!club) return null;
    return (
        <Modal open onClose={onClose} title="Detalle del Comité" icon="fa-eye" iconClass="text-[#0284C7]" maxWidth="sm:max-w-2xl">
            <div className="p-6">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Código</p>
                        <p className="text-base font-bold text-charcoal">{club.code || '-'}</p>
                    </div>
                    <div>
                        <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Nombre</p>
                        <p className="text-base font-bold text-charcoal">{club.name || '-'}</p>
                    </div>
                    <div className="col-span-2">
                        <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Razón Social</p>
                        <p className="text-base font-bold text-charcoal">{club.company_name || '-'}</p>
                    </div>
                    <div className="col-span-2">
                        <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Dirección</p>
                        <p className="text-base font-bold text-charcoal">{club.address || '-'}</p>
                    </div>
                    <div>
                        <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Teléfono</p>
                        <p className="text-base font-bold text-charcoal">{club.phone || '-'}</p>
                    </div>
                    <div>
                        <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Presidenta</p>
                        <p className="text-base font-bold text-charcoal">{club.president_name || 'Sin asignar'}</p>
                    </div>
                    <div>
                        <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Zona / Sector</p>
                        <p className="text-base font-bold text-charcoal">
                            {club.place_sector?.place?.title} - {club.place_sector?.sector?.title}
                        </p>
                    </div>
                    <div>
                        <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Tipo de Local</p>
                        <p className="text-base font-bold text-charcoal">{club.type_premises?.title || '-'}</p>
                    </div>
                    <div>
                        <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Estado</p>
                        <span className={`badge ${stateBadge(club.state).cls}`}>{stateBadge(club.state).label}</span>
                    </div>
                </div>

                <div className="mt-5">
                    <p className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Resoluciones del Comité</p>
                    {(club.all_resolutions || []).length === 0 ? (
                        <p className="text-sm text-earth">Sin resoluciones registradas.</p>
                    ) : (
                        <div className="overflow-x-auto border-2 border-wheat rounded-xl">
                            <table className="w-full text-xs sm:text-sm">
                                <thead className="bg-cream">
                                    <tr>
                                        <th className="px-3 py-2 text-left font-bold text-earth">Documento</th>
                                        <th className="px-3 py-2 text-left font-bold text-earth">F. Documento</th>
                                        <th className="px-3 py-2 text-left font-bold text-earth">Inicio</th>
                                        <th className="px-3 py-2 text-left font-bold text-earth">Fin</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-wheat">
                                    {(club.all_resolutions || []).map((r) => (
                                        <tr key={r.id} className="hover:bg-mist/50">
                                            <td className="px-3 py-2 font-semibold">{r.document || '-'}</td>
                                            <td className="px-3 py-2">{fmtDateTime(r.date_document)}</td>
                                            <td className="px-3 py-2">{fmtDate(r.date_start)}</td>
                                            <td className="px-3 py-2">{fmtDate(r.date_end)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </Modal>
    );
}

function AsignarPresidentaModal({ club, onClose, onSaved }) {
    const toast = useToast();
    const clubId = club?.id;
    const [detail, setDetail] = useState(null);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(false);
    const [partnerId, setPartnerId] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const loadDetail = useCallback(async () => {
        if (!clubId) return;
        setLoading(true);
        setLoadError(false);
        try {
            const res = await http.get(`${BASE}/clubs/${clubId}`);
            setDetail(res.data.data);
            setPartnerId(res.data.data.president_partner_id ? String(res.data.data.president_partner_id) : '');
        } catch {
            setLoadError(true);
        } finally {
            setLoading(false);
        }
    }, [clubId]);

    useEffect(() => {
        loadDetail();
    }, [loadDetail]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!clubId) return;
        if (!partnerId) {
            toast.error('Seleccione una socia para asignar como presidenta.');
            return;
        }
        setSubmitting(true);
        try {
            await http.post(`${BASE}/clubs/${clubId}/asignar-presidenta`, { partner_id: partnerId });
            toast.success('Presidenta asignada correctamente.');
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo asignar la presidenta.'));
        } finally {
            setSubmitting(false);
        }
    };

    const partners = detail?.partners || [];

    if (!club) return null;

    return (
        <Modal
            open
            onClose={onClose}
            title="Asignar Presidenta"
            icon="fa-user-tie"
            iconClass="text-sun"
            maxWidth="sm:max-w-lg"
        >
            <div className="p-6">
                <p className="text-sm text-earth mb-4">
                    Comité: <span className="font-bold text-charcoal">{club.name}</span> ({club.code})
                </p>
                {loading ? (
                    <div className="flex items-center justify-center py-8 text-earth">
                        <i className="fas fa-spinner fa-spin mr-2" /> Cargando socias del comité...
                    </div>
                ) : loadError ? (
                    <div className="empty-state py-8">
                        <i className="fas fa-exclamation-triangle" />
                        <p>No se pudieron cargar las socias del comité. Intenta de nuevo.</p>
                        <button type="button" onClick={loadDetail} className="btn-primary mt-3 text-xs">
                            Reintentar
                        </button>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit}>
                        <label className={labelCls}>Socia Presidenta *</label>
                        {partners.length === 0 ? (
                            <p className="text-sm text-clay font-semibold mt-1">
                                El comité no tiene socias registradas. Registre socias antes de asignar la presidenta.
                            </p>
                        ) : (
                            <select value={partnerId} onChange={(e) => setPartnerId(e.target.value)} className={inputCls} required>
                                <option value="">Seleccione una socia</option>
                                {partners.map((p) => (
                                    <option key={p.id} value={p.id}>{p.dni ? `${p.name} (${p.dni})` : p.name}</option>
                                ))}
                            </select>
                        )}
                        <div className="flex gap-3 mt-6 pt-4 border-t-2 border-wheat">
                            <button type="button" onClick={onClose} className="btn-secondary flex-1">Cancelar</button>
                            <button type="submit" disabled={submitting || partners.length === 0} className="btn-primary flex-1">
                                <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-check'} mr-2`} />
                                Asignar
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </Modal>
    );
}

const ComitesTab = forwardRef(function ComitesTab({ options, can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', state_id: '', place_sector_id: '' });
    const [page, setPage] = useState(1);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [viewing, setViewing] = useState(null);
    const [assigning, setAssigning] = useState(null);
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
            if (debouncedFilters.state_id) params.state_id = debouncedFilters.state_id;
            if (debouncedFilters.place_sector_id) params.place_sector_id = debouncedFilters.place_sector_id;
            const res = await http.get(`${BASE}/clubs`, { params });
            setData(res.data);
        } catch {
            toast.error('No se pudo cargar la lista de comités.');
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

    const placeSectorOptions = (options.place_sectors || [])
        .filter((ps) => ps.place && ps.sector)
        .map((ps) => ({
            id: ps.id,
            label: `${ps.place.title} - ${ps.sector.title}`,
            placeTitle: ps.place.title,
        }))
        .sort((a, b) => {
            const numA = parseInt(a.placeTitle.match(/\d+/)?.[0] || '0', 10);
            const numB = parseInt(b.placeTitle.match(/\d+/)?.[0] || '0', 10);
            if (numA !== numB) return numA - numB;
            return a.placeTitle.localeCompare(b.placeTitle, 'es');
        });

    const openEdit = (club) => {
        setEditing(club);
        setFormMode('edit');
        setFormOpen(true);
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/clubs/${deleting.id}`);
            toast.success('Comité eliminado exitosamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar el comité.'));
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
                            placeholder="Buscar por código o nombre..."
                            className="w-full pl-10 pr-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                        />
                    </div>
                </div>
                <div className="w-full sm:w-[300px] lg:w-[300px] shrink-0">
                    <label className={labelCls}>Zona / Sector</label>
                    <select
                        value={filters.place_sector_id}
                        onChange={(e) => setFilter('place_sector_id', e.target.value)}
                        className={inputCls}
                    >
                        <option value="">Todas las Zonas / Sectores</option>
                        {placeSectorOptions.map((ps) => (
                            <option key={ps.id} value={ps.id}>{ps.label}</option>
                        ))}
                    </select>
                </div>
                <div className="w-full sm:w-40 lg:w-40 shrink-0">
                    <label className={labelCls}>Estado</label>
                    <select value={filters.state_id} onChange={(e) => setFilter('state_id', e.target.value)} className={inputCls}>
                        <option value="">Estados</option>
                        {(options.states || []).map((state) => (
                            <option key={state.id} value={state.id}>{state.title}</option>
                        ))}
                    </select>
                </div>
                <div className="w-full sm:w-auto shrink-0 flex flex-col">
                <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', state_id: '', place_sector_id: '' });
                            setPage(1);
                        }}
                        className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf border border-lead rounded-md px-2.5 py-1.5 hover:opacity-80 whitespace-nowrap shrink-0 self-end"
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
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando comités...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[760px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">Código</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Nombre</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Zona / Sector</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Presidenta</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Estado</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6}>
                                        <div className="empty-state">
                                            <i className="fas fa-people-roof" />
                                            <p>No hay comités registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((club) => {
                                    const status = stateBadge(club.state);
                                    return (
                                        <tr key={club.id} className="row-enter">
                                            <td className="px-3 sm:px-4 py-3 font-semibold">{club.code || '-'}</td>
                                            <td className="px-3 sm:px-4 py-3">
                                                <div className="font-semibold">{club.name || 'Sin nombre'}</div>
                                                {club.company_name && <div className="text-xs text-earth">{club.company_name}</div>}
                                            </td>
                                            <td className="px-3 sm:px-4 py-3 text-earth">
                                                {club.place_sector?.place?.title} - {club.place_sector?.sector?.title}
                                            </td>
                                            <td className="px-3 sm:px-4 py-3">
                                                {club.president_name ? (
                                                    <span className="text-charcoal font-semibold">{club.president_name}</span>
                                                ) : (
                                                    <span className="text-xs text-clay italic">Sin asignar</span>
                                                )}
                                            </td>
                                            <td className="px-3 sm:px-4 py-3">
                                                <span className={`badge ${status.cls}`}>{status.label}</span>
                                            </td>
                                            <td className="px-3 sm:px-4 py-3 text-center">
                                                <div className="inline-grid grid-cols-[repeat(4,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => setViewing(club)}
                                                        className="btn-action col-start-1 bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                        title="Ver"
                                                    >
                                                        <i className="fas fa-eye" />
                                                    </button>
                                                    {can.edit && (
                                                        <button
                                                            type="button"
                                                            onClick={() => openEdit(club)}
                                                            className="btn-action col-start-2 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                            title="Editar"
                                                        >
                                                            <i className="fas fa-edit" />
                                                        </button>
                                                    )}
                                                    {can.edit && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setAssigning(club)}
                                                            className="btn-action col-start-3 bg-leaf-light text-leaf hover:bg-leaf hover:text-white"
                                                            title="Asignar Presidenta"
                                                        >
                                                            <i className="fas fa-user-tie" />
                                                        </button>
                                                    )}
                                                    {can.del && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleting(club)}
                                                            className="btn-action col-start-4 bg-clay-light text-clay hover:bg-clay hover:text-white"
                                                            title="Eliminar"
                                                        >
                                                            <i className="fas fa-trash" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
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
                <ClubFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    club={editing}
                    options={options}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                    }}
                />
            )}

            <ClubViewModal
                club={viewing}
                onClose={() => setViewing(null)}
            />

            {assigning && (
                <AsignarPresidentaModal
                    key={assigning.id}
                    club={assigning}
                    onClose={() => setAssigning(null)}
                    onSaved={() => {
                        setAssigning(null);
                        load();
                    }}
                />
            )}

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Comité"
                message="Se eliminará este comité de forma permanente."
                details={deleting ? [
                    { label: 'Comité', value: deleting.name },
                    { label: 'Código', value: deleting.code },
                ] : []}
            />
        </>
    );
});

export default ComitesTab;
