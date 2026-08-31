import { useEffect, useState } from 'react';
import { isDark, toggleTheme, onThemeChange } from '../theme';

// Botón para alternar entre tema claro y oscuro. Refleja el estado real del
// <html> y se mantiene sincronizado si el tema cambia desde otro control.
export default function ThemeToggle({ className = '', variant = 'shell' }) {
    const [dark, setDark] = useState(isDark);

    useEffect(() => onThemeChange(setDark), []);

    const base =
        variant === 'portal'
            ? 'portal-theme-toggle'
            : 'w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-base border-2 border-mist flex items-center justify-center text-slate dark:text-white hover:bg-mist transition-all';

    return (
        <button
            type="button"
            onClick={toggleTheme}
            aria-label={dark ? 'Activar tema claro' : 'Activar tema oscuro'}
            aria-pressed={dark}
            title="Cambiar entre tema claro y oscuro"
            className={`${base} ${className}`.trim()}
        >
            {dark ? (
                <svg className="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                </svg>
            ) : (
                <svg className="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                </svg>
            )}
        </button>
    );
}
