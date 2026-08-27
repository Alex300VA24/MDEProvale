/**
 * Pie de acciones estándar para los modales de formulario.
 *
 * Regla del sistema: el botón de envío se llama exactamente **Guardar** al
 * registrar y **Actualizar** al editar (sin sufijos como "Guardar Ingreso").
 * El botón de cancelar siempre se llama **Cancelar**.
 *
 *   <ModalActions mode={mode} submitting={submitting} onCancel={onClose} />
 */
export default function ModalActions({ mode = 'create', submitting = false, onCancel, cancelLabel = 'Cancelar', bordered = false }) {
    const isEdit = mode === 'edit';
    return (
        <div className={`flex gap-3 pt-2 ${bordered ? 'mt-6 border-t border-mist pt-4' : ''}`}>
            <button type="button" onClick={onCancel} className="btn-secondary flex-1 text-xs sm:text-sm">
                {cancelLabel}
            </button>
            <button type="submit" disabled={submitting} className="btn-primary flex-1 text-xs sm:text-sm disabled:opacity-60">
                <i className={`fas ${submitting ? 'fa-spinner fa-spin' : isEdit ? 'fa-rotate' : 'fa-save'} mr-2`} aria-hidden="true" />
                {isEdit ? 'Actualizar' : 'Guardar'}
            </button>
        </div>
    );
}
