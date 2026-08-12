import { useEffect, useMemo, useRef, useState } from 'react';

export default function Combobox({
    value,
    onChange,
    onSelect = null,
    options = [],
    onSearch = null,
    placeholder = 'Seleccionar...',
    allowClear = false,
    disabled = false,
    selectedLabel = null,
    minQuery = 2,
    maxResults = 60,
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const [highlight, setHighlight] = useState(-1);
    const rootRef = useRef(null);
    const timerRef = useRef(null);
    const searchRef = useRef(onSearch);

    useEffect(() => {
        searchRef.current = onSearch;
    }, [onSearch]);

    useEffect(() => {
        const handler = (e) => {
            if (rootRef.current && !rootRef.current.contains(e.target)) setOpen(false);
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    useEffect(() => {
        if (searchRef.current) {
            const q = query.trim();
            if (q.length < minQuery) {
                setResults([]);
                setLoading(false);
                return;
            }
            setLoading(true);
            clearTimeout(timerRef.current);
            timerRef.current = setTimeout(async () => {
                try {
                    const data = await searchRef.current(q);
                    setResults(Array.isArray(data) ? data : []);
                } catch {
                    setResults([]);
                } finally {
                    setLoading(false);
                }
            }, 300);
            return () => clearTimeout(timerRef.current);
        }

        const q = query.trim().toLowerCase();
        setResults(q ? options.filter((o) => o.label.toLowerCase().includes(q)) : options);
        setLoading(false);
    }, [query, options, minQuery]);

    const selected = useMemo(() => {
        if (value === null || value === undefined || value === '') return null;
        return options.find((o) => String(o.id) === String(value)) || null;
    }, [value, options]);

    const display = selectedLabel || selected?.label || '';
    const inputValue = open && query !== '' ? query : display;

    const handleSelect = (opt) => {
        onChange(opt.id);
        if (onSelect) onSelect(opt);
        setQuery('');
        setOpen(false);
        setHighlight(-1);
    };

    const handleClear = (e) => {
        e.stopPropagation();
        onChange(null);
        setQuery('');
    };

    const handleKeyDown = (e) => {
        if (disabled) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!open) {
                setOpen(true);
            } else {
                setHighlight((h) => Math.min(h + 1, results.length - 1));
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlight((h) => Math.max(h - 1, 0));
        } else if (e.key === 'Enter') {
            if (open && highlight >= 0 && results[highlight]) {
                e.preventDefault();
                handleSelect(results[highlight]);
            } else {
                setOpen(true);
            }
        } else if (e.key === 'Escape') {
            setOpen(false);
        }
    };

    const visible = results.slice(0, maxResults);
    const truncated = results.length > maxResults;

    return (
        <div className="relative" ref={rootRef}>
            <div className="relative">
                <input
                    type="text"
                    className="w-full px-4 py-2.5 pr-9 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                    value={inputValue}
                    placeholder={placeholder}
                    disabled={disabled}
                    onFocus={() => setOpen(true)}
                    onChange={(e) => {
                        setQuery(e.target.value);
                        setOpen(true);
                        setHighlight(-1);
                    }}
                    onKeyDown={handleKeyDown}
                />
                {allowClear && !disabled && value !== null && value !== undefined && value !== '' && (
                    <button
                        type="button"
                        onClick={handleClear}
                        className="absolute right-8 top-1/2 -translate-y-1/2 text-earth hover:text-clay text-sm"
                        tabIndex={-1}
                    >
                        <i className="fas fa-times-circle" />
                    </button>
                )}
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-earth pointer-events-none text-xs">
                    <i className={`fas fa-chevron-down ${open ? 'rotate-180' : ''}`} />
                </span>
            </div>
            {open && (
                <div className="absolute z-30 mt-1 w-full bg-white border-2 border-wheat rounded-xl shadow-lg max-h-56 overflow-y-auto">
                    {loading ? (
                        <div className="px-4 py-3 text-sm text-earth flex items-center gap-2">
                            <i className="fas fa-spinner fa-spin" /> Buscando...
                        </div>
                    ) : visible.length === 0 ? (
                        <div className="px-4 py-3 text-sm text-earth">
                            {searchRef.current && query.trim().length < minQuery
                                ? `Escribe al menos ${minQuery} caracteres`
                                : 'Sin resultados'}
                        </div>
                    ) : (
                        visible.map((o, i) => (
                            <button
                                type="button"
                                key={o.id}
                                onClick={() => handleSelect(o)}
                                onMouseEnter={() => setHighlight(i)}
                                className={`block w-full text-left px-4 py-2 text-sm truncate ${i === highlight ? 'bg-sky-light' : 'hover:bg-sky-light'}`}
                            >
                                {o.label}
                            </button>
                        ))
                    )}
                    {truncated && !loading && (
                        <div className="px-4 py-2 text-xs text-earth border-t border-wheat">
                            Escribe para filtrar ({results.length} opciones)
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
