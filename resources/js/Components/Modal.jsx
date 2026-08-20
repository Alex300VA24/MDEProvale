import { createPortal } from 'react-dom';

export default function Modal({ open, onClose, title, icon, iconClass = 'text-leaf', maxWidth = 'sm:max-w-2xl', children }) {
    if (!open) return null;

    return createPortal(
        <div className="modal-overlay is-open animate-fade-in" onClick={onClose}>
            <div className={`modal-panel ${maxWidth}`} onClick={(e) => e.stopPropagation()}>
                <div className="modal-card modal-enter">
                    <div className="modal-header">
                        <h3 className="font-extrabold text-charcoal text-lg flex items-center gap-2">
                            {icon && <i className={`fas ${icon} ${iconClass}`} />}
                            {title}
                        </h3>
                        <button type="button" onClick={onClose} className="modal-close-btn">
                            <i className="fas fa-times" />
                        </button>
                    </div>
                    {children}
                </div>
            </div>
        </div>,
        document.body
    );
}
