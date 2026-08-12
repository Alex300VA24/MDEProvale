import { createContext, useCallback, useContext, useMemo, useRef, useState } from 'react';

const ToastContext = createContext(null);

const STYLES = {
    success: { icon: 'fa-circle-check', iconColor: 'text-leaf', border: 'border-leaf' },
    error: { icon: 'fa-circle-exclamation', iconColor: 'text-clay', border: 'border-clay' },
    info: { icon: 'fa-circle-info', iconColor: 'text-sky', border: 'border-sky' },
};

export function ToastProvider({ children }) {
    const [toasts, setToasts] = useState([]);
    const idRef = useRef(0);

    const dismiss = useCallback((id) => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
    }, []);

    const push = useCallback((type, message) => {
        const id = ++idRef.current;
        setToasts((prev) => [...prev, { id, type, message }]);
        window.setTimeout(() => dismiss(id), 4500);
    }, [dismiss]);

    const toast = useMemo(
        () => ({
            success: (message) => push('success', message),
            error: (message) => push('error', message),
            info: (message) => push('info', message),
        }),
        [push]
    );

    return (
        <ToastContext.Provider value={toast}>
            {children}
            <div className="fixed top-4 right-4 z-[120] flex flex-col gap-2 w-[min(24rem,90vw)] pointer-events-none">
                {toasts.map((t) => {
                    const s = STYLES[t.type] || STYLES.info;
                    return (
                        <div
                            key={t.id}
                            className={`pointer-events-auto flex items-start gap-3 rounded-xl border-2 ${s.border} bg-white px-4 py-3 shadow-lg`}
                        >
                            <i className={`fas ${s.icon} ${s.iconColor} mt-0.5 text-lg`} />
                            <p className="text-sm font-semibold flex-1 text-charcoal">{t.message}</p>
                            <button type="button" onClick={() => dismiss(t.id)} className="text-earth hover:text-charcoal">
                                <i className="fas fa-xmark" />
                            </button>
                        </div>
                    );
                })}
            </div>
        </ToastContext.Provider>
    );
}

export function useToast() {
    return useContext(ToastContext);
}
