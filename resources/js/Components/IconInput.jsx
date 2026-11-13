/**
 * Envuelve un input/select y dibuja un icono Font Awesome dentro, al inicio.
 * El hijo debe recibir `pl-10` en su className para no tapar el icono.
 */
export default function IconInput({ icon, children, className = '' }) {
    return (
        <div className={`relative ${className}`}>
            <i
                className={`fas ${icon} absolute left-3.5 top-1/2 -translate-y-1/2 text-earth pointer-events-none`}
                aria-hidden="true"
            />
            {children}
        </div>
    );
}
