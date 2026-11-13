import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import PdfLinkButton from './PdfLinkButton';

const itemCls = 'w-full flex items-center gap-3 px-4 py-2.5 text-xs sm:text-sm font-semibold text-left transition-colors disabled:opacity-60';
const MENU_WIDTH = 224;

/**
 * Menú desplegable (kebab) para las acciones de una fila de tabla.
 * items: { label, icon, onClick?, pdf?: { href, loadingTitle }, danger? } (los falsy se omiten).
 * Se pinta en un portal con posición fija para que no lo recorte el scroll de la tabla.
 */
export default function RowActionsMenu({ items, label = 'Acciones', triggerText = null, triggerClassName = 'btn-action bg-fog text-charcoal hover:bg-wheat' }) {
    const [open, setOpen] = useState(false);
    const [position, setPosition] = useState(null);
    const buttonRef = useRef(null);
    const menuRef = useRef(null);

    const visibleItems = (items || []).filter(Boolean);

    useLayoutEffect(() => {
        if (!open || !buttonRef.current) return;
        const rect = buttonRef.current.getBoundingClientRect();
        const menuHeight = menuRef.current?.offsetHeight ?? visibleItems.length * 40 + 12;
        const openUp = rect.bottom + menuHeight + 8 > window.innerHeight && rect.top > menuHeight + 8;
        setPosition({
            top: openUp ? rect.top - menuHeight - 4 : rect.bottom + 4,
            left: Math.max(8, Math.min(rect.right - MENU_WIDTH, window.innerWidth - MENU_WIDTH - 8)),
        });
    }, [open, visibleItems.length]);

    useEffect(() => {
        if (!open) return;
        const close = () => setOpen(false);
        const onClickOutside = (event) => {
            if (buttonRef.current?.contains(event.target) || menuRef.current?.contains(event.target)) return;
            close();
        };
        const onEscape = (event) => event.key === 'Escape' && close();
        document.addEventListener('mousedown', onClickOutside);
        document.addEventListener('keydown', onEscape);
        window.addEventListener('scroll', close, true);
        window.addEventListener('resize', close);
        return () => {
            document.removeEventListener('mousedown', onClickOutside);
            document.removeEventListener('keydown', onEscape);
            window.removeEventListener('scroll', close, true);
            window.removeEventListener('resize', close);
        };
    }, [open]);

    if (visibleItems.length === 0) return null;

    const colorCls = (item) => (item.danger ? 'text-clay hover:bg-clay-light' : 'text-charcoal hover:bg-cream');
    const iconCls = (item) => (item.danger ? 'text-clay' : 'text-leaf');

    return (
        <>
            <button
                ref={buttonRef}
                type="button"
                onClick={() => setOpen((value) => !value)}
                className={triggerText ? `${triggerClassName} flex items-center gap-2` : triggerClassName}
                aria-haspopup="menu"
                aria-expanded={open}
                aria-label={label}
                title={label}
            >
                <i className="fas fa-ellipsis-vertical" aria-hidden="true" />
                {triggerText}
            </button>
            {open && createPortal(
                <div
                    ref={menuRef}
                    role="menu"
                    style={{ position: 'fixed', top: position?.top ?? -9999, left: position?.left ?? -9999, width: MENU_WIDTH }}
                    className="z-[60] rounded-xl border-2 border-wheat bg-white py-1.5 shadow-lg"
                >
                    {visibleItems.map((item) => item.pdf ? (
                        <PdfLinkButton
                            key={item.label}
                            href={item.pdf.href}
                            loadingTitle={item.pdf.loadingTitle}
                            icon={`${item.icon} w-4 ${iconCls(item)}`}
                            className={`${itemCls} ${colorCls(item)}`}
                            role="menuitem"
                        >
                            {item.label}
                        </PdfLinkButton>
                    ) : (
                        <button
                            key={item.label}
                            type="button"
                            role="menuitem"
                            onClick={() => {
                                setOpen(false);
                                item.onClick?.();
                            }}
                            className={`${itemCls} ${colorCls(item)}`}
                        >
                            <i className={`fas ${item.icon} w-4 ${iconCls(item)}`} aria-hidden="true" /> {item.label}
                        </button>
                    ))}
                </div>,
                document.body
            )}
        </>
    );
}
