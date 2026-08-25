import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

function normalize(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
}

export default function IconSelect({
    id,
    value,
    onChange,
    options = [],
    disabled = false,
    labelledBy,
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [coords, setCoords] = useState(null);
    const rootRef = useRef(null);
    const triggerRef = useRef(null);
    const searchRef = useRef(null);
    const popoverRef = useRef(null);
    const optionRefs = useRef([]);

    const selected = useMemo(
        () => options.find((option) => option.class_name === value) || null,
        [options, value]
    );

    const filtered = useMemo(() => {
        const term = normalize(query.trim());
        if (!term) return options;

        return options.filter((option) => normalize(
            `${option.name} ${option.category} ${option.class_name}`
        ).includes(term));
    }, [options, query]);

    useEffect(() => {
        const handleOutsideClick = (event) => {
            const insideTrigger = rootRef.current?.contains(event.target);
            const insidePopover = popoverRef.current?.contains(event.target);
            if (!insideTrigger && !insidePopover) setOpen(false);
        };

        document.addEventListener('mousedown', handleOutsideClick);
        return () => document.removeEventListener('mousedown', handleOutsideClick);
    }, []);

    useEffect(() => {
        if (!open) return undefined;

        const updatePosition = () => {
            const rect = triggerRef.current?.getBoundingClientRect();
            if (!rect) return;

            const margin = 8;
            const preferredHeight = 360;
            const availableBelow = window.innerHeight - rect.bottom - margin;
            const availableAbove = rect.top - margin;
            const openUp = availableBelow < 240 && availableAbove > availableBelow;
            const availableHeight = openUp ? availableAbove : availableBelow;
            const width = Math.min(Math.max(rect.width, 320), window.innerWidth - (margin * 2));

            setCoords({
                left: Math.max(margin, Math.min(rect.left, window.innerWidth - width - margin)),
                width,
                top: openUp ? undefined : rect.bottom + 4,
                bottom: openUp ? window.innerHeight - rect.top + 4 : undefined,
                maxHeight: Math.max(200, Math.min(preferredHeight, availableHeight)),
            });
        };

        updatePosition();
        requestAnimationFrame(() => searchRef.current?.focus());
        window.addEventListener('resize', updatePosition);
        window.addEventListener('scroll', updatePosition, true);

        return () => {
            window.removeEventListener('resize', updatePosition);
            window.removeEventListener('scroll', updatePosition, true);
        };
    }, [open]);

    const close = () => {
        setOpen(false);
        setQuery('');
        requestAnimationFrame(() => triggerRef.current?.focus());
    };

    const select = (className) => {
        onChange(className);
        close();
    };

    const focusOption = (index) => {
        const safeIndex = Math.max(0, Math.min(index, filtered.length - 1));
        optionRefs.current[safeIndex]?.focus();
    };

    return (
        <div ref={rootRef} className="relative">
            <button
                ref={triggerRef}
                id={id}
                type="button"
                disabled={disabled}
                onClick={() => setOpen((current) => !current)}
                onKeyDown={(event) => {
                    if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        setOpen(true);
                    }
                }}
                className="w-full min-h-[44px] px-3.5 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-3 text-left"
                aria-labelledby={labelledBy}
                aria-haspopup="listbox"
                aria-controls={`${id}-listbox`}
                aria-expanded={open}
            >
                <span className="w-8 h-8 rounded-lg bg-sky-light text-blue flex items-center justify-center flex-shrink-0" aria-hidden="true">
                    <i className={`fas ${selected?.class_name || value || 'fa-icons'}`} />
                </span>
                <span className="min-w-0 flex-1">
                    <span className={`block truncate ${selected || value ? 'text-charcoal' : 'text-earth'}`}>
                        {selected?.name || (value ? value : 'Seleccionar un ícono')}
                    </span>
                    {selected && <span className="block text-[11px] font-medium text-earth truncate">{selected.category}</span>}
                </span>
                <i className={`fas fa-chevron-down text-earth text-xs transition-transform ${open ? 'rotate-180' : ''}`} aria-hidden="true" />
            </button>

            {open && coords && createPortal(
                <div
                    ref={popoverRef}
                    className="fixed z-[1000] bg-white border-2 border-wheat rounded-xl shadow-xl overflow-hidden"
                    style={{
                        left: coords.left,
                        width: coords.width,
                        top: coords.top,
                        bottom: coords.bottom,
                        maxHeight: coords.maxHeight,
                    }}
                >
                    <div className="p-3 border-b border-wheat bg-base">
                        <label htmlFor={`${id}-search`} className="sr-only">Buscar ícono</label>
                        <div className="relative">
                            <i className="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-earth text-xs" aria-hidden="true" />
                            <input
                                ref={searchRef}
                                id={`${id}-search`}
                                type="search"
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Escape') close();
                                    if (event.key === 'ArrowDown' && filtered.length) {
                                        event.preventDefault();
                                        focusOption(0);
                                    }
                                }}
                                className="w-full min-h-[42px] pl-9 pr-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf"
                                placeholder="Buscar por nombre o categoría..."
                            />
                        </div>
                    </div>

                    <div
                        id={`${id}-listbox`}
                        role="listbox"
                        aria-label="Iconos disponibles"
                        className="overflow-y-auto p-2"
                        style={{ maxHeight: Math.max(72, coords.maxHeight - (value ? 124 : 68)) }}
                    >
                        {filtered.length === 0 ? (
                            <div className="px-3 py-8 text-sm text-earth text-center">
                                <i className="fas fa-magnifying-glass mb-2 block text-xl" aria-hidden="true" />
                                No se encontraron iconos.
                            </div>
                        ) : filtered.map((option, index) => (
                            <button
                                ref={(element) => { optionRefs.current[index] = element; }}
                                key={option.id}
                                type="button"
                                role="option"
                                aria-selected={option.class_name === value}
                                onClick={() => select(option.class_name)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Escape') close();
                                    if (event.key === 'ArrowDown') {
                                        event.preventDefault();
                                        focusOption(index + 1);
                                    }
                                    if (event.key === 'ArrowUp') {
                                        event.preventDefault();
                                        if (index === 0) searchRef.current?.focus();
                                        else focusOption(index - 1);
                                    }
                                    if (event.key === 'Home') {
                                        event.preventDefault();
                                        focusOption(0);
                                    }
                                    if (event.key === 'End') {
                                        event.preventDefault();
                                        focusOption(filtered.length - 1);
                                    }
                                }}
                                className={`w-full min-h-[44px] px-3 py-2 rounded-lg flex items-center gap-3 text-left transition-colors focus:outline-none focus:ring-2 focus:ring-leaf/30 ${
                                    option.class_name === value ? 'bg-sky-light text-blue' : 'text-charcoal hover:bg-mist'
                                }`}
                            >
                                <span className="w-8 h-8 rounded-lg bg-white border border-wheat flex items-center justify-center flex-shrink-0" aria-hidden="true">
                                    <i className={`fas ${option.class_name}`} />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block text-sm font-bold truncate">{option.name}</span>
                                    <span className="block text-[11px] font-medium text-earth truncate">{option.category}</span>
                                </span>
                                {option.class_name === value && <i className="fas fa-check text-leaf" aria-hidden="true" />}
                            </button>
                        ))}
                    </div>

                    {value && (
                        <div className="p-2 border-t border-wheat bg-base">
                            <button
                                type="button"
                                onClick={() => select('')}
                                className="w-full min-h-[40px] px-3 rounded-lg text-sm font-bold text-earth hover:bg-mist hover:text-charcoal transition-colors"
                            >
                                Quitar selección
                            </button>
                        </div>
                    )}
                </div>,
                document.body
            )}
        </div>
    );
}
