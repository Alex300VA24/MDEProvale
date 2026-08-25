import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/responsables-raciones';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

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
                    <button type="button" onClick={onClose} className="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary flex-1">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} /> {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

const RacionesTab = forwardRef(function RacionesTab({ can }, ref) {
    const toast = useToast();
    const [raciones, setRaciones] = useState(null);
    const [loadError, setLoadError] = useState(false);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [yearFilter, setYearFilter] = useState('');

    const load = useCallback(async () => {
        try {
            const res = await http.get(`${BASE}/raciones`);
            setRaciones(res.data.data);
        } catch {
            setLoadError(true);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    useImperativeHandle(ref, () => ({
        openCreate: () => {
            setFormMode('create');
            setEditing(null);
            setFormOpen(true);
        },
    }));

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

    if (loadError) {
        return (
            <div className="empty-state">
                <i className="fas fa-exclamation-triangle" />
                <p>No se pudieron cargar los datos de la sección. Recarga la página.</p>
            </div>
        );
    }

    return (
        <>
            <div className="bg-gray-50 rounded-xl p-3 sm:p-4 mb-4">
                <div className="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div className="w-full sm:w-40">
                        <label className={labelCls}>Año</label>
                        <select value={yearFilter} onChange={(e) => setYearFilter(e.target.value)} className={inputCls}>
                            <option value="">Todos los años</option>
                            {(raciones || []).length > 0 &&
                                [...new Set(raciones.map((r) => String(r.year)))]
                                    .sort((a, b) => Number(b) - Number(a))
                                    .map((y) => (
                                        <option key={y} value={y}>{y}</option>
                                    ))}
                        </select>
                    </div>
                    {yearFilter !== '' && (
                        <button
                            type="button"
                            onClick={() => setYearFilter('')}
                            className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf hover:opacity-80 whitespace-nowrap shrink-0 self-end"
                        >
                            <i className="fa-solid fa-sliders" /> Limpiar filtros
                        </button>
                    )}
                </div>
            </div>

            {!raciones && (
                <div className="flex items-center justify-center py-10 text-earth">
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando...
                </div>
            )}

            {raciones && (
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
                        {raciones.length === 0 ? (
                            <tr>
                                <td colSpan={5}>
                                    <div className="empty-state">
                                        <i className="fas fa-utensils" />
                                        <p>No hay raciones registradas</p>
                                    </div>
                                </td>
                            </tr>
                        ) : (() => {
                            const visible = raciones.filter((r) => (yearFilter !== '' ? String(r.year) === String(yearFilter) : true));
                            if (visible.length === 0) {
                                return (
                                    <tr>
                                        <td colSpan={5}>
                                            <div className="empty-state">
                                                <i className="fas fa-calendar-xmark" />
                                                <p>No hay raciones para el año seleccionado</p>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }
                            return visible.map((r) => (
                                <tr key={r.id} className="row-enter">
                                    <td className="px-3 sm:px-4 py-3 font-bold">{r.year}</td>
                                    <td className="px-3 sm:px-4 py-3">{r.racion_hojuelas_gramos} g</td>
                                    <td className="px-3 sm:px-4 py-3">{r.racion_leche_militros} ml</td>
                                    <td className="px-3 sm:px-4 py-3 text-center">
                                        <span className={`badge ${r.active ? 'badge-current' : 'badge-expired'}`}>
                                            {r.active ? 'Vigente' : 'Vencido'}
                                        </span>
                                    </td>
                                    <td className="px-3 sm:px-4 py-3 text-center">
                                        <div className="inline-grid grid-cols-[repeat(2,2.25rem)] items-center justify-items-center gap-2">
                                            {can.edit && (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setFormMode('edit');
                                                        setEditing(r);
                                                        setFormOpen(true);
                                                    }}
                                                    className="btn-action col-start-1 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                    title="Editar"
                                                >
                                                    <i className="fas fa-edit" />
                                                </button>
                                            )}
                                            {can.del && (
                                                <button
                                                    type="button"
                                                    onClick={() => setDeleting(r)}
                                                    className="btn-action col-start-2 bg-clay-light text-clay hover:bg-clay hover:text-white"
                                                    title="Eliminar"
                                                >
                                                    <i className="fas fa-trash" />
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ));
                        })()}
                    </tbody>
                </table>
            </div>
            )}

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
                details={deleting ? [
                    { label: 'Año', value: deleting.year },
                    { label: 'Ración Hojuelas', value: `${deleting.racion_hojuelas_gramos} g` },
                    { label: 'Ración Leche', value: `${deleting.racion_leche_militros} ml` },
                ] : []}
            />
        </>
    );
});

export default RacionesTab;
