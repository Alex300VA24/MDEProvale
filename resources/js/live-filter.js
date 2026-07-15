/**
 * Convierte un formulario de filtro <form method="GET"> en una búsqueda
 * dinámica sin recargar la página, SIN tocar el backend: pide la misma
 * ruta con los mismos parámetros vía fetch(), y solo reemplaza el
 * contenedor de resultados (tabla + paginación) parseando el HTML
 * devuelto. El controlador recibe una petición normal y devuelve la
 * misma vista Blade de siempre.
 *
 * Uso:
 *   initLiveFilter({
 *     formEl: document.getElementById('filtro-x'),
 *     resultsSelector: '#resultados-x',
 *     url: '{{ route("modulo.index") }}',
 *   });
 */

let activeRequests = 0;

function setFormBusy(formEl, busy) {
    activeRequests = Math.max(0, activeRequests + (busy ? 1 : -1));
    if (window.FilterLoading) {
        if (activeRequests > 0) {
            window.FilterLoading.fire();
        } else if (window.Swal) {
            window.Swal.close();
        }
    }
    formEl.querySelectorAll('input, select').forEach((el) => {
        el.disabled = busy;
    });
}

export function initLiveFilter({ formEl, resultsSelector, url, onAfterSwap }) {
    if (!formEl) return;

    let debounceTimer;
    let isBusy = false;
    const debounce = (fn, ms = 350) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fn, ms);
    };

    async function refresh(targetUrl) {
        if (isBusy) return;
        const container = document.querySelector(resultsSelector);
        if (!container) return;

        const requestUrl = targetUrl || `${url}?${new URLSearchParams(new FormData(formEl)).toString()}`;
        isBusy = true;
        setFormBusy(formEl, true);

        try {
            const res = await fetch(requestUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.querySelector(resultsSelector);

            if (fresh) {
                container.replaceWith(fresh);
                history.replaceState(null, '', requestUrl);
                attachPaginationLinks();
                reinitSelect2();
                if (typeof onAfterSwap === 'function') onAfterSwap();
            }
        } catch (e) {
            // red de seguridad: se libera el formulario igual en el finally
        } finally {
            isBusy = false;
            setFormBusy(formEl, false);
        }
    }

    function reinitSelect2() {
        if (typeof window.$ === 'undefined' || !window.$.fn.select2) return;
        window.$(formEl).find('.select2-filter').each(function () {
            if (window.$(this).data('select2')) window.$(this).select2('destroy');
            // Sin botón "×": estos selects ya traen su propia opción "Todos los..."
            // para limpiar el filtro, así que el botón de limpiar es redundante.
            window.$(this).select2({ width: '100%', allowClear: false });
        });
    }

    function attachPaginationLinks() {
        document.querySelectorAll(`${resultsSelector} .pagination a[href], ${resultsSelector} nav a[href]`).forEach((a) => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                refresh(a.href);
            });
        });
    }

    formEl.querySelectorAll('input[type="text"], input[type="search"]').forEach((el) => {
        // Más margen que un simple debounce de UI: le da tiempo a alguien
        // que escribe despacio a terminar la palabra antes de disparar el
        // filtro (y el modal "Aplicando filtro...").
        el.addEventListener('input', () => debounce(() => refresh(), 700));
    });
    formEl.querySelectorAll('select, input[type="date"]').forEach((el) => {
        el.addEventListener('change', () => refresh());
    });
    // Select2 actualiza el <select> y dispara "change" solo por dentro de jQuery
    // (no despacha un evento nativo), así que un addEventListener normal nunca
    // se entera. Se escucha también por delegación de jQuery para cubrir ese caso.
    if (window.$ && window.$.fn) {
        window.$(formEl).on('change', 'select', () => refresh());
    }
    formEl.addEventListener('submit', (e) => {
        e.preventDefault();
        refresh();
    });

    attachPaginationLinks();
    reinitSelect2();
}

window.initLiveFilter = initLiveFilter;
