/**
 * Extrae un mensaje de error legible de una respuesta de axios: un error de
 * validación (422, con `errors`), un mensaje de negocio (`message`, p. ej.
 * 403/404/422 sin `errors`), o un fallo de red/timeout donde nunca llegó a
 * haber respuesta del servidor (antes cada pantalla caía siempre al mismo
 * `fallback` genérico en ese caso, sin explicar qué pasó realmente).
 */
export default function errorMessage(err, fallback) {
    if (!err?.response) {
        if (err?.code === 'ECONNABORTED') {
            return 'La solicitud tardó demasiado en responder. Verifica tu conexión e inténtalo nuevamente.';
        }
        return 'No se pudo conectar con el servidor. Verifica tu conexión a internet e inténtalo nuevamente.';
    }

    const data = err.response.data;
    if (data?.errors) {
        const first = Object.values(data.errors)[0];
        const firstMessage = Array.isArray(first) ? first[0] : first;
        if (firstMessage) return firstMessage;
    }
    return data?.message || fallback;
}
