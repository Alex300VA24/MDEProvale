import { useEffect, useId, useRef } from 'react';
import { createPortal } from 'react-dom';

export default function Modal({ open, onClose, title, icon, iconClass = 'text-leaf', maxWidth = 'sm:max-w-2xl', children }) {
    const titleId = useId();
    const closeButtonRef = useRef(null);
    const dialogRef = useRef(null);
    const onCloseRef = useRef(onClose);
    onCloseRef.current = onClose;

    useEffect(() => {
        if (!open) return undefined;

        const previousOverflow = document.body.style.overflow;
        const previouslyFocused = document.activeElement;
        document.body.style.overflow = 'hidden';
        closeButtonRef.current?.focus();

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                onCloseRef.current();
                return;
            }
            if (event.key !== 'Tab') return;

            const focusable = dialogRef.current?.querySelectorAll(
                'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            if (!focusable?.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            document.body.style.overflow = previousOverflow;
            previouslyFocused?.focus?.();
        };
    }, [open]);

    if (!open) return null;

    return createPortal(
        <div className="modal-overlay is-open animate-fade-in" onClick={onClose}>
            <div className={`modal-panel ${maxWidth}`} onClick={(e) => e.stopPropagation()}>
                <div ref={dialogRef} className="modal-card modal-enter" role="dialog" aria-modal="true" aria-labelledby={titleId}>
                    <div className="modal-header">
                        <h3 id={titleId} className="font-extrabold text-charcoal text-xl flex items-center gap-2">
                            {icon && <i className={`fas ${icon} ${iconClass}`} aria-hidden="true" />}
                            {title}
                        </h3>
                        <button ref={closeButtonRef} type="button" onClick={onClose} className="modal-close-btn" aria-label="Cerrar ventana">
                            <i className="fas fa-times" aria-hidden="true" />
                        </button>
                    </div>
                    {children}
                </div>
            </div>
        </div>,
        document.body
    );
}
