export default function ConfirmDialog({
    open,
    onCancel,
    onConfirm,
    title,
    message,
    confirmLabel = 'Sí, eliminar',
    cancelLabel = 'Cancelar',
    danger = true,
    loading = false,
}) {
    if (!open) return null;

    return (
        <div className="modal-overlay is-open" onClick={onCancel}>
            <div className="modal-panel sm:max-w-md" onClick={(e) => e.stopPropagation()}>
                <div className="modal-card">
                    <div className="p-6 text-center">
                        <div className="w-16 h-16 mx-auto rounded-full bg-clay-light flex items-center justify-center mb-4">
                            <i className="fas fa-exclamation-triangle text-clay text-2xl" />
                        </div>
                        <h3 className="font-extrabold text-charcoal text-lg mb-2">{title}</h3>
                        <p className="text-sm text-earth mb-6">{message}</p>
                        <div className="flex gap-3">
                            <button type="button" onClick={onCancel} disabled={loading} className="btn-secondary flex-1">
                                {cancelLabel}
                            </button>
                            <button type="button" onClick={onConfirm} disabled={loading} className="btn-danger flex-1">
                                <i className="fas fa-spinner fa-spin mr-2" hidden={!loading} />
                                {danger && !loading && <i className="fas fa-trash mr-2" />}
                                {confirmLabel}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
