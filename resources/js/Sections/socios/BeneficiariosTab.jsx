import { forwardRef, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Combobox from '../../Components/Combobox';
import Pagination from '../../Components/Pagination';
import { useDebounced } from './hooks';
import { formatDate, personFullName, personLabel } from './format';

const BASE = '/api/dashboard/socios-beneficiarios';
const SEARCH_PEOPLE = '/api/search/people';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';

function BeneficiarioFormModal({ mode, beneficiario, options, onClose, onSaved }) {
    const toast = useToast();
    const [personId, setPersonId] = useState(mode === 'edit' ? beneficiario?.person_id ?? '' : '');
    const [personLabelState, setPersonLabelState] = useState(mode === 'edit' ? personLabel(beneficiario?.person) : '');
    const [partnerId, setPartnerId] = useState(mode === 'edit' ? beneficiario?.partner_id ?? '' : '');
    const [relationshipId, setRelationshipId] = useState(mode === 'edit' ? beneficiario?.relationship_id ?? '' : '');
    const [submitting, setSubmitting] = useState(false);

    const partnerOptions = (options.partners ?? []).map((p) => ({ id: p.id, label: p.name }));
    const relationshipOptions = (options.relationships ?? []).map((r) => ({ id: r.id, label: r.title }));

    const searchPeople = async (search) => {
        try {
            const res = await http.get(SEARCH_PEOPLE, { params: { q: search, limit: 30 } });
            return (res.data?.results ?? []).map((r) => ({ id: r.id, label: r.text }));
        } catch {
            return [];
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!personId || !partnerId || !relationshipId) {
            toast.error('Complete todos los campos.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = { person_id: personId, partner_id: partnerId, relationship_id: relationshipId };
            if (mode === 'edit') {
                await http.put(`${BASE}/beneficiarios/${beneficiario.id}`, payload);
            } else {
                await http.post(`${BASE}/beneficiarios`, payload);
            }
            toast.success(mode === 'edit' ? 'Beneficiario actualizado correctamente.' : 'Beneficiario creado exitosamente.');
            onSaved();
        } catch (err) {
            const data = err.response?.data;
            if (data?.errors) {
                toast.error(Object.values(data.errors)[0][0]);
            } else {
                toast.error(data?.message || 'Ocurrió un error al guardar el beneficiario.');
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Beneficiario' : 'Nuevo Beneficiario'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-hand-holding-heart'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-xl"
        >
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
                <div>
                    <label className={labelCls}>
                        Persona Beneficiaria <span className="text-clay">*</span>
                    </label>
                    <Combobox
                        value={personId}
                        onChange={(v) => setPersonId(v ?? '')}
                        onSelect={(opt) => setPersonLabelState(opt.label)}
                        options={[]}
                        selectedLabel={personLabelState}
                        onSearch={searchPeople}
                        placeholder="Buscar por nombre o DNI..."
                        minQuery={2}
                        allowClear
                    />
                </div>
                <div>
                    <label className={labelCls}>
                        Socio (Titular) <span className="text-clay">*</span>
                    </label>
                    <Combobox
                        value={partnerId}
                        onChange={(v) => setPartnerId(v ?? '')}
                        options={partnerOptions}
                        placeholder="Seleccionar socio..."
                        allowClear
                    />
                </div>
                <div>
                    <label className={labelCls}>
                        Parentesco <span className="text-clay">*</span>
                    </label>
                    <Combobox
                        value={relationshipId}
                        onChange={(v) => setRelationshipId(v ?? '')}
                        options={relationshipOptions}
                        placeholder="Seleccionar parentesco..."
                        allowClear
                    />
                </div>
                <div className="flex gap-3 pt-2">
                    <button type="submit" disabled={submitting} className="btn-primary flex-1 text-xs sm:text-sm">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />
                        {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                    <button type="button" onClick={onClose} className="btn-secondary flex-1 text-xs sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function BeneficiarioViewModal({ beneficiario, onClose }) {
    if (!beneficiario) return null;
    return (
        <Modal
            open
            onClose={onClose}
            title="Detalle de Beneficiario"
            icon="fa-hand-holding-heart"
            iconClass="text-leaf"
            maxWidth="sm:max-w-md"
        >
            <div className="p-6 space-y-4 text-sm">
                <div>
                    <span className={labelCls}>Beneficiario</span>
                    <p className="font-semibold text-charcoal">{personFullName(beneficiario.person)}</p>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <span className={labelCls}>DNI</span>
                        <p className="font-mono">{beneficiario.person?.dni || '-'}</p>
                    </div>
                    <div>
                        <span className={labelCls}>Parentesco</span>
                        <p>{beneficiario.relationship?.title || '-'}</p>
                    </div>
                </div>
                <div>
                    <span className={labelCls}>Socio (Titular)</span>
                    <p className="font-medium">{beneficiario.partner?.name || '-'}</p>
                </div>
                <div>
                    <span className={labelCls}>Fecha de Nacimiento</span>
                    <p>{formatDate(beneficiario.person?.birthdate) || '-'}</p>
                </div>
            </div>
            <div className="px-6 pb-6">
                <button type="button" onClick={onClose} className="btn-secondary w-full text-xs sm:text-sm">
                    Cerrar
                </button>
            </div>
        </Modal>
    );
}

const BeneficiariosTab = forwardRef(function BeneficiariosTab({ options, can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', partner_id: '', relationship_id: '' });
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

    const load = async () => {
        setLoading(true);
        try {
            const params = { per_page: 10, page };
            if (debouncedFilters.search) params.search = debouncedFilters.search;
            if (debouncedFilters.partner_id) params.partner_id = debouncedFilters.partner_id;
            if (debouncedFilters.relationship_id) params.relationship_id = debouncedFilters.relationship_id;
            const res = await http.get(`${BASE}/beneficiarios`, { params });
            setData(res.data);
        } catch {
            toast.error('No se pudo cargar la lista de beneficiarios.');
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

    const openEdit = (beneficiario) => {
        setEditing(beneficiario);
        setFormMode('edit');
        setFormOpen(true);
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/beneficiarios/${deleting.id}`);
            toast.success('Beneficiario eliminado correctamente.');
            setDeleting(null);
            load();
        } catch (err) {
            const data = err.response?.data;
            toast.error(data?.message || 'No se pudo eliminar el beneficiario.');
            setDeleting(null);
        }
    };

    const partnerOptions = (options.partners ?? []).map((p) => ({ id: p.id, label: p.name }));
    const relationshipOptions = (options.relationships ?? []).map((r) => ({ id: r.id, label: r.title }));

    return (
        <>
            <form onSubmit={(e) => e.preventDefault()} className="mb-4 sm:mb-6 flex flex-col sm:flex-row gap-2 sm:gap-3">
                <div className="w-full sm:w-72">
                    <input
                        type="text"
                        value={filters.search}
                        onChange={(e) => setFilter('search', e.target.value)}
                        placeholder="Buscar por nombre o DNI"
                        className="w-full px-4 sm:px-10 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                    />
                </div>
                <div className="flex-1 sm:min-w-48">
                    <Combobox
                        value={filters.partner_id}
                        onChange={(v) => setFilter('partner_id', v ?? '')}
                        options={partnerOptions}
                        placeholder="Todos los Socios"
                        allowClear
                    />
                </div>
                <div className="flex-1 sm:min-w-40">
                    <Combobox
                        value={filters.relationship_id}
                        onChange={(v) => setFilter('relationship_id', v ?? '')}
                        options={relationshipOptions}
                        placeholder="Todos los Parentescos"
                        allowClear
                    />
                </div>
                <div>
                    <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', partner_id: '', relationship_id: '' });
                            setPage(1);
                        }}
                        className="btn-secondary text-xs sm:text-sm"
                    >
                        <i className="fas fa-broom mr-1 sm:mr-2" />
                        Limpiar
                    </button>
                </div>
            </form>

            {loading && !data && (
                <div className="flex items-center justify-center py-10 text-earth">
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando beneficiarios...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[640px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">ID</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Beneficiario</th>
                                <th className="px-3 sm:px-4 py-3 text-left">DNI</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Socio</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Parentesco</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6}>
                                        <div className="empty-state">
                                            <i className="fas fa-hand-holding-heart" />
                                            <p>No hay beneficiarios registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((beneficiario) => (
                                    <tr key={beneficiario.id} className="row-enter">
                                        <td className="px-3 sm:px-4 py-3 font-mono">{beneficiario.id}</td>
                                        <td className="px-3 sm:px-4 py-3 font-medium">{personFullName(beneficiario.person)}</td>
                                        <td className="px-3 sm:px-4 py-3 font-mono">{beneficiario.person?.dni || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3">
                                            {beneficiario.partner ? (
                                                <span className="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">
                                                    <i className="fas fa-user-tie mr-1" /> {beneficiario.partner.name}
                                                </span>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="px-3 sm:px-4 py-3">{beneficiario.relationship?.title || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="flex items-center justify-center gap-1 sm:gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setViewing(beneficiario)}
                                                    className="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver"
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>
                                                {can.edit && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(beneficiario)}
                                                        className="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                        title="Editar"
                                                    >
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                )}
                                                {can.del && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleting(beneficiario)}
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
                <div className="mt-4">
                    {loading && (
                        <p className="text-xs text-earth text-center mb-2">
                            <i className="fas fa-spinner fa-spin mr-1" /> Cargando...
                        </p>
                    )}
                    <Pagination links={data.meta?.links} meta={data.meta} onPage={setPage} loading={loading} />
                </div>
            )}

            {formOpen && (
                <BeneficiarioFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    beneficiario={editing}
                    options={options}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                    }}
                />
            )}

            <BeneficiarioViewModal beneficiario={viewing} onClose={() => setViewing(null)} />

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Beneficiario"
                message="Se eliminará el registro del beneficiario de forma permanente."
            />
        </>
    );
});

export default BeneficiariosTab;
