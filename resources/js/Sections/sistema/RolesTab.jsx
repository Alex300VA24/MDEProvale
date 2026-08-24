import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import { router } from '@inertiajs/react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/sistema';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

const PERMS = [
    ['can_view', 'Ver'],
    ['can_create', 'Crear'],
    ['can_edit', 'Editar'],
    ['can_delete', 'Eliminar'],
];

function permissionsFromRol(rol, modulos) {
    const byModuleId = {};
    (rol?.modules || []).forEach((m) => {
        byModuleId[m.id] = { can_view: m.can_view, can_create: m.can_create, can_edit: m.can_edit, can_delete: m.can_delete };
    });
    const result = {};
    modulos.forEach((m) => {
        result[m.id] = byModuleId[m.id] || { can_view: false, can_create: false, can_edit: false, can_delete: false };
    });
    return result;
}

function RolFormModal({ mode, rol, modulos, onClose, onSaved }) {
    const toast = useToast();
    const [title, setTitle] = useState(rol?.title || '');
    const [description, setDescription] = useState(rol?.description || '');
    const [permissions, setPermissions] = useState(() => permissionsFromRol(rol, modulos));
    const [submitting, setSubmitting] = useState(false);

    const togglePerm = (moduleId, key) => {
        setPermissions((prev) => {
            const current = prev[moduleId] || { can_view: false, can_create: false, can_edit: false, can_delete: false };
            const next = { ...current, [key]: !current[key] };
            if (key !== 'can_view' && next[key]) next.can_view = true;
            if (key === 'can_view' && !next.can_view) {
                next.can_create = false;
                next.can_edit = false;
                next.can_delete = false;
            }
            return { ...prev, [moduleId]: next };
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!title) {
            toast.error('El nombre del rol es obligatorio.');
            return;
        }
        setSubmitting(true);
        try {
            const modulesPayload = {};
            Object.entries(permissions).forEach(([moduleId, perms]) => {
                if (perms.can_view || perms.can_create || perms.can_edit || perms.can_delete) {
                    modulesPayload[moduleId] = perms;
                }
            });
            const payload = { title, description: description || null, modules: modulesPayload };

            if (mode === 'edit') {
                await http.put(`${BASE}/roles/${rol.id}`, payload);
                toast.success('Rol actualizado correctamente.');
            } else {
                await http.post(`${BASE}/roles`, payload);
                toast.success('Rol creado correctamente.');
            }
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo guardar el rol.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open onClose={onClose} title={mode === 'edit' ? 'Editar Rol' : 'Nuevo Rol'} icon="fa-user-tag" iconClass="text-leaf" maxWidth="sm:max-w-3xl">
            <form onSubmit={handleSubmit} className="p-4 sm:p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label className={labelCls}>Nombre del Rol *</label>
                        <input type="text" value={title} onChange={(e) => setTitle(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Descripción</label>
                        <input type="text" value={description} onChange={(e) => setDescription(e.target.value)} className={inputCls} />
                    </div>
                </div>

                <h4 className="font-extrabold text-charcoal text-sm mb-3 flex items-center gap-2">
                    <i className="fas fa-shield-halved text-leaf" /> Permisos por Módulo
                </h4>
                <div className="overflow-x-auto -mx-4 sm:mx-0 mb-6">
                    <table className="data-table w-full text-xs min-w-[500px]">
                        <thead>
                            <tr>
                                <th className="px-3 py-2 text-left">Módulo</th>
                                {PERMS.map(([key, label]) => (
                                    <th key={key} className="px-3 py-2 text-center">{label}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {modulos.map((m) => (
                                <tr key={m.id}>
                                    <td className="px-3 py-2 font-semibold">{m.name}</td>
                                    {PERMS.map(([key]) => (
                                        <td key={key} className="px-3 py-2 text-center">
                                            <input
                                                type="checkbox"
                                                checked={!!permissions[m.id]?.[key]}
                                                onChange={() => togglePerm(m.id, key)}
                                                disabled={key !== 'can_view' && !permissions[m.id]?.can_view}
                                            />
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex gap-3">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1 text-xs sm:text-sm">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary flex-1 text-xs sm:text-sm">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} /> {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

const RolesTab = forwardRef(function RolesTab({ can }, ref) {
    const toast = useToast();
    const [roles, setRoles] = useState(null);
    const [modulos, setModulos] = useState([]);
    const [loading, setLoading] = useState(true);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const [rolesRes, modulosRes] = await Promise.all([
                http.get(`${BASE}/roles`),
                http.get(`${BASE}/modulos`),
            ]);
            setRoles(rolesRes.data.data);
            setModulos(modulosRes.data.data);
        } catch {
            toast.error('No se pudo cargar la lista de roles.');
        } finally {
            setLoading(false);
        }
    }, [toast]);

    useEffect(() => {
        load();
    }, [load]);

    useEffect(() => {
        const refresh = () => load();
        window.addEventListener('modules:changed', refresh);
        return () => window.removeEventListener('modules:changed', refresh);
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
            await http.delete(`${BASE}/roles/${deleting.id}`);
            toast.success('Rol eliminado correctamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar el rol.'));
            setDeleting(null);
        }
    };

    if (loading && !roles) {
        return (
            <div className="flex items-center justify-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin mr-2" /> Cargando roles...
            </div>
        );
    }

    if (!roles) return null;

    return (
        <>
            <div className="overflow-x-auto -mx-4 sm:mx-0">
                <table className="data-table w-full text-xs sm:text-sm min-w-[600px]">
                    <thead>
                        <tr>
                            <th className="px-3 sm:px-4 py-3 text-left">Rol</th>
                            <th className="px-3 sm:px-4 py-3 text-left">Descripción</th>
                            <th className="px-3 sm:px-4 py-3 text-right">Usuarios</th>
                            <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {roles.map((rol) => (
                            <tr key={rol.id} className="row-enter">
                                <td className="px-3 sm:px-4 py-3 font-semibold">
                                    {rol.title}
                                    {rol.is_protected && <span className="ml-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-light text-[#0284C7]">Base</span>}
                                </td>
                                <td className="px-3 sm:px-4 py-3 text-earth">{rol.description || '-'}</td>
                                <td className="px-3 sm:px-4 py-3 text-right">{rol.users_count}</td>
                                <td className="px-3 sm:px-4 py-3 text-center">
                                    <div className="flex items-center justify-center gap-1 sm:gap-2">
                                        {can.edit && (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setEditing(rol);
                                                    setFormMode('edit');
                                                    setFormOpen(true);
                                                }}
                                                className="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                title="Editar"
                                            >
                                                <i className="fas fa-edit" />
                                            </button>
                                        )}
                                        {can.del && !rol.is_protected && (
                                            <button
                                                type="button"
                                                onClick={() => setDeleting(rol)}
                                                className="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white"
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
                <RolFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    rol={editing}
                    modulos={modulos}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                        router.reload({ only: ['modules'], preserveState: true, preserveScroll: true });
                    }}
                />
            )}

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Rol"
                message="Se eliminará este rol de forma permanente."
                details={deleting ? [
                    { label: 'Rol', value: deleting.title },
                    { label: 'Usuarios asignados', value: deleting.users_count },
                ] : []}
            />
        </>
    );
});

export default RolesTab;
