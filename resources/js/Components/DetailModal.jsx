import Modal from './Modal';

/**
 * Primitivos compartidos para los modales de *detalle* (solo lectura).
 *
 * Objetivo: que todos los modales de detalle del sistema se vean iguales —
 * misma separación, misma jerarquía entre el rótulo de un dato y su valor,
 * y mismos grupos con título de sección. Antes cada modal los escribía a
 * mano con clases distintas (`text-slate-500` / `text-slate-600`, `space-y-5`
 * / `space-y-6`, etc.).
 *
 *   <DetailModal open title="Detalle del Socio" icon="fa-user" onClose={...}>
 *     <DetailGroup>
 *       <Field label="Nombre" value={nombre} wide />
 *       <FieldGrid>
 *         <Field label="DNI" value={dni} mono />
 *         <Field label="Estado">{badge}</Field>
 *       </FieldGrid>
 *     </DetailGroup>
 *     <DetailGroup title="Datos clínicos" icon="fa-notes-medical">
 *       ...
 *     </DetailGroup>
 *   </DetailModal>
 */

export default function DetailModal({ open = true, onClose, title, icon = 'fa-eye', iconClass = 'text-blue', maxWidth = 'sm:max-w-2xl', children }) {
    return (
        <Modal open={open} onClose={onClose} title={title} icon={icon} iconClass={iconClass} maxWidth={maxWidth}>
            <div className="p-5 sm:p-7">{children}</div>
        </Modal>
    );
}

/** Grupo de datos. Se separa del grupo anterior con un divisor (ver app.css). */
export function DetailGroup({ title, icon, children }) {
    return (
        <section className="detail-group">
            {title && (
                <h4 className="detail-group-title">
                    {icon && <i className={`fas ${icon}`} aria-hidden="true" />}
                    {title}
                </h4>
            )}
            <div className="space-y-4">{children}</div>
        </section>
    );
}

/** Rejilla responsiva para poner varios <Field> en fila. */
export function FieldGrid({ cols = 2, children }) {
    const map = {
        2: 'sm:grid-cols-2',
        3: 'sm:grid-cols-2 md:grid-cols-3',
        4: 'sm:grid-cols-2 md:grid-cols-4',
    };
    return <div className={`grid grid-cols-1 ${map[cols] || map[2]} gap-x-6 gap-y-4`}>{children}</div>;
}

/**
 * Un dato: rótulo pequeño en mayúsculas (subordinado) + valor destacado.
 * Pasa el contenido por `value` o como `children` (para badges, listas, etc.).
 * `wide` lo hace ocupar todo el ancho dentro de una FieldGrid.
 */
export function Field({ label, value, children, mono = false, wide = false }) {
    const content = children ?? (value === 0 ? '0' : value || '—');
    return (
        <div className={wide ? 'sm:col-span-full' : undefined}>
            <span className="detail-label">{label}</span>
            <div className={`detail-value ${mono ? 'font-mono' : ''}`}>{content}</div>
        </div>
    );
}
