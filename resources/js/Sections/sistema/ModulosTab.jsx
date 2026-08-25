import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import { router } from '@inertiajs/react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import IconSelect from '../../Components/IconSelect';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/sistema';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

function ModuloFormModal({ mode, modulo, iconOptions, iconsLoading, onClose, onSaved }) {
    const toast = useToast();
    const [name, setName] = useState(modulo?.name || '');
    const [slug, setSlug] = useState(modulo?.slug || '');
    const [description, setDescription] = useState(modulo?.description || '');
    const [icon, setIcon] = useState(modulo?.icon || '');
    const [route, setRoute] = useState(modulo?.route || '');
    const [order, setOrder] = useState(modulo?.order ?? 0);
    const [isActive, setIsActive] = useState(modulo ? modulo.is_active : true);
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!name || !slug) {
            toast.error('Nombre y slug son obligatorios.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = { name, slug, description: description || null, icon: icon || null, route: route || null, order: Number(order), is_active: isActive };
            if (mode === 'edit') {
                await http.put(`${BASE}/modulos/${modulo.id}`, payload);
                toast.success('Módulo actualizado correctamente.');
            } else {
                await http.post(`${BASE}/modulos`, payload);
                toast.success('Módulo creado correctamente.');
            }
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo guardar el módulo.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open onClose={onClose} title={mode === 'edit' ? 'Editar Módulo' : 'Nuevo Módulo'} icon="fa-puzzle-piece" iconClass="text-leaf">
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
                <div>
                    <label className={labelCls}>Nombre *</label>
                    <input type="text" value={name} onChange={(e) => setName(e.target.value)} className={inputCls} required />
                </div>
                <div>
                    <label className={labelCls}>Slug *</label>
                    <input type="text" value={slug} onChange={(e) => setSlug(e.target.value)} className={inputCls} required disabled={modulo?.is_protected} />
                </div>
                <div>
                    <label className={labelCls}>Descripción</label>
                    <input type="text" value={description} onChange={(e) => setDescription(e.target.value)} className={inputCls} />
                </div>
                <div>
                    <label id="module-icon-label" className={labelCls}>Ícono (Font Awesome)</label>
                    <IconSelect
                        id="module-icon"
                        value={icon}
                        onChange={setIcon}
                        options={iconOptions}
                        disabled={iconsLoading}
                        labelledBy="module-icon-label"
                    />
                    <p className="mt-1.5 text-[11px] font-medium text-earth">
                        {iconsLoading ? 'Cargando catálogo de iconos...' : `${iconOptions.length} iconos temáticos disponibles.`}
                    </p>
                </div>
                <div>
                    <label className={labelCls}>Ruta o sección</label>
                    <input type="text" value={route} onChange={(e) => setRoute(e.target.value)} className={inputCls} placeholder="reportes o /reportes" />
                </div>
                <div>
                    <label className={labelCls}>Orden</label>
                    <input type="number" min="0" value={order} onChange={(e) => setOrder(e.target.value)} className={inputCls} />
                </div>
                <label className="flex items-center gap-2 text-sm font-semibold text-charcoal">
                    <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} />
                    Módulo activo
                </label>
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

const ModulosTab = forwardRef(function ModulosTab({ can }, ref) {
    const toast = useToast();
    const [modulos, setModulos] = useState(null);
    const [loading, setLoading] = useState(true);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [iconOptions, setIconOptions] = useState([]);
    const [iconsLoading, setIconsLoading] = useState(true);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await http.get(`${BASE}/modulos`);
            setModulos(res.data.data);
        } catch {
            toast.error('No se pudo cargar la lista de módulos.');
        } finally {
            setLoading(false);
        }
    }, [toast]);

    useEffect(() => {
        load();
    }, [load]);

    useEffect(() => {
        let active = true;

        const loadIcons = async () => {
            try {
                const res = await http.get(`${BASE}/module-icons`);
                if (active) setIconOptions(res.data.data || []);
            } catch {
                if (active) toast.error('No se pudo cargar el catálogo de iconos.');
            } finally {
                if (active) setIconsLoading(false);
            }
        };

        loadIcons();
        return () => { active = false; };
    }, [toast]);

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
            await http.delete(`${BASE}/modulos/${deleting.id}`);
            toast.success('Módulo eliminado correctamente.');
            setDeleting(null);
            load();
            window.dispatchEvent(new CustomEvent('modules:changed'));
            router.reload({ only: ['modules'], preserveState: true, preserveScroll: true });
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar el módulo.'));
            setDeleting(null);
        }
    };

    if (loading && !modulos) {
        return (
            <div className="flex items-center justify-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin mr-2" /> Cargando módulos...
            </div>
        );
    }

    if (!modulos) return null;

    return (
        <>
            <div className="overflow-x-auto -mx-4 sm:mx-0">
                <table className="data-table w-full text-xs sm:text-sm min-w-[700px]">
                    <thead>
                        <tr>
                            <th className="px-3 sm:px-4 py-3 text-left">Módulo</th>
                            <th className="px-3 sm:px-4 py-3 text-left">Slug</th>
                            <th className="px-3 sm:px-4 py-3 text-right">Orden</th>
                            <th className="px-3 sm:px-4 py-3 text-center">Estado</th>
                            <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {modulos.map((m) => (
                            <tr key={m.id} className="row-enter">
                                <td className="px-3 sm:px-4 py-3 font-semibold">
                                    <i className={`fas ${m.icon || 'fa-square'} text-leaf mr-2`} />
                                    {m.name}
                                    {m.is_protected && <span className="ml-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-light text-[#0284C7]">Protegido</span>}
                                </td>
                                <td className="px-3 sm:px-4 py-3 text-earth font-mono">{m.slug}</td>
                                <td className="px-3 sm:px-4 py-3 text-right">{m.order}</td>
                                <td className="px-3 sm:px-4 py-3 text-center">
                                    <span className={`badge ${m.is_active ? 'badge-active' : 'badge-inactive'} px-3 py-1 rounded-full text-xs font-bold`}>
                                        {m.is_active ? 'Activo' : 'Inactivo'}
                                    </span>
                                </td>
                                <td className="px-3 sm:px-4 py-3 text-center">
                                    <div className="inline-grid grid-cols-[repeat(2,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                        {can.edit && (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setEditing(m);
                                                    setFormMode('edit');
                                                    setFormOpen(true);
                                                }}
                                                className="btn-action col-start-1 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                title="Editar"
                                            >
                                                <i className="fas fa-edit" />
                                            </button>
                                        )}
                                        {can.del && !m.is_protected && (
                                            <button
                                                type="button"
                                                onClick={() => setDeleting(m)}
                                                className="btn-action col-start-2 bg-clay-light text-clay hover:bg-clay hover:text-white"
                                                title="Eliminar"
                                            >
                                                <i className="fas fa-trash" />
                                            </button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {formOpen && (
                <ModuloFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    modulo={editing}
                    iconOptions={iconOptions}
                    iconsLoading={iconsLoading}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                        window.dispatchEvent(new CustomEvent('modules:changed'));
                        router.reload({ only: ['modules'], preserveState: true, preserveScroll: true });
                    }}
                />
            )}

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Módulo"
                message="Se eliminará este módulo de forma permanente."
                details={deleting ? [
                    { label: 'Módulo', value: deleting.name },
                    { label: 'Slug', value: deleting.slug },
                ] : []}
            />
        </>
    );
});

export default ModulosTab;
