// Estado del tema (claro / oscuro) compartido por toda la SPA.
//
// El tema por defecto es claro: el modo oscuro solo se activa si el usuario lo
// eligió antes (persistido en localStorage). El script anti-parpadeo de los
// blade (`app.blade.php`, `layouts/main.blade.php`) ya añade la clase `.dark`
// al <html> antes del primer pintado; aquí solo se gestiona el cambio en vivo.

export const THEME_STORAGE_KEY = 'mde-theme';
const THEME_EVENT = 'mde:themechange';

export function isDark() {
    if (typeof document === 'undefined') return false;
    return document.documentElement.classList.contains('dark');
}

export function applyTheme(dark) {
    if (typeof document === 'undefined') return;
    document.documentElement.classList.toggle('dark', dark);
    try {
        localStorage.setItem(THEME_STORAGE_KEY, dark ? 'dark' : 'light');
    } catch (e) {
        /* almacenamiento no disponible: el cambio sigue aplicando en esta sesión */
    }
    window.dispatchEvent(new CustomEvent(THEME_EVENT, { detail: { dark } }));
}

export function toggleTheme() {
    applyTheme(!isDark());
}

// Suscribe un callback a los cambios de tema. Devuelve la función para cancelar.
export function onThemeChange(callback) {
    if (typeof window === 'undefined') return () => {};
    const handler = (event) => callback(event.detail?.dark ?? isDark());
    window.addEventListener(THEME_EVENT, handler);
    return () => window.removeEventListener(THEME_EVENT, handler);
}
