import { useCallback, useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import http from '../http';
import { useToast } from '../Components/Toast';
import Modal from '../Components/Modal';
import ConfirmDialog from '../Components/ConfirmDialog';

const BASE = '/api/dashboard/mantenimiento';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

function errorMessage(err, fallback) {
    const data = err.response?.data;
    if (data?.errors) return Object.values(data.errors)[0]?.[0] || fallback;
    return data?.message || fallback;
}

function ResponsibleCard({ title, subtitle, icon, iconClass, responsible, people, canEdit, onSaved }) {
    const toast = useToast();
    const [open, setOpen] = useState(false);
    const [personId, setPersonId] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const openModal = () => {
        setPersonId(responsible?.person_id ?? '');
        setOpen(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!personId) {
            toast.error('Seleccione una persona.');
            return;
        }
        setSubmitting(true);
        try {
            await http.put(`${BASE}/responsibles/${title.type}`, { person_id: personId });
            toast.success('Responsable actualizado correctamente.');
            setOpen(false);
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo actualizar el responsable.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="bg-cream rounded-xl p-6 border-2 border-wheat">
            <div className="flex items-center gap-3 mb-4">
                <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${iconClass}`}>
                    <i className={`fas ${icon}`} />
                </div>
                <div>
                    <p className={labelCls}>{subtitle}</p>
                    <p className="font-bold text-charcoal">{responsible?.person_name || 'Sin asignar'}</p>
                    {responsible?.person_dni && <p className="text-xs text-earth">DNI: {responsible.person_dni}</p>}
                </div>
            </div>
            {canEdit && (
                <button type="button" onClick={openModal} className="btn-primary w-full text-sm">
                    <i className="fas fa-exchange-alt mr-2" /> Cambiar {title.label}
                </button>
            )}

            <Modal open={open} onClose={() => setOpen(false)} title={`Cambiar ${title.label}`} icon={icon} iconClass="text-leaf">
                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    <div>
                        <label className={labelCls}>Seleccionar Persona</label>
                        <select value={personId} onChange={(e) => setPersonId(e.target.value)} className={inputCls} required>
                            <option value="">Seleccionar...</option>
                            {people.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.names} {p.father_lastname} {p.mother_lastname} - {p.dni}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-3 pt-2">
                        <button type="submit" disabled={submitting} className="btn-primary flex-1">
                            <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} /> Guardar
                        </button>
                        <button type="button" onClick={() => setOpen(false)} className="btn-secondary flex-1">Cancelar</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}

function RacionFormModal({ mode, racion, onClose, onSaved }) {
    const toast = useToast();
    const [year, setYear] = useState(mode === 'edit' ? racion.year : new Date().getFullYear());
    const [hojuelas, setHojuelas] = useState(mode === 'edit' ? racion.racion_hojuelas_gramos : '');
    const [leche, setLeche] = useState(mode === 'edit' ? racion.racion_leche_militros : '');
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!year || hojuelas === '' || leche === '') {
            toast.error('Complete todos los campos.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                racion_hojuelas_gramos: Number(hojuelas),
                racion_leche_militros: Number(leche),
            };
            if (mode === 'edit') {
                await http.put(`${BASE}/raciones/${racion.id}`, payload);
                toast.success('Ración actualizada correctamente.');
            } else {
                await http.post(`${BASE}/raciones`, { ...payload, year: Number(year) });
                toast.success('Ración creada correctamente.');
            }
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo guardar la ración.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open onClose={onClose} title={mode === 'edit' ? `Editar Ración ${racion.year}` : 'Nueva Ración'} icon="fa-utensils" iconClass="text-leaf">
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
                <div>
                    <label className={labelCls}>Año</label>
                    <input type="number" min="2000" max="2100" value={year} onChange={(e) => setYear(e.target.value)} className={inputCls} required readOnly={mode === 'edit'} />
                </div>
                <div>
                    <label className={labelCls}>Ración Hojuelas (gramos)</label>
                    <input type="number" step="0.01" min="0" value={hojuelas} onChange={(e) => setHojuelas(e.target.value)} className={inputCls} required placeholder="Ej: 50.00" />
                </div>
                <div>
                    <label className={labelCls}>Ración Leche (mililitros)</label>
                    <input type="number" step="0.01" min="0" value={leche} onChange={(e) => setLeche(e.target.value)} className={inputCls} required placeholder="Ej: 410.00" />
                </div>
                <div className="flex gap-3 pt-2">
                    <button type="submit" disabled={submitting} className="btn-primary flex-1">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} /> {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                    <button type="button" onClick={onClose} className="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </Modal>
    );
}

export default function Mantenimiento() {
    const { modules } = usePage().props;
    const mod = (modules ?? []).find((m) => m.slug === 'mantenimiento');
    const can = {
        view: !!mod?.can_view,
        create: !!mod?.can_create,
        edit: !!mod?.can_edit,
        del: !!mod?.can_delete,
    };

    const toast = useToast();
    const [data, setData] = useState(null);
    const [loadError, setLoadError] = useState(false);
    const [raciones, setRaciones] = useState(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);

    const load = useCallback(async () => {
        try {
            const [responsiblesRes, racionesRes] = await Promise.all([
                http.get(`${BASE}/responsibles`),
                http.get(`${BASE}/raciones`),
            ]);
            setData(responsiblesRes.data);
            setRaciones(racionesRes.data.data);
        } catch {
            setLoadError(true);
        }
    }, []);

    useEffect(() => {
        if (can.view) load();
    }, [can.view, load]);

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/raciones/${deleting.id}`);
            toast.success('Ración eliminada correctamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar la ración.'));
            setDeleting(null);
        }
    };

    if (!can.view) {
        return (
            <div className="empty-state">
                <i className="fas fa-lock" />
                <p>No tiene acceso a este módulo.</p>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
                <h3 className="font-extrabold text-charcoal text-lg sm:text-xl flex items-center gap-3">
                    <i className="fas fa-tools text-leaf" /> Mantenimiento
                </h3>
            </div>

            <div className="p-4 sm:p-6 space-y-8">
                {loadError && (
                    <div className="empty-state">
                        <i className="fas fa-exclamation-triangle" />
                        <p>No se pudieron cargar los datos de la sección. Recarga la página.</p>
                    </div>
                )}

                {!loadError && !data && (
                    <div className="flex items-center justify-center py-10 text-earth">
                        <i className="fas fa-spinner fa-spin mr-2" /> Cargando...
                    </div>
                )}

                {data && (
                    <>
                        <div>
                            <h4 className="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                                <i className="fas fa-user-tie text-leaf" /> Responsables de Almacén
                            </h4>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <ResponsibleCard
                                    title={{ type: 'chief', label: 'Subgerente de Programas Sociales' }}
                                    subtitle="Subgerencia de Programas Sociales"
                                    icon="fa-user-shield"
                                    iconClass="bg-leaf-light text-leaf"
                                    responsible={data.chief}
                                    people={data.people}
                                    canEdit={can.edit}
                                    onSaved={load}
                                />
                                <ResponsibleCard
                                    title={{ type: 'storekeeper', label: 'Encargado de PROVALE' }}
                                    subtitle="Programa Vaso de Leche"
                                    icon="fa-warehouse"
                                    iconClass="bg-sky-light text-[#0284C7]"
                                    responsible={data.storekeeper}
                                    people={data.people}
                                    canEdit={can.edit}
                                    onSaved={load}
                                />
                            </div>
                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="font-extrabold text-charcoal text-lg flex items-center gap-2">
                                    <i className="fas fa-utensils text-leaf" /> Raciones por Año
                                </h4>
                                {can.create && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setFormMode('create');
                                            setEditing(null);
                                            setFormOpen(true);
                                        }}
                                        className="btn-primary text-sm"
                                    >
                                        <i className="fas fa-plus mr-2" /> Nueva Ración
                                    </button>
                                )}
                            </div>

                            <div className="overflow-x-auto -mx-4 sm:mx-0">
                                <table className="data-table w-full text-xs sm:text-sm min-w-[500px]">
                                    <thead>
                                        <tr>
                                            <th className="px-3 sm:px-4 py-3 text-left">Año</th>
                                            <th className="px-3 sm:px-4 py-3 text-left">Ración Hojuelas (g)</th>
                                            <th className="px-3 sm:px-4 py-3 text-left">Ración Leche (ml)</th>
                                            <th className="px-3 sm:px-4 py-3 text-center">Estado</th>
                                            <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {!raciones || raciones.length === 0 ? (
                                            <tr>
                                                <td colSpan={5}>
                                                    <div className="empty-state">
                                                        <i className="fas fa-utensils" />
                                                        <p>No hay raciones registradas</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        ) : (
                                            raciones.map((r) => (
                                                <tr key={r.id} className="row-enter">
                                                    <td className="px-3 sm:px-4 py-3 font-bold">{r.year}</td>
                                                    <td className="px-3 sm:px-4 py-3">{r.racion_hojuelas_gramos} g</td>
                                                    <td className="px-3 sm:px-4 py-3">{r.racion_leche_militros} ml</td>
                                                    <td className="px-3 sm:px-4 py-3 text-center">
                                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${r.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                                            {r.active ? 'Activo' : 'Inactivo'}
                                                        </span>
                                                    </td>
                                                    <td className="px-3 sm:px-4 py-3 text-center">
                                                        <div className="flex items-center justify-center gap-2">
                                                            {can.edit && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => {
                                                                        setFormMode('edit');
                                                                        setEditing(r);
                                                                        setFormOpen(true);
                                                                    }}
                                                                    className="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                                    title="Editar"
                                                                >
                                                                    <i className="fas fa-edit" />
                                                                </button>
                                                            )}
                                                            {can.del && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setDeleting(r)}
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
                        </div>
                    </>
                )}
            </div>

            {formOpen && (
                <RacionFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    racion={editing}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                    }}
                />
            )}

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Ración"
                message="Se eliminará esta ración de forma permanente."
            />
        </div>
    );
}
