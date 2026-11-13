import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import Modal from './Modal';
import ModalActions from './ModalActions';
import { useToast } from './Toast';
import http from '../http';
import errorMessage from '../errorMessage';

const labelClass = 'block mb-1 text-xs font-bold uppercase tracking-wider text-slate';
const inputClass = 'w-full border-2 border-mist bg-white px-3 py-2.5 text-sm font-semibold text-charcoal focus:border-blue focus:outline-none focus:ring-2 focus:ring-blue/15 disabled:bg-gray-100 disabled:text-slate';
const errorClass = 'mt-1 text-xs font-semibold text-clay';

const EMPTY_PASSWORDS = { current_password: '', password: '', password_confirmation: '' };

export default function ProfileModal({ open, onClose }) {
    const toast = useToast();
    const [profile, setProfile] = useState(null);
    const [form, setForm] = useState(null);
    const [passwords, setPasswords] = useState(EMPTY_PASSWORDS);
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (!open) return;
        let cancelled = false;
        setLoading(true);
        setErrors({});
        setPasswords(EMPTY_PASSWORDS);
        http.get('/perfil')
            .then(({ data }) => {
                if (cancelled) return;
                setProfile(data.data);
                setForm({
                    names: data.data.names ?? '',
                    father_surname: data.data.father_surname ?? '',
                    mother_surname: data.data.mother_surname ?? '',
                    email: data.data.email ?? '',
                    dni: data.data.dni ?? '',
                });
            })
            .catch((err) => {
                if (cancelled) return;
                toast?.error(errorMessage(err, 'No se pudo cargar el perfil.'));
                onClose();
            })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const setField = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }));
    const setPassword = (key) => (e) => setPasswords((p) => ({ ...p, [key]: e.target.value }));
    const fieldError = (key) => errors[key]?.[0] && <p className={errorClass} role="alert">{errors[key][0]}</p>;

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});
        try {
            const payload = { ...form };
            if (passwords.password) Object.assign(payload, passwords);
            const { data } = await http.put('/perfil', payload);
            toast?.success(data.message || 'Perfil actualizado.');
            // Refresca el nombre/correo compartidos (auth.user) sin recargar la página.
            router.reload({ only: ['auth'] });
            onClose();
        } catch (err) {
            if (err?.response?.status === 422) setErrors(err.response.data.errors || {});
            toast?.error(errorMessage(err, 'No se pudo actualizar el perfil.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open={open} onClose={onClose} title="Mi perfil" icon="fa-user" maxWidth="sm:max-w-xl">
            {loading || !form ? (
                <div className="p-8 text-center text-slate text-sm font-semibold">
                    <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />Cargando perfil...
                </div>
            ) : (
                <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-4" noValidate>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className={labelClass} htmlFor="pf-username">Usuario</label>
                            <input id="pf-username" value={profile.username ?? ''} disabled className={inputClass} />
                        </div>
                        <div>
                            <label className={labelClass} htmlFor="pf-rol">Rol</label>
                            <input id="pf-rol" value={profile.rol ?? ''} disabled className={inputClass} />
                        </div>
                    </div>

                    <div>
                        <label className={labelClass} htmlFor="pf-names">Nombres <span className="text-clay">(*)</span></label>
                        <input id="pf-names" value={form.names} onChange={setField('names')} maxLength={150} required className={inputClass} />
                        {fieldError('names')}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className={labelClass} htmlFor="pf-father">Apellido paterno <span className="text-clay">(*)</span></label>
                            <input id="pf-father" value={form.father_surname} onChange={setField('father_surname')} maxLength={100} required className={inputClass} />
                            {fieldError('father_surname')}
                        </div>
                        <div>
                            <label className={labelClass} htmlFor="pf-mother">Apellido materno <span className="text-clay">(*)</span></label>
                            <input id="pf-mother" value={form.mother_surname} onChange={setField('mother_surname')} maxLength={100} required className={inputClass} />
                            {fieldError('mother_surname')}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className={labelClass} htmlFor="pf-dni">DNI <span className="text-clay">(*)</span></label>
                            <input id="pf-dni" value={form.dni} onChange={setField('dni')} inputMode="numeric" maxLength={8} required className={inputClass} />
                            {fieldError('dni')}
                        </div>
                        <div>
                            <label className={labelClass} htmlFor="pf-email">Correo <span className="text-clay">(*)</span></label>
                            <input id="pf-email" type="email" value={form.email} onChange={setField('email')} maxLength={255} required className={inputClass} />
                            {fieldError('email')}
                        </div>
                    </div>

                    <fieldset className="border-t border-mist pt-4 space-y-4">
                        <legend className="text-xs font-bold uppercase tracking-wider text-slate pr-2">Cambiar contraseña (opcional)</legend>
                        <div>
                            <label className={labelClass} htmlFor="pf-current">Contraseña actual</label>
                            <input id="pf-current" type="password" autoComplete="current-password" value={passwords.current_password} onChange={setPassword('current_password')} className={inputClass} />
                            {fieldError('current_password')}
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className={labelClass} htmlFor="pf-new">Nueva contraseña</label>
                                <input id="pf-new" type="password" autoComplete="new-password" value={passwords.password} onChange={setPassword('password')} className={inputClass} />
                                {fieldError('password')}
                            </div>
                            <div>
                                <label className={labelClass} htmlFor="pf-confirm">Confirmar contraseña</label>
                                <input id="pf-confirm" type="password" autoComplete="new-password" value={passwords.password_confirmation} onChange={setPassword('password_confirmation')} className={inputClass} />
                            </div>
                        </div>
                        <p className="text-xs text-slate">Mínimo 8 caracteres, con números y símbolos.</p>
                    </fieldset>

                    <ModalActions mode="edit" submitting={submitting} onCancel={onClose} bordered />
                </form>
            )}
        </Modal>
    );
}
