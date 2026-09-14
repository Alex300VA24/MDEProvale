import { forwardRef, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import DetailModal, { DetailGroup, Field, FieldGrid } from '../../Components/DetailModal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Combobox from '../../Components/Combobox';
import Pagination from '../../Components/Pagination';
import { useDebounced } from './hooks';
import { formatDate, personFullName } from './format';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/socios-beneficiarios';

const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';

function PersonaFormModal({ mode, persona, options, onClose, onSaved }) {
    const toast = useToast();
    const [names, setNames] = useState(mode === 'edit' ? persona?.names ?? '' : '');
    const [dni, setDni] = useState(mode === 'edit' ? persona?.dni ?? '' : '');
    const [fatherLastname, setFatherLastname] = useState(mode === 'edit' ? persona?.father_lastname ?? '' : '');
    const [motherLastname, setMotherLastname] = useState(mode === 'edit' ? persona?.mother_lastname ?? '' : '');
    const [birthdate, setBirthdate] = useState(mode === 'edit' ? persona?.birthdate ?? '' : '');
    const [gender, setGender] = useState(mode === 'edit' ? persona?.gender ?? '' : '');
    const [address, setAddress] = useState(mode === 'edit' ? persona?.address ?? '' : '');
    const [phoneNumber, setPhoneNumber] = useState(mode === 'edit' ? persona?.phone_number ?? '' : '');
    const [placeSectorId, setPlaceSectorId] = useState(mode === 'edit' ? persona?.place_sector_id ?? '' : '');
    const [submitting, setSubmitting] = useState(false);
    const [consultingReniec, setConsultingReniec] = useState(false);
    const [reniecStatus, setReniecStatus] = useState(null);
    const [reniecPhotoToken, setReniecPhotoToken] = useState(null);

    const placeSectorOptions = (options.place_sectors ?? []).map((ps) => ({
        id: ps.id,
        label: [ps.place?.title, ps.sector?.title].filter(Boolean).join(' - '),
    }));

    const handleDniChange = (value) => {
        setDni(value.replace(/\D/g, '').slice(0, 8));
        setReniecStatus(null);
        setReniecPhotoToken(null);
    };

    const handleReniecLookup = async () => {
        if (!/^\d{8}$/.test(dni)) {
            const message = 'Ingrese un DNI válido de 8 dígitos.';
            setReniecStatus({ type: 'error', message });
            toast.error(message);
            return;
        }

        setConsultingReniec(true);
        setReniecStatus(null);

        try {
            const response = await http.post(`${BASE}/personas/reniec`, { dni }, { timeout: 22000 });
            const person = response.data.data;

            if (person.names) setNames(person.names);
            if (person.father_lastname) setFatherLastname(person.father_lastname);
            if (person.mother_lastname) setMotherLastname(person.mother_lastname);
            setAddress(person.address ?? '');
            setReniecPhotoToken(person.photo_token ?? null);

            const restriction = person.restriction && person.restriction !== 'NINGUNA'
                ? ` Restricción: ${person.restriction}.`
                : '';
            const missingAddress = person.address ? '' : ' RENIEC no devolvió una dirección.';
            setReniecStatus({
                type: restriction || missingAddress ? 'warning' : 'success',
                message: `Datos obtenidos de RENIEC.${restriction}${missingAddress}`,
            });
        } catch (err) {
            const message = errorMessage(err, 'No se pudo consultar RENIEC.');
            setReniecPhotoToken(null);
            setReniecStatus({ type: 'error', message });
            toast.error(message);
        } finally {
            setConsultingReniec(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!names || !dni || !fatherLastname || !motherLastname) {
            toast.error('Complete los campos obligatorios.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                names,
                dni,
                father_lastname: fatherLastname,
                mother_lastname: motherLastname,
                birthdate: birthdate || null,
                gender: gender || null,
                address: address || null,
                phone_number: phoneNumber || null,
                place_sector_id: placeSectorId || null,
            };
            if (mode === 'create' && reniecPhotoToken) {
                payload.reniec_photo_token = reniecPhotoToken;
            }
            if (mode === 'edit') {
                await http.put(`${BASE}/personas/${persona.id}`, payload);
            } else {
                await http.post(`${BASE}/personas`, payload);
            }
            toast.success(mode === 'edit' ? 'Persona actualizada correctamente.' : 'Persona creada exitosamente.');
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al guardar la persona.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Persona' : 'Registrar Persona'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-user-plus'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-2xl"
        >
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="sm:col-span-2">
                        <label htmlFor="persona-dni" className={labelCls}>
                            DNI <span className="text-clay">*</span>
                        </label>
                        <div className="flex flex-col sm:flex-row gap-2">
                            <input
                                id="persona-dni"
                                type="text"
                                inputMode="numeric"
                                autoComplete="off"
                                value={dni}
                                maxLength={8}
                                onChange={(e) => handleDniChange(e.target.value)}
                                className={`${inputCls} font-mono sm:flex-1`}
                                aria-describedby={mode === 'create' ? 'persona-dni-help persona-reniec-status' : undefined}
                                autoFocus={mode === 'create'}
                                required
                            />
                            {mode === 'create' && (
                                <button
                                    type="button"
                                    onClick={handleReniecLookup}
                                    disabled={consultingReniec || dni.length !== 8}
                                    className="btn-secondary min-h-[44px] sm:w-48 text-xs sm:text-sm disabled:cursor-not-allowed disabled:opacity-50"
                                    aria-busy={consultingReniec}
                                >
                                    <i
                                        className={`fas ${consultingReniec ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'} mr-2`}
                                        aria-hidden="true"
                                    />
                                    {consultingReniec ? 'Consultando...' : 'Consultar RENIEC'}
                                </button>
                            )}
                        </div>
                        {mode === 'create' && (
                            <p id="persona-dni-help" className="mt-1.5 text-xs text-earth">
                                Consulta individual. Completa nombres, apellidos y dirección disponibles.
                            </p>
                        )}
                        {mode === 'create' && (
                            <div
                                id="persona-reniec-status"
                                role={reniecStatus?.type === 'error' ? 'alert' : 'status'}
                                aria-live="polite"
                                className={reniecStatus ? `mt-2 rounded-lg border px-3 py-2 text-xs font-semibold ${
                                    reniecStatus.type === 'success'
                                        ? 'border-leaf/30 bg-leaf-light text-leaf'
                                        : reniecStatus.type === 'warning'
                                            ? 'border-sun/40 bg-sun-light text-amber-dark'
                                            : 'border-clay/30 bg-clay-light text-clay'
                                }` : ''}
                            >
                                {reniecStatus?.message}
                            </div>
                        )}
                    </div>
                    <div>
                        <label htmlFor="persona-names" className={labelCls}>
                            Nombres <span className="text-clay">*</span>
                        </label>
                        <input
                            id="persona-names"
                            type="text"
                            value={names}
                            onChange={(e) => setNames(e.target.value)}
                            className={inputCls}
                            required
                        />
                    </div>
                    <div>
                        <label htmlFor="persona-father-lastname" className={labelCls}>
                            Apellido Paterno <span className="text-clay">*</span>
                        </label>
                        <input
                            id="persona-father-lastname"
                            type="text"
                            value={fatherLastname}
                            onChange={(e) => setFatherLastname(e.target.value)}
                            className={inputCls}
                            required
                        />
                    </div>
                    <div>
                        <label htmlFor="persona-mother-lastname" className={labelCls}>
                            Apellido Materno <span className="text-clay">*</span>
                        </label>
                        <input
                            id="persona-mother-lastname"
                            type="text"
                            value={motherLastname}
                            onChange={(e) => setMotherLastname(e.target.value)}
                            className={inputCls}
                            required
                        />
                    </div>
                    <div>
                        <label htmlFor="persona-birthdate" className={labelCls}>Fecha de Nacimiento</label>
                        <input
                            id="persona-birthdate"
                            type="date"
                            value={birthdate}
                            max={new Date().toISOString().split('T')[0]}
                            onChange={(e) => setBirthdate(e.target.value)}
                            className={inputCls}
                        />
                    </div>
                    <div>
                        <label htmlFor="persona-gender" className={labelCls}>Género</label>
                        <select id="persona-gender" value={gender} onChange={(e) => setGender(e.target.value)} className={inputCls}>
                            <option value="">Seleccionar...</option>
                            <option value="F">Femenino</option>
                            <option value="M">Masculino</option>
                        </select>
                    </div>
                    <div>
                        <label htmlFor="persona-phone" className={labelCls}>Celular</label>
                        <input
                            id="persona-phone"
                            type="text"
                            inputMode="numeric"
                            value={phoneNumber}
                            maxLength={9}
                            onChange={(e) => setPhoneNumber(e.target.value.replace(/\D/g, ''))}
                            className={inputCls}
                        />
                    </div>
                    <div>
                        <label className={labelCls}>Barrio / Sector</label>
                        <Combobox
                            value={placeSectorId}
                            onChange={setPlaceSectorId}
                            options={placeSectorOptions}
                            placeholder="Seleccionar..."
                            allowClear
                        />
                    </div>
                </div>
                <div>
                    <label htmlFor="persona-address" className={labelCls}>Dirección</label>
                    <input
                        id="persona-address"
                        type="text"
                        value={address}
                        onChange={(e) => setAddress(e.target.value)}
                        className={inputCls}
                    />
                </div>
                <div className="flex gap-3 pt-2">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1 text-xs sm:text-sm">
                        Cancelar
                    </button>
                    <button type="submit" disabled={submitting || consultingReniec} className="btn-primary flex-1 text-xs sm:text-sm disabled:cursor-not-allowed disabled:opacity-50">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} aria-hidden="true" />
                        {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function ReniecPhoto({ persona, loading }) {
    const [failed, setFailed] = useState(false);
    const fullName = personFullName(persona);

    useEffect(() => {
        setFailed(false);
    }, [persona.id, persona.photo]);

    if (loading) {
        return (
            <div
                className="h-36 w-28 shrink-0 animate-pulse rounded-xl bg-wheat"
                role="status"
                aria-label="Cargando foto de RENIEC"
            />
        );
    }

    if (!persona.photo || failed) {
        return (
            <div className="flex h-36 w-28 shrink-0 flex-col items-center justify-center rounded-xl bg-gray-100 px-3 text-center text-earth">
                <i className="fas fa-user text-3xl" aria-hidden="true" />
                <span className="mt-2 text-xs font-semibold">Sin foto RENIEC</span>
            </div>
        );
    }

    return (
        <img
            src={persona.photo}
            alt={'Foto de RENIEC de ' + fullName}
            width="112"
            height="144"
            decoding="async"
            onError={() => setFailed(true)}
            className="h-36 w-28 shrink-0 rounded-xl bg-gray-100 object-cover"
        />
    );
}

function PersonaViewModal({ persona, loading, onClose }) {
    if (!persona) return null;
    const sector = persona.place_sector
        ? [persona.place_sector.place_title, persona.place_sector.sector_title].filter(Boolean).join(' - ')
        : '';
    return (
        <DetailModal open onClose={onClose} title="Detalle de la persona" icon="fa-user" maxWidth="sm:max-w-lg">
            <DetailGroup>
                <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                    <ReniecPhoto persona={persona} loading={loading} />
                    <div className="min-w-0 flex-1 self-stretch">
                        <Field label="Nombre completo" wide>
                            <span className="text-base font-bold text-charcoal">{personFullName(persona)}</span>
                        </Field>
                    </div>
                </div>
                <FieldGrid>
                    <Field label="DNI" value={persona.dni} mono />
                    <Field label="Género" value={persona.gender === 'F' ? 'Femenino' : persona.gender === 'M' ? 'Masculino' : ''} />
                    <Field label="Edad" value={persona.age_formatted} />
                    <Field label="Celular" value={persona.phone_number} />
                </FieldGrid>
            </DetailGroup>
            <DetailGroup title="Ubicación y contacto" icon="fa-location-dot">
                <Field label="Barrio / Sector" value={sector} wide />
                <Field label="Dirección" value={persona.address} wide />
            </DetailGroup>
            <DetailGroup title="Datos personales" icon="fa-id-card">
                <Field label="Fecha de nacimiento" value={formatDate(persona.birthdate)} />
            </DetailGroup>
        </DetailModal>
    );
}

const PersonasTab = forwardRef(function PersonasTab({ options, can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', gender: '', place_sector_id: '' });
    const [page, setPage] = useState(1);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [viewing, setViewing] = useState(null);
    const [viewingLoadingId, setViewingLoadingId] = useState(null);
    const [deleting, setDeleting] = useState(null);

    const debouncedFilters = useDebounced(filters, 400);

    useEffect(() => {
        setPage(1);
    }, [debouncedFilters]);

    const load = async () => {
        setLoading(true);
        try {
            const params = { per_page: 15, page };
            if (debouncedFilters.search) params.search = debouncedFilters.search;
            if (debouncedFilters.gender) params.gender = debouncedFilters.gender;
            if (debouncedFilters.place_sector_id) params.place_sector_id = debouncedFilters.place_sector_id;
            const res = await http.get(`${BASE}/personas`, { params });
            setData(res.data);
        } catch {
            toast.error('No se pudo cargar la lista de personas.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debouncedFilters, page]);

    useImperativeHandle(ref, () => ({
        openCreate: () => {
            setFormMode('create');
            setEditing(null);
            setFormOpen(true);
        },
    }));

    const setFilter = (key, value) => setFilters((prev) => ({ ...prev, [key]: value }));

    const openEdit = (persona) => {
        setEditing(persona);
        setFormMode('edit');
        setFormOpen(true);
    };

    const openView = async (persona) => {
        setViewing(persona);
        setViewingLoadingId(persona.id);

        try {
            const [detailResult, photoResult] = await Promise.allSettled([
                http.get(BASE + '/personas/' + persona.id),
                http.get(BASE + '/personas/' + persona.id + '/reniec-photo', { timeout: 22000 }),
            ]);

            if (detailResult.status === 'rejected') {
                throw detailResult.reason;
            }

            const detail = detailResult.value.data.data;
            const photo = photoResult.status === 'fulfilled'
                ? photoResult.value.data.data?.photo ?? null
                : null;

            setViewing((current) => current?.id === persona.id ? { ...detail, photo } : current);

            if (photoResult.status === 'rejected') {
                toast.info(errorMessage(photoResult.reason, 'No se pudo obtener la foto de RENIEC.'));
            }
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo cargar el detalle de la persona.'));
        } finally {
            setViewingLoadingId((current) => current === persona.id ? null : current);
        }
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/personas/${deleting.id}`);
            toast.success('Persona eliminada correctamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar la persona.'));
            setDeleting(null);
        }
    };

    const placeSectorOptions = (options.place_sectors ?? []).map((ps) => ({
        id: ps.id,
        label: [ps.place?.title, ps.sector?.title].filter(Boolean).join(' - '),
    }));

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
                            placeholder="Buscar por nombre o DNI"
                            className="w-full pl-10 pr-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                        />
                    </div>
                </div>
                <div className="w-full sm:w-[300px] lg:w-[300px] shrink-0">
                    <label className={labelCls}>Barrio / Sector</label>
                    <Combobox
                        value={filters.place_sector_id}
                        onChange={(v) => setFilter('place_sector_id', v ?? '')}
                        options={placeSectorOptions}
                        placeholder="Barrio / Sector"
                        allowClear
                    />
                </div>
                <div className="w-full sm:w-40 lg:w-40 shrink-0">
                    <label className={labelCls}>Género</label>
                    <Combobox
                        value={filters.gender}
                        onChange={(v) => setFilter('gender', v ?? '')}
                        options={[
                            { id: 'F', label: 'Femenino' },
                            { id: 'M', label: 'Masculino' },
                        ]}
                        placeholder="Género"
                        allowClear
                    />
                </div>
                <div className="w-full sm:w-auto shrink-0 flex flex-col">
                    <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', gender: '', place_sector_id: '' });
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
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando personas...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[640px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">DNI</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Nombres</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Apellidos</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Edad</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Celular</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Barrio / Sector</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="empty-state">
                                            <i className="fas fa-user" />
                                            <p>No hay personas registradas</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((persona) => (
                                    <tr key={persona.id} className="row-enter">
                                        <td className="px-3 sm:px-4 py-3 font-mono">{persona.dni || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3 font-medium">{persona.names}</td>
                                        <td className="px-3 sm:px-4 py-3">
                                            {[persona.father_lastname, persona.mother_lastname].filter(Boolean).join(' ') || '-'}
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 text-center">{persona.age_formatted || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3">{persona.phone_number || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3">
                                            {persona.place_sector
                                                ? [persona.place_sector.place_title, persona.place_sector.sector_title]
                                                      .filter(Boolean)
                                                      .join(' - ')
                                                : '-'}
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="inline-grid grid-cols-[repeat(3,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => openView(persona)}
                                                    className="btn-action col-start-1 bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver"
                                                    aria-label={'Ver detalle de ' + personFullName(persona)}
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>
                                                {can.edit && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(persona)}
                                                        className="btn-action col-start-2 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                        title="Editar"
                                                    >
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                )}
                                                {can.del && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleting(persona)}
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

            {data && (
                <div className="flex items-center justify-between px-1 sm:px-2 py-3 border-t-2 border-wheat mt-2">
                    <span className="text-xs sm:text-sm text-earth font-medium">
                        Mostrando {data.meta?.from ?? 0} - {data.meta?.to ?? 0} de {data.meta?.total ?? 0} registros
                    </span>
                    <Pagination links={data.meta?.links} meta={data.meta} onPage={setPage} loading={loading} />
                </div>
            )}

            {formOpen && (
                <PersonaFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    persona={editing}
                    options={options}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                    }}
                />
            )}

            <PersonaViewModal
                persona={viewing}
                loading={viewingLoadingId === viewing?.id}
                onClose={() => setViewing(null)}
            />

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Persona"
                message="Se eliminará esta persona de forma permanente. Si está asociada a un socio o beneficiario no se podrá eliminar."
                details={deleting ? [
                    { label: 'Nombre', value: personFullName(deleting) },
                    { label: 'DNI', value: deleting.dni },
                ] : []}
            />
        </>
    );
});

export default PersonasTab;
