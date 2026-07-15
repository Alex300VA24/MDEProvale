import Swal from 'sweetalert2';

/**
 * Modal ligero "Aplicando filtro..." que se muestra mientras un filtro
 * dinámico (live-filter.js) está en vuelo. No se puede cerrar manualmente:
 * se cierra solo cuando la respuesta llega, para no permitir aplicar otro
 * filtro hasta que el anterior termine.
 */
export const FilterLoading = Swal.mixin({
    html: `
        <div class="flex flex-col items-center gap-3 py-1">
            <div class="relative w-16 h-16 flex items-center justify-center">
                <span class="absolute inset-0 rounded-full border-4 border-mist"></span>
                <span class="absolute inset-0 rounded-full border-4 border-transparent border-t-sky animate-spin"></span>
                <i class="fas fa-filter text-blue text-lg"></i>
            </div>
            <div class="text-center">
                <div class="text-base font-extrabold text-charcoal">Aplicando filtro</div>
                <div class="text-xs text-earth mt-0.5">Actualizando resultados...</div>
            </div>
            <div class="filter-progress-track">
                <div class="filter-progress-bar"></div>
            </div>
        </div>`,
    showConfirmButton: false,
    showCloseButton: false,
    allowOutsideClick: false,
    allowEscapeKey: false,
    backdrop: 'rgba(15, 23, 42, 0.35)',
    customClass: {
        popup: 'rounded-2xl shadow-2xl border-2 border-wheat',
        container: 'backdrop-blur-[2px]',
    },
});

window.FilterLoading = FilterLoading;
