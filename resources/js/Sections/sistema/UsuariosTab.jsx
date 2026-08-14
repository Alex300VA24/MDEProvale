import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/sistema';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

const PASSWORD_RULES = [
    { key: 'length', label: 'Al menos 8 caracteres', test: (v) => v.length >= 8 },
    { key: 'number', label: 'Al menos 1 número', test: (v) => /\d/.test(v) },
    { key: 'special', label: 'Al menos 1 caracter especial', test: (v) => /[^A-Za-z0-9]/.test(v) },
];

function isPasswordValid(password) {
    return PASSWORD_RULES.every((rule) => rule.test(password));
}

function PasswordRequirements({ password }) {
    return (
        <ul className="mt-2 space-y-1">
            {PASSWORD_RULES.map((rule) => {
                const met = rule.test(password);
                return (
                    <li key={rule.key} className={`flex items-center gap-2 text-xs font-semibold ${met ? 'text-leaf' : 'text-clay'}`}>
                        <i className={`fas ${met ? 'fa-circle-check' : 'fa-circle-xmark'}`} />
                        {rule.label}
                    </li>
                );
            })}
        </ul>
    );
}

function UserFormModal({ mode, usuario, roles, estados, onClose, onSaved }) {
    const toast = useToast();
    const isSelf = mode === 'edit' && usuario?.is_self;
    const [names, setNames] = useState(usuario?.names || '');
    const [fatherSurname, setFatherSurname] = useState(usuario?.father_surname || '');
    const [motherSurname, setMotherSurname] = useState(usuario?.mother_surname || '');
    const [username, setUsername] = useState(usuario?.username || '');
    const [email, setEmail] = useState(usuario?.email || '');
    const [dni, setDni] = useState(usuario?.dni || '');
    const [cui, setCui] = useState(usuario?.cui || '0');
    const [rolId, setRolId] = useState(usuario?.rol_id || '');
    const [stateId, setStateId] = useState(usuario?.state_id || '');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!names || !fatherSurname || !motherSurname || !username || !email || !dni || !rolId || !stateId) {
            toast.error('Complete los campos obligatorios.');
            return;
        }
        if ((mode === 'create' || password) && !isPasswordValid(password)) {
            toast.error('La contraseña no cumple los requisitos.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                names, father_surname: fatherSurname, mother_surname: motherSurname,
                username, email, dni, cui: cui || '0', rol_id: rolId, state_id: stateId,
            };
            if (password) payload.password = password;

            if (mode === 'edit') {
                await http.put(`${BASE}/usuarios/${usuario.id}`, payload);
                toast.success('Usuario actualizado correctamente.');
            } else {
                await http.post(`${BASE}/usuarios`, payload);
                toast.success('Usuario creado correctamente.');
            }
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo guardar el usuario.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open onClose={onClose} title={mode === 'edit' ? 'Editar Usuario' : 'Nuevo Usuario'} icon="fa-user" iconClass="text-leaf" maxWidth="sm:max-w-2xl">
            <form onSubmit={handleSubmit} className="p-4 sm:p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label className={labelCls}>Nombres *</label>
                        <input type="text" value={names} onChange={(e) => setNames(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Usuario *</label>
                        <input type="text" value={username} onChange={(e) => setUsername(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Apellido Paterno *</label>
                        <input type="text" value={fatherSurname} onChange={(e) => setFatherSurname(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Apellido Materno *</label>
                        <input type="text" value={motherSurname} onChange={(e) => setMotherSurname(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Email *</label>
                        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>DNI *</label>
                        <input type="text" maxLength={8} value={dni} onChange={(e) => setDni(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>
                            Rol *{isSelf && <span className="normal-case font-normal text-earth"> (no puedes cambiar tu propio rol)</span>}
                        </label>
                        <select value={rolId} onChange={(e) => setRolId(e.target.value)} className={inputCls} required disabled={isSelf}>
                            <option value="">Seleccionar...</option>
                            {roles.map((r) => (
                                <option key={r.id} value={r.id}>{r.title}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className={labelCls}>Estado *</label>
                        <select value={stateId} onChange={(e) => setStateId(e.target.value)} className={inputCls} required>
                            <option value="">Seleccionar...</option>
                            {estados.map((s) => (
                                <option key={s.id} value={s.id}>{s.title}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className={labelCls}>{mode === 'edit' ? 'Nueva Contraseña' : 'Contraseña *'}</label>
                        <div className="relative">
                            <input
                                type={showPassword ? 'text' : 'password'}
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                className={`${inputCls} pr-10`}
                                placeholder={mode === 'edit' ? 'Dejar en blanco para no cambiar' : ''}
                                required={mode === 'create'}
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((v) => !v)}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-earth hover:text-charcoal"
                                tabIndex={-1}
                                title={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                            >
                                <i className={`fas ${showPassword ? 'fa-eye-slash' : 'fa-eye'}`} />
                            </button>
                        </div>
                        {(mode === 'create' || password) && <PasswordRequirements password={password} />}
                    </div>
                </div>
                <div className="flex gap-3">
                    <button type="submit" disabled={submitting} className="btn-primary flex-1 text-xs sm:text-sm">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} /> {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                    <button type="button" onClick={onClose} className="btn-danger flex-1 text-xs sm:text-sm">Cancelar</button>
                </div>
            </form>
        </Modal>
    );
}

const UsuariosTab = forwardRef(function UsuariosTab({ can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [resetting, setResetting] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await http.get(`${BASE}/usuarios`);
            setData(res.data);
        } catch {
            toast.error('No se pudo cargar la lista de usuarios.');
        } finally {
            setLoading(false);
        }
    }, [toast]);

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

    const openEdit = (usuario) => {
        setEditing(usuario);
        setFormMode('edit');
        setFormOpen(true);
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/usuarios/${deleting.id}`);
            toast.success('Usuario eliminado correctamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar el usuario.'));
            setDeleting(null);
        }
    };

    const confirmReset = async () => {
        if (!resetting) return;
        try {
            await http.post(`${BASE}/usuarios/${resetting.id}/reset-password`);
            toast.success(`Contraseña restaurada al DNI para ${resetting.names}.`);
            setResetting(null);
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo restaurar la contraseña.'));
            setResetting(null);
        }
    };

    if (loading && !data) {
        return (
            <div className="flex items-center justify-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin mr-2" /> Cargando usuarios...
            </div>
        );
    }

    if (!data) return null;

    return (
        <>
            <div className="overflow-x-auto -mx-4 sm:mx-0">
                <table className="data-table w-full text-xs sm:text-sm min-w-[900px]">
                    <thead>
                        <tr>
                            <th className="px-3 sm:px-4 py-3 text-left">Nombre</th>
                            <th className="px-3 sm:px-4 py-3 text-left">Usuario</th>
                            <th className="px-3 sm:px-4 py-3 text-left">Email</th>
                            <th className="px-3 sm:px-4 py-3 text-left">Rol</th>
                            <th className="px-3 sm:px-4 py-3 text-left">Estado</th>
                            <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.data.length === 0 ? (
                            <tr>
                                <td colSpan={6}>
                                    <div className="empty-state">
                                        <i className="fas fa-users" />
                                        <p>No hay usuarios registrados</p>
                                    </div>
                                </td>
                            </tr>
                        ) : (
                            data.data.map((usuario) => (
                                <tr key={usuario.id} className="row-enter">
                                    <td className="px-3 sm:px-4 py-3 font-semibold">
                                        {usuario.full_name}
                                        {usuario.is_self && <span className="ml-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-light text-[#0284C7]">Tú</span>}
                                    </td>
                                    <td className="px-3 sm:px-4 py-3 text-earth">{usuario.username}</td>
                                    <td className="px-3 sm:px-4 py-3 text-earth">{usuario.email}</td>
                                    <td className="px-3 sm:px-4 py-3">
                                        <span className="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">{usuario.rol?.title || '-'}</span>
                                    </td>
                                    <td className="px-3 sm:px-4 py-3">
                                        <span className={`badge ${usuario.state?.title === 'Activo' ? 'badge-active' : 'badge-inactive'} px-3 py-1 rounded-full text-xs font-bold`}>
                                            {usuario.state?.title || 'N/A'}
                                        </span>
                                    </td>
                                    <td className="px-3 sm:px-4 py-3 text-center">
                                        <div className="flex items-center justify-center gap-1 sm:gap-2">
                                            {can.edit && (
                                                <button
                                                    type="button"
                                                    onClick={() => openEdit(usuario)}
                                                    className="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                    title="Editar"
                                                >
                                                    <i className="fas fa-edit" />
                                                </button>
                                            )}
                                            {can.edit && (
                                                <button
                                                    type="button"
                                                    onClick={() => setResetting(usuario)}
                                                    className="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Restaurar contraseña al DNI"
                                                >
                                                    <i className="fas fa-key" />
                                                </button>
                                            )}
                                            {can.del && !usuario.is_self && (
                                                <button
                                                    type="button"
                                                    onClick={() => setDeleting(usuario)}
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

            {formOpen && (
                <UserFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    usuario={editing}
                    roles={data.roles}
                    estados={data.estados}
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
                title="Eliminar Usuario"
                message="Se eliminará este usuario de forma permanente."
                details={deleting ? [
                    { label: 'Usuario', value: deleting.full_name },
                    { label: 'Usuario (login)', value: deleting.username },
                    { label: 'Rol', value: deleting.rol?.title },
                ] : []}
            />

            <ConfirmDialog
                open={!!resetting}
                onCancel={() => setResetting(null)}
                onConfirm={confirmReset}
                title="Restaurar Contraseña"
                message="La contraseña se restaurará a su número de DNI."
                details={resetting ? [
                    { label: 'Usuario', value: resetting.full_name },
                    { label: 'Usuario (login)', value: resetting.username },
                ] : []}
                danger={false}
                confirmLabel="Sí, restaurar"
            />
        </>
    );
});

export default UsuariosTab;
