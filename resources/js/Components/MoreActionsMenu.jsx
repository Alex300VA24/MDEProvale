import { useEffect, useRef, useState } from 'react';

export default function MoreActionsMenu({ items, label = 'Más Acciones' }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const onClickOutside = (e) => {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        };
        const onEscape = (e) => {
            if (e.key === 'Escape') setOpen(false);
        };
        document.addEventListener('mousedown', onClickOutside);
        document.addEventListener('keydown', onEscape);
        return () => {
            document.removeEventListener('mousedown', onClickOutside);
            document.removeEventListener('keydown', onEscape);
        };
    }, [open]);

    const visibleItems = (items || []).filter(Boolean);
    if (visibleItems.length === 0) return null;

    if (visibleItems.length === 1) {
        const item = visibleItems[0];
        const cls = 'btn-secondary flex items-center gap-2 text-xs sm:text-sm';
        return item.href ? (
            <a href={item.href} target={item.target} rel={item.target ? 'noreferrer' : undefined} className={cls}>
                <i className={`fas ${item.icon}`} /> {item.label}
            </a>
        ) : (
            <button type="button" onClick={item.onClick} className={cls}>
                <i className={`fas ${item.icon}`} /> {item.label}
            </button>
        );
    }

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="btn-secondary flex items-center gap-2 text-xs sm:text-sm"
                aria-haspopup="true"
                aria-expanded={open}
            >
                {label}
                <i
                    className={`fas ${open ? 'fa-chevron-up' : 'fa-chevron-down'} text-[10px]`}
                    aria-hidden="true"
                />
            </button>
            {open && (
                <div className="absolute left-0 sm:right-0 sm:left-auto top-full mt-2 w-56 bg-white border-2 border-wheat rounded-xl shadow-lg py-1.5 z-30">
                    {visibleItems.map((item, idx) =>
                        item.href ? (
                            <a
                                key={idx}
                                href={item.href}
                                target={item.target}
                                rel={item.target ? 'noreferrer' : undefined}
                                onClick={() => setOpen(false)}
                                className="flex items-center gap-3 px-4 py-2.5 text-xs sm:text-sm font-semibold text-charcoal hover:bg-cream transition-colors"
                            >
                                <i className={`fas ${item.icon} text-leaf w-4`} /> {item.label}
                            </a>
                        ) : (
                            <button
                                key={idx}
                                type="button"
                                onClick={() => {
                                    setOpen(false);
                                    item.onClick?.();
                                }}
                                className="w-full flex items-center gap-3 px-4 py-2.5 text-xs sm:text-sm font-semibold text-charcoal hover:bg-cream transition-colors text-left"
                            >
                                <i className={`fas ${item.icon} text-leaf w-4`} /> {item.label}
                            </button>
                        )
                    )}
                </div>
            )}
        </div>
    );
}
