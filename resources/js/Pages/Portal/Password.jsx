import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import PortalLayout, { portalPath } from '../../Layouts/PortalLayout';

function PasswordField({ id, label, value, onChange, error, autoComplete, helper }) {
    const [visible, setVisible] = useState(false);
    const describedBy = [helper ? `${id}-help` : null, error ? `${id}-error` : null]
        .filter(Boolean)
        .join(' ') || undefined;

    return (
        <div className="password-field">
            <label htmlFor={id}>{label}</label>
            <div className="password-input-wrap">
                <i className="fas fa-lock" aria-hidden="true" />
                <input
                    id={id}
                    name={id}
                    type={visible ? 'text' : 'password'}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    autoComplete={autoComplete}
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    required
                />
                <button
                    type="button"
                    className="password-toggle"
                    onClick={() => setVisible((current) => !current)}
                    aria-label={visible ? `Ocultar ${label.toLowerCase()}` : `Mostrar ${label.toLowerCase()}`}
                    aria-pressed={visible}
                >
                    <i className={`fas ${visible ? 'fa-eye-slash' : 'fa-eye'}`} aria-hidden="true" />
                </button>
            </div>
            {helper && <p className="password-help" id={`${id}-help`}>{helper}</p>}
            {error && <p className="password-error" id={`${id}-error`} role="alert">{error}</p>}
        </div>
    );
}

export default function Password({ passwordChangeRequired }) {
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        const firstInvalid = ['current_password', 'password', 'password_confirmation']
            .find((field) => form.errors[field]);
        if (firstInvalid) document.getElementById(firstInvalid)?.focus();
    }, [form.errors]);

    const submit = (event) => {
        event.preventDefault();
        form.post(portalPath('/portal-presidentas/cambiar-contrasena'), {
            preserveScroll: true,
        });
    };

    return (
        <div className="password-page">
            <div className="pagehead password-pagehead">
                <div>
                    <p className="eyebrow">Seguridad de cuenta</p>
                    <h1>Cambiar contraseña</h1>
                    <p className="subtitle">
                        {passwordChangeRequired
                            ? 'Debes crear una contraseña personal antes de continuar.'
                            : 'Actualiza tu contraseña sin salir del portal.'}
                    </p>
                </div>
            </div>

            <section className="section password-card" aria-labelledby="password-form-title">
                <div className="section-head">
                    <div>
                        <h2 id="password-form-title">Nueva contraseña</h2>
                        <p>Usa mínimo 8 caracteres. No puede ser igual a tu DNI.</p>
                    </div>
                </div>
                <div className="section-body">
                    <form className="password-form" onSubmit={submit} noValidate>
                        <PasswordField
                            id="current_password"
                            label="Contraseña actual"
                            value={form.data.current_password}
                            onChange={(value) => form.setData('current_password', value)}
                            error={form.errors.current_password}
                            autoComplete="current-password"
                        />
                        <PasswordField
                            id="password"
                            label="Nueva contraseña"
                            value={form.data.password}
                            onChange={(value) => form.setData('password', value)}
                            error={form.errors.password}
                            autoComplete="new-password"
                            helper="Mínimo 8 caracteres y diferente a tu DNI."
                        />
                        <PasswordField
                            id="password_confirmation"
                            label="Confirmar nueva contraseña"
                            value={form.data.password_confirmation}
                            onChange={(value) => form.setData('password_confirmation', value)}
                            error={form.errors.password_confirmation}
                            autoComplete="new-password"
                        />
                        <button className="password-submit" type="submit" disabled={form.processing}>
                            <i className={`fas ${form.processing ? 'fa-spinner fa-spin' : 'fa-shield-halved'}`} aria-hidden="true" />
                            {form.processing ? 'Guardando…' : 'Guardar y continuar'}
                        </button>
                    </form>
                </div>
            </section>
        </div>
    );
}

Password.layout = (page) => <PortalLayout title="Cambiar contraseña">{page}</PortalLayout>;
