# Verificación de padrones por código QR

## Flujo

1. El controlador solicita a `VerifiedDocumentService` emitir un padrón.
2. El servicio crea un token aleatorio de 256 bits y forma la URL pública `/verificar-documento/{token}`.
3. La URL se convierte en QR y se entrega a la plantilla Blade del PDF.
4. El PDF terminado se guarda una sola vez en el disco privado `local`.
5. Se registra su ruta, metadatos y huella SHA-256 en `verified_documents`.
6. La consulta pública muestra autenticidad y sirve exactamente el PDF almacenado. No regenera el padrón.

## URLs

| Método | URL | Nombre | Protección |
| --- | --- | --- | --- |
| GET | `/verificar-documento/{token}` | `documents.verify` | Pública, token de 64 caracteres hexadecimales, límite 30/min |
| GET | `/verificar-documento/{token}/pdf` | `documents.pdf` | Pública, mismo token, visor por defecto y descarga con `?descargar=1` |
| GET | `/portal-presidentas/login` | `president.login` | Acceso independiente para presidentas |
| POST | `/portal-presidentas/login` | `president.login.store` | Credenciales, estado activo, rol activo `Socia Presidenta` y límite 5 intentos |
| POST | `/portal-presidentas/logout` | `president.logout` | Cierre de sesión del portal |
| GET | `/portal-presidentas` | `president-portal.index` | `role.president`; invitadas vuelven al login de presidentas |

El dominio y protocolo impresos en el QR salen de `APP_URL`. En producción debe ser la URL HTTPS pública definitiva.

## Posición del QR

- Padrón de Pecosas: bloque de 48 px (aprox. 12,7 mm) en la esquina superior derecha del encabezado. Está dentro del flujo del encabezado y no invade la tabla.
- Comprobante individual de Pecosa: QR de 17 × 17 mm dentro de una celda propia del encabezado, con el texto debajo.
- Padrón de Repartición: QR compacto de 17 × 17 mm al final del contenido. No usa posición fija; si no queda espacio, el bloque completo fluye a la página siguiente sin cubrir tablas.

## Portal de presidentas

El acceso exige el rol activo `Socia Presidenta`. La asignación del comité se resuelve por el DNI de la usuaria, su registro de socia y una directiva vigente con cargo de presidenta. Solo se consultan Pecosas del comité y dentro de las fechas de esa gestión.

## Puesta en producción

```bash
php artisan migrate --force
php artisan db:seed --class=RolSeeder --force
php artisan config:cache
php artisan view:cache
```

Asignar el rol `Socia Presidenta` a la cuenta correcta y confirmar que su DNI coincide con la persona registrada como presidenta vigente. El directorio `storage/app/documentos-verificados` debe persistir entre despliegues y no debe publicarse directamente.

## Revocación

Para invalidar un documento se establece `status = revoked` y `revoked_at`. La página pública conserva la trazabilidad, marca el documento como revocado y mantiene su archivo histórico privado.
