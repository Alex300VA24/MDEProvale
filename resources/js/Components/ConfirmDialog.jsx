import { createPortal } from 'react-dom';

export default function ConfirmDialog({
    open,
    onCancel,
    onConfirm,
    title,
    message,
    details = null,
    confirmLabel = 'Sí, eliminar',
    cancelLabel = 'Cancelar',
    danger = true,
    loading = false,
}) {
    if (!open) return null;

    return createPortal(
        <div className="modal-overlay is-open animate-fade-in" onClick={onCancel}>
            <div className="modal-panel sm:max-w-md" onClick={(e) => e.stopPropagation()}>
                <div className="modal-card modal-enter">
                    <div className="p-6 text-center">
                        <div className={`w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4 ${danger ? 'bg-clay-light' : 'bg-sky-light'}`}>
                            <i className={`fas fa-exclamation-triangle text-2xl ${danger ? 'text-clay' : 'text-sky'}`} />
                        </div>
                        <h3 className="font-extrabold text-charcoal text-lg mb-2">{title}</h3>
                        <p className="text-sm text-earth mb-4">{message}</p>
                        {details && details.length > 0 && (
                            <div className="bg-cream border-2 border-wheat rounded-xl p-3 mb-6 text-left space-y-1.5">
                                {details.map((d, i) => (
                                    <div key={i} className="flex items-baseline justify-between gap-3 text-sm">
                                        <span className="text-earth font-semibold shrink-0">{d.label}</span>
                                        <span className="text-charcoal font-bold text-right truncate">{d.value || '-'}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                        <div className={`flex gap-3 ${details && details.length > 0 ? '' : 'mt-2'}`}>
                            <button type="button" onClick={onCancel} disabled={loading} className="btn-secondary flex-1">
                                {cancelLabel}
                            </button>
                            <button type="button" onClick={onConfirm} disabled={loading} className={`${danger ? 'btn-danger' : 'btn-primary'} flex-1`}>
                                {loading ? (
                                    <i className="fas fa-spinner fa-spin mr-2" />
                                ) : (
                                    danger && <i className="fas fa-trash mr-2" />
                                )}
                                {confirmLabel}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>,
        document.body
    );
}
