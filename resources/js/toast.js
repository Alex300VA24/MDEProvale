import Swal from 'sweetalert2';

/**
 * Notificación flotante no bloqueante (éxito/error/advertencia/info),
 * se cierra sola. Reemplaza el banner de página + Swal.fire duplicado
 * que existía antes para los mensajes flash.
 */
export const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (t) => {
        t.addEventListener('mouseenter', Swal.stopTimer);
        t.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

window.Toast = Toast;
