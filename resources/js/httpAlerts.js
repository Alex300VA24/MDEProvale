import Swal from 'sweetalert2';

let activeSessionAlert = null;
let activeHttpAlert = null;

const appUrl = () => window.APP_URL || '';

const loginUrlForCurrentPage = () => {
    const portal = window.location.pathname.includes('/portal-presidentas');
    const path = portal ? '/portal-presidentas/login' : '/login';

    return `${appUrl()}${path}?expired=1`;
};

const safeRedirect = (redirect) => {
    if (typeof redirect !== 'string' || !redirect.trim()) {
        return loginUrlForCurrentPage();
    }

    try {
        const target = new URL(redirect, window.location.origin);
        return target.origin === window.location.origin
            ? target.href
            : loginUrlForCurrentPage();
    } catch {
        return loginUrlForCurrentPage();
    }
};

export function showSessionExpired(redirect) {
    if (activeSessionAlert) {
        return activeSessionAlert;
    }

    const destination = safeRedirect(redirect);

    activeSessionAlert = Swal.fire({
        icon: 'warning',
        title: 'Sesión finalizada',
        text: 'Tu sesión venció por inactividad. Inicia sesión nuevamente para continuar.',
        confirmButtonText: 'Iniciar sesión',
        confirmButtonColor: '#0B3A66',
        allowOutsideClick: false,
        allowEscapeKey: false,
        returnFocus: false,
        customClass: {
            popup: 'provale-alert',
            confirmButton: 'provale-alert-button',
        },
    }).then(() => {
        window.location.assign(destination);
    });

    return activeSessionAlert;
}

const HTTP_ALERTS = {
    403: {
        icon: 'warning',
        title: 'Acceso restringido',
        text: 'Tu cuenta no tiene permiso para realizar esta acción. Consulta con un administrador si necesitas acceso.',
        confirmButtonText: 'Entendido',
    },
    404: {
        icon: 'info',
        title: 'Contenido no encontrado',
        text: 'El contenido pudo cambiar de ubicación o dejar de estar disponible. Regresa y consulta otra opción.',
        confirmButtonText: 'Regresar',
        action: 'back',
    },
    429: {
        icon: 'warning',
        title: 'Demasiadas solicitudes',
        text: 'Espera un momento antes de intentarlo nuevamente.',
        confirmButtonText: 'Entendido',
    },
    500: {
        icon: 'error',
        title: 'No pudimos completar la acción',
        text: 'Ocurrió un problema interno. Puedes reintentar; si continúa, consulta con soporte.',
        confirmButtonText: 'Reintentar',
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        action: 'reload',
    },
    503: {
        icon: 'info',
        title: 'Servicio temporalmente no disponible',
        text: 'El sistema está en mantenimiento o saturado. Espera un momento y vuelve a intentar.',
        confirmButtonText: 'Reintentar',
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        action: 'reload',
    },
};

function showHttpAlert(status) {
    if (activeHttpAlert || !HTTP_ALERTS[status]) {
        return activeHttpAlert;
    }

    const alert = HTTP_ALERTS[status];
    const { action, ...options } = alert;
    activeHttpAlert = Swal.fire({
        ...options,
        confirmButtonColor: '#0B3A66',
        cancelButtonColor: '#64748B',
        customClass: {
            popup: 'provale-alert',
            confirmButton: 'provale-alert-button',
            cancelButton: 'provale-alert-button',
        },
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        if (action === 'reload') {
            window.location.reload();
        } else if (action === 'back') {
            window.history.back();
        }
    }).finally(() => {
        activeHttpAlert = null;
    });

    return activeHttpAlert;
}

export function registerInertiaErrorAlerts(router) {
    router.on('httpException', (event) => {
        const { status, data } = event.detail.response;

        if ([401, 419].includes(status)) {
            event.preventDefault();
            showSessionExpired(data?.redirect);
            return;
        }

        if (HTTP_ALERTS[status]) {
            event.preventDefault();
            showHttpAlert(status);
        }
    });

    router.on('networkError', (event) => {
        event.preventDefault();

        if (activeHttpAlert) {
            return;
        }

        activeHttpAlert = Swal.fire({
            icon: 'warning',
            title: 'Sin conexión con el sistema',
            text: 'Revisa tu conexión a internet y vuelve a intentarlo.',
            confirmButtonText: 'Reintentar',
            showCancelButton: true,
            cancelButtonText: 'Cerrar',
            confirmButtonColor: '#0B3A66',
            cancelButtonColor: '#64748B',
            customClass: {
                popup: 'provale-alert',
                confirmButton: 'provale-alert-button',
                cancelButton: 'provale-alert-button',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        }).finally(() => {
            activeHttpAlert = null;
        });
    });
}
