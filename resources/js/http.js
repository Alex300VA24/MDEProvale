import axios from 'axios';
import { reloadForCsrfExpired, showSessionExpired } from './httpAlerts';

/**
 * Cliente axios para las llamadas JSON del dashboard (Sanctum SPA).
 * - withCredentials: envía la cookie de sesión en cada request.
 * - Envía XSRF-TOKEN / X-CSRF-TOKEN automáticamente para los verbos mutantes.
 * - Si la sesión expiró (401), redirige al login.
 * - timeout: evita que un request se quede "colgado" indefinidamente
 *   (p. ej. combos de búsqueda) si la conexión o el servidor no responden.
 */
const http = axios.create({
    withCredentials: true,
    timeout: 15000,
    // baseURL: rutas absolutas como /api/... se resuelven bajo la base real de la
    // aplicacion (window.APP_URL) para que funcione tanto con `php artisan serve`
    // como instalado en una subcarpeta de htdocs (p. ej. http://localhost/MDEProvale/public).
    baseURL: window.APP_URL || '',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

http.interceptors.request.use((config) => {
    if (!config.headers['X-XSRF-TOKEN']) {
        const xsrfCookie = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='));
        if (xsrfCookie) {
            config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie.split('=')[1]);
        }
    }
    // La cookie XSRF-TOKEN siempre es la vigente (se renueva cuando la sesión se
    // restaura con "mantener sesión activa"); el meta de la página puede estar viejo.
    if (!config.headers['X-CSRF-TOKEN'] && !config.headers['X-XSRF-TOKEN']) {
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            config.headers['X-CSRF-TOKEN'] = metaToken.getAttribute('content');
        }
    }
    return config;
});

http.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const data = error.response?.data;

        if (data?.wrong_portal && data?.redirect) {
            window.location.assign(data.redirect);
        } else if (error.config?.silentSession && [401, 419].includes(status)) {
            // Petición de fondo (polling): no interrumpe al usuario con alertas.
        } else if (status === 419 && data?.csrf_expired) {
            reloadForCsrfExpired();
        } else if ([401, 419].includes(status)) {
            showSessionExpired(data?.redirect);
        }
        return Promise.reject(error);
    }
);

export default http;
