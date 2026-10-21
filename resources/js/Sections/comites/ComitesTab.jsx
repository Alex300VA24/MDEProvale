import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import DetailModal, { DetailGroup, Field, FieldGrid } from '../../Components/DetailModal';
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
const localToday = () => {
    const date = new Date();
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

function resolutionLabel(res) {
    if (!res) return '';
    if (!res.document) return `Resolución #${res.id}`;
    const start = fmtDate(res.date_start);
    const end = fmtDate(res.date_end);
    if (start && end) {
        return `${res.document} (${start} - ${end})`;
    }
    if (start) {
        return `${res.document} (${start})`;
    }
    if (res.date_document) {
        return `${res.document} (${fmtDate(res.date_document)})`;
    }
    return res.document;
}

function ClubFormModal({ mode, club, options, onClose, onSaved }) {
    const toast = useToast();
    const maxUploadMb = Number(usePage().props.upload_max_mb || 10);
    const [code, setCode] = useState(() => {
        if (mode === 'edit' && club) return club.code || '';
        return options.next_code ? String(options.next_code) : '';
    });
    const [name, setName] = useState(mode === 'edit' && club ? club.name || '' : '');
    const [companyName, setCompanyName] = useState(mode === 'edit' && club ? club.company_name || '' : '');
    const [address, setAddress] = useState(mode === 'edit' && club ? club.address || '' : '');
    const [phone, setPhone] = useState(mode === 'edit' && club ? club.phone || '' : '');
    const [observation, setObservation] = useState(mode === 'edit' && club ? club.observation || '' : '');
    const [resolutionId, setResolutionId] = useState(mode === 'edit' && club ? club.resolution_id ?? '' : '');
    const [placeSectorId, setPlaceSectorId] = useState(mode === 'edit' && club ? club.place_sector_id ?? '' : '');
    const [typePremisesId, setTypePremisesId] = useState(mode === 'edit' && club ? club.type_premises_id ?? '' : '');
    const [stateId, setStateId] = useState(mode === 'edit' && club ? club.state_id ?? '' : '');
    const [statusReason, setStatusReason] = useState('RENUNCIA');
    const [statusNotes, setStatusNotes] = useState('');
    const [resignationFile, setResignationFile] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (mode !== 'edit' && !code && options.next_code) {
            setCode(String(options.next_code));
        }
    }, [mode, options.next_code]);

    const isCodeTaken = Boolean(
        code.trim() &&
        (options.existing_codes || []).some((item) => {
            const existingCode = typeof item === 'object' ? item.code : item;
            const existingId = typeof item === 'object' ? item.id : null;
            if (mode === 'edit' && club && (club.id === existingId || String(club.code || '').trim().toLowerCase() === code.trim().toLowerCase())) {
                return false;
            }
            const cleanVal = code.trim().toLowerCase();
            const cleanExisting = String(existingCode).trim().toLowerCase();
            if (cleanVal === cleanExisting) return true;
            if (/^\d+$/.test(cleanVal) && /^\d+$/.test(cleanExisting)) {
                return parseInt(cleanVal, 10) === parseInt(cleanExisting, 10);
            }
            return false;
        })
    );

    const placeSectorOptions = (options.place_sectors || []).map((ps) => ({
        id: ps.id,
        label: `${ps.place ? ps.place.title : ''} - ${ps.sector ? ps.sector.title : ''}`,
    }));

    const resolutionOptions = (options.resolutions || []).map((r) => ({ id: r.id, label: resolutionLabel(r) }));

    if (mode === 'edit' && club?.resolution && !resolutionOptions.some((ro) => ro.id === club.resolution.id)) {
        resolutionOptions.unshift({
            id: club.resolution.id,
            label: resolutionLabel(club.resolution),
        });
    }

    const editableStates = (options.states || []).filter(
        (state) => ['VIG', 'INA'].includes(state.abbreviation) || (mode === 'edit' && String(state.id) === String(club?.state_id))
    );
    const statusChanged = mode === 'edit' && String(stateId) !== String(club.state_id);
    const goingInactive = statusChanged && editableStates.find((state) => String(state.id) === String(stateId))?.abbreviation === 'INA';

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!code || !name || !companyName || !address || !resolutionId || !placeSectorId || !typePremisesId) {
            toast.error('Complete los campos obligatorios del comité (incluye Razón Social).');
            return;
        }
        if (isCodeTaken) {
            toast.error('El código ingresado ya está registrado.');
            return;
        }
        if (statusChanged && goingInactive && !statusNotes.trim()) {
            toast.error('Ingrese el detalle del cambio de estado.');
            return;
        }
        if (statusChanged && goingInactive && statusReason === 'RENUNCIA' && !resignationFile) {
            toast.error('Adjunte el acta de renuncia en PDF.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                code: code.trim(),
                name: name.trim(),
                company_name: companyName,
                address: address.trim(),
                phone: phone ? phone.trim() : null,
                observation: observation ? observation.trim() : null,
                resolution_id: resolutionId,
                place_sector_id: placeSectorId,
                type_premises_id: typePremisesId,
            };
            if (mode === 'edit') {
                await http.put(`${BASE}/clubs/${club.id}`, payload);
                if (statusChanged) {
                    const statusData = new FormData();
                    statusData.append('state_id', stateId);
                    statusData.append('reason', goingInactive ? statusReason : 'OTRO');
                    statusData.append('notes', goingInactive ? statusNotes.trim() : '');
                    statusData.append('effective_date', localToday());
                    if (goingInactive && statusReason === 'RENUNCIA' && resignationFile) statusData.append('acta', resignationFile);
                    await http.post(`${BASE}/clubs/${club.id}/status-change`, statusData, { headers: { 'Content-Type': 'multipart/form-data' } });
                }
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
            title={mode === 'edit' ? 'Editar Comité' : 'Registrar Comité'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-plus-circle'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-3xl"
        >
            <form onSubmit={handleSubmit} className="p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label className={labelCls}>Código *</label>
                        <input
                            type="text"
                            value={code}
                            onChange={(e) => setCode(e.target.value)}
                            className={`${inputCls} ${isCodeTaken ? 'border-clay focus:border-clay' : ''}`}
                            required
                            maxLength={20}
                        />
                        {isCodeTaken && (
                            <span className="text-clay text-xs font-semibold mt-1 block">
                                <i className="fas fa-exclamation-circle mr-1" /> Código ya registrado
                            </span>
                        )}
                    </div>
                    <div>
                        <label className={labelCls}>Nombre del Comité *</label>
                        <input type="text" value={name} onChange={(e) => setName(e.target.value)} className={inputCls} required maxLength={100} />
                    </div>
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Razón Social *</label>
                        <select value={companyName} onChange={(e) => setCompanyName(e.target.value)} className={inputCls} required>
                            <option value="">Seleccione...</option>
                            <option value="CDM">CDM - Club de Madres</option>
                            <option value="CVL">CVL - Centro de Vaso de Leche</option>
                            <option value="OSB">OSB - Organización de Sociedades de Base</option>
                        </select>
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
                    {mode === 'edit' && (
                        <div className="sm:col-span-2 rounded-xl border-2 border-wheat bg-base p-4 space-y-3">
                            <p className="text-xs font-bold uppercase tracking-wider text-charcoal">Cambio de estado del comité</p>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div><label htmlFor="committee-state" className={labelCls}>Estado</label><select id="committee-state" value={stateId} onChange={(event) => setStateId(event.target.value)} className={inputCls}>{editableStates.map((state) => <option key={state.id} value={state.id}>{state.title}</option>)}</select></div>
                                {goingInactive && <div><label htmlFor="committee-reason" className={labelCls}>Motivo</label><select id="committee-reason" value={statusReason} onChange={(event) => setStatusReason(event.target.value)} className={inputCls}><option value="RENUNCIA">Renuncia</option><option value="OTRO">Otro</option></select></div>}
                            </div>
                            {goingInactive && <>
                                <div><label htmlFor="committee-status-notes" className={labelCls}>Detalle del cambio</label><textarea id="committee-status-notes" rows="2" value={statusNotes} onChange={(event) => setStatusNotes(event.target.value)} className={inputCls} /></div>
                                {statusReason === 'RENUNCIA' && <div><label htmlFor="resignation-file" className={labelCls}>Acta de renuncia (PDF; máx. {maxUploadMb} MB)</label><input id="resignation-file" type="file" accept=".pdf,application/pdf" onChange={(event) => {
                                    const file = event.target.files?.[0] || null;
                                    if (file && file.size > maxUploadMb * 1024 * 1024) {
                                        toast.error(`El archivo no debe exceder los ${maxUploadMb} MB.`);
                                        event.target.value = '';
                                        setResignationFile(null);
                                        return;
                                    }
                                    setResignationFile(file);
                                }} className="block w-full text-xs text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-leaf file:px-3 file:py-2 file:font-bold file:text-white" /></div>}
                            </>}
                        </div>
                    )}
                </div>
                <div className="flex gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" disabled={submitting || isCodeTaken} className="btn-primary flex-1">
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
        <DetailModal open onClose={onClose} title="Detalle del comité" icon="fa-people-roof" maxWidth="sm:max-w-2xl">
            <DetailGroup>
                <FieldGrid>
                    <Field label="Código" value={club.code} />
                    <Field label="Nombre" value={club.name} />
                    <Field label="Razón social" value={club.company_name} wide />
                    <Field label="Dirección" value={club.address} wide />
                    <Field label="Teléfono" value={club.phone} />
                    <Field label="Presidenta" value={club.president_name || 'Sin asignar'} />
                    <Field label="Zona / Sector" value={[club.place_sector?.place?.title, club.place_sector?.sector?.title].filter(Boolean).join(' - ')} />
                    <Field label="Tipo de local" value={club.type_premises?.title} />
                    <Field label="Estado">
                        <span className={`badge ${stateBadge(club.state).cls}`}>{stateBadge(club.state).label}</span>
                    </Field>
                </FieldGrid>
            </DetailGroup>

            <DetailGroup title="Resoluciones del comité" icon="fa-file-contract">
                {(club.all_resolutions || []).length === 0 ? (
                    <p className="text-sm text-earth">Sin resoluciones registradas.</p>
                ) : (
                    <div className="overflow-x-auto border border-mist rounded-xl">
                        <table className="w-full text-xs sm:text-sm">
                            <thead className="bg-base">
                                <tr>
                                    <th className="px-3 py-2 text-left font-heading font-semibold uppercase tracking-wider text-slate">Documento</th>
                                    <th className="px-3 py-2 text-left font-heading font-semibold uppercase tracking-wider text-slate">Fecha de documento</th>
                                    <th className="px-3 py-2 text-left font-heading font-semibold uppercase tracking-wider text-slate">Fecha de inicio</th>
                                    <th className="px-3 py-2 text-left font-heading font-semibold uppercase tracking-wider text-slate">Fecha de fin</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-mist">
                                {(club.all_resolutions || []).map((r) => (
                                    <tr key={r.id} className="hover:bg-blue-light">
                                        <td className="px-3 py-2 font-semibold text-charcoal">{r.document || '—'}</td>
                                        <td className="px-3 py-2 text-charcoal">{fmtDate(r.date_document)}</td>
                                        <td className="px-3 py-2 text-charcoal">{fmtDate(r.date_start)}</td>
                                        <td className="px-3 py-2 text-charcoal">{fmtDate(r.date_end)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </DetailGroup>
        </DetailModal>
    );
}

const COMMITTEE_POSITIONS = [
    'PRESIDENTA',
    'VICE PRESIDENTA',
    'SECRETARIA',
    'TESORERA',
    'COORDINADORA',
    'ALMACENERA',
    'VOCAL',
];

function AsignarCargoModal({ club, onClose, onSaved }) {
    const toast = useToast();
    const clubId = club?.id;
    const [detail, setDetail] = useState(null);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(false);
    const [assignments, setAssignments] = useState({});
    const [initialAssignments, setInitialAssignments] = useState({});
    const [submitting, setSubmitting] = useState(false);

    const loadDetail = useCallback(async () => {
        if (!clubId) return;
        setLoading(true);
        setLoadError(false);
        try {
            const res = await http.get(`${BASE}/clubs/${clubId}`);
            setDetail(res.data.data);
            const currentAssignments = Object.fromEntries(
                COMMITTEE_POSITIONS.map((item) => [
                    item,
                    res.data.data.current_positions?.[item]
                        ? String(res.data.data.current_positions[item])
                        : '',
                ])
            );
            setAssignments(currentAssignments);
            setInitialAssignments(currentAssignments);
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

        const changedAssignments = COMMITTEE_POSITIONS
            .filter((item) => assignments[item] && assignments[item] !== initialAssignments[item])
            .map((item) => ({ position: item, partner_id: Number(assignments[item]) }));

        if (changedAssignments.length === 0) {
            toast.error('Seleccione o cambie por lo menos una socia.');
            return;
        }

        setSubmitting(true);
        try {
            await http.post(`${BASE}/clubs/${clubId}/asignar-cargos`, { assignments: changedAssignments });
            toast.success(changedAssignments.length === 1
                ? 'Cargo asignado correctamente.'
                : `${changedAssignments.length} cargos asignados correctamente.`);
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudieron asignar los cargos.'));
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
            title="Asignar Cargo"
            icon="fa-user-tie"
            iconClass="text-sun"
            maxWidth="sm:max-w-5xl"
        >
            <div className="p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-5 rounded-2xl border border-wheat bg-base px-4 py-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-earth">Comité</p>
                        <p className="font-heading font-bold text-charcoal">{club.name} <span className="text-earth">({club.code})</span></p>
                    </div>
                    <div className="inline-flex items-center gap-2 text-xs font-bold text-navy">
                        <i className="fas fa-users-gear" aria-hidden="true" />
                        7 cargos de directiva
                    </div>
                </div>
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
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {partners.length === 0 ? (
                            <div className="empty-state py-8">
                                <i className="fas fa-user-slash" />
                                <p>El comité no tiene socias registradas. Registre socias antes de asignar cargos.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {COMMITTEE_POSITIONS.map((item, index) => (
                                    <div key={item} className="rounded-2xl border-2 border-wheat bg-white p-4 transition-colors focus-within:border-leaf">
                                        <div className="flex items-center gap-3 mb-3">
                                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-light text-xs font-extrabold text-navy">
                                                {index + 1}
                                            </span>
                                            <label htmlFor={`cargo-${index}`} className="font-heading text-sm font-extrabold tracking-wide text-charcoal">
                                                {item}
                                            </label>
                                        </div>
                                        <select
                                            id={`cargo-${index}`}
                                            value={assignments[item] || ''}
                                            onChange={(e) => setAssignments((current) => ({ ...current, [item]: e.target.value }))}
                                            className={inputCls}
                                        >
                                            <option value="" disabled>Seleccione una socia</option>
                                            {partners.map((partner) => (
                                                <option key={partner.id} value={partner.id}>
                                                    {partner.dni ? `${partner.name} (${partner.dni})` : partner.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                ))}
                            </div>
                        )}
                        <div className="flex gap-3 pt-4 border-t-2 border-wheat">
                            <button type="button" onClick={onClose} className="btn-secondary flex-1">Cancelar</button>
                            <button type="submit" disabled={submitting || partners.length === 0} className="btn-primary flex-1">
                                <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-check'} mr-2`} />
                                Guardar cargos
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </Modal>
    );
}

const ComitesTab = forwardRef(function ComitesTab({ options, can, reloadOptions }, ref) {
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
            reloadOptions?.();
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
            reloadOptions?.();
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
                        className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf border border-leaf rounded-md px-2.5 py-1.5 hover:opacity-80 whitespace-nowrap shrink-0 self-end"
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
                                                            title="Asignar Cargo"
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
                        reloadOptions?.();
                    }}
                />
            )}

            <ClubViewModal
                club={viewing}
                onClose={() => setViewing(null)}
            />

            {assigning && (
                <AsignarCargoModal
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
