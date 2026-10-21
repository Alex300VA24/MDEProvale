import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';

export default function PromptDialog({
    open,
    onCancel,
    onConfirm,
    title,
    message,
    label,
    placeholder = '',
    maxLength = 255,
    confirmLabel = 'Guardar',
    cancelLabel = 'Cancelar',
    required = true,
}) {
    const [value, setValue] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        if (open) {
            setValue('');
            setError('');
        }
    }, [open]);

    if (!open) return null;

    const submit = (event) => {
        event.preventDefault();
        const text = value.trim();
        if (required && !text) {
            setError('Ingrese el motivo.');
            return;
        }
        onConfirm(text);
    };

    return createPortal(
        <div className="modal-overlay is-open animate-fade-in" onClick={onCancel}>
            <div className="modal-panel sm:max-w-md" onClick={(e) => e.stopPropagation()}>
                <form onSubmit={submit} className="modal-card modal-enter">
                    <div className="p-6">
                        <div className="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4 bg-sky-light">
                            <i className="fas fa-pen text-2xl text-sky" aria-hidden="true" />
                        </div>
                        <h3 className="font-extrabold text-charcoal text-lg mb-2 text-center">{title}</h3>
                        {message && <p className="text-sm text-earth mb-4 text-center">{message}</p>}
                        {label && <label htmlFor="prompt-dialog-input" className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">{label}</label>}
                        <textarea
                            id="prompt-dialog-input"
                            autoFocus
                            rows={3}
                            value={value}
                            maxLength={maxLength}
                            onChange={(e) => { setValue(e.target.value); setError(''); }}
                            placeholder={placeholder}
                            className="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all resize-none"
                        />
                        {error && <p className="mt-1 text-xs font-semibold text-clay">{error}</p>}
                        <div className="flex gap-3 mt-5">
                            <button type="button" onClick={onCancel} className="btn-secondary flex-1">{cancelLabel}</button>
                            <button type="submit" className="btn-primary flex-1">{confirmLabel}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>,
        document.body
    );
}
