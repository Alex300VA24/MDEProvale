import Swal from 'sweetalert2';

/**
 * Modal de confirmación para acciones destructivas (eliminar, cerrar sesión,
 * resetear contraseña). Antes cada función en main.blade.php repetía el mismo
 * `customClass`/colores; ahora es un solo mixin y cada call-site solo pasa
 * lo que cambia (título, texto, ícono, texto del botón).
 */
export const ConfirmDialog = Swal.mixin({
    customClass: {
        popup: 'rounded-2xl shadow-2xl border-2 border-mist',
        confirmButton: 'rounded-xl font-bold px-4 py-2',
        cancelButton: 'rounded-xl font-bold px-4 py-2',
    },
    backdrop: 'rgba(0,0,0,0.4)',
    showCancelButton: true,
    reverseButtons: true,
    confirmButtonColor: '#D94F3D',
    cancelButtonColor: '#5A7FA8',
    cancelButtonText: 'Cancelar',
});

window.ConfirmDialog = ConfirmDialog;
