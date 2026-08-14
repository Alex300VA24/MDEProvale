# MDEProvale

Aplicación Laravel 10 + React/Inertia para la gestión de socios, beneficiarios, comités, productos, pecosas, raciones y movimientos del Programa Vaso de Leche.

## Requisitos

- **PHP 8.1** (el proyecto está fijado a `>=8.1 <8.2` en `composer.json`; con PHP 8.2+ o 7.x no instalará bien las dependencias)
- Composer
- MySQL o MariaDB
- Node.js 18+ y npm (para compilar el frontend con Vite)
- Apache/Nginx, o simplemente `php artisan serve` para desarrollo
- XAMPP, WAMP o similar si trabajas en Windows

## 1. Clonar y preparar el proyecto

```bash
git clone <url-del-repositorio>
cd MDEProvale
composer install
cp .env.example .env
php artisan key:generate
```

## 2. Base de datos

### 2.1 Crear la base de datos

```sql
CREATE DATABASE mdeprovale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Edita `.env` con tus datos de conexión:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mdeprovale
DB_USERNAME=root
DB_PASSWORD=
```

Con XAMPP en Windows, `DB_USERNAME=root` y `DB_PASSWORD=` (vacío) suele funcionar si no configuraste contraseña.

### 2.2 Importar los datos (no hay seeders)

> **Importante:** este repositorio **no tiene `database/seeders/`**. Correr `php artisan migrate` desde cero solo crea las tablas *vacías*: sin roles, sin módulos del menú, sin estados y sin ningún usuario con el que iniciar sesión. El sistema no es utilizable así.

Para tener datos reales, pide un **dump SQL** (export de la base de datos) a quien te lo comparta e impórtalo:

```bash
mysql -u root -p mdeprovale < dump.sql
```

O desde phpMyAdmin: crea la base `mdeprovale` y usa **Importar** → selecciona el archivo `.sql`.

### 2.3 Ejecutar migraciones pendientes

El dump puede no incluir las migraciones más recientes. Después de importarlo, corre siempre:

```bash
php artisan migrate
```

Laravel revisa la tabla `migrations` (que viene incluida en el dump) y solo aplica las que falten, sin duplicar ni romper nada. Si en algún momento clonas el proyecto **sin** un dump disponible y solo quieres levantar el sistema para explorar el código (sin datos reales), puedes correr `php artisan migrate` a secas, pero recuerda que tendrás que crear manualmente al menos un registro en `states`, `rols`, `modules`/`module_rol` y `users` para poder iniciar sesión.

## 3. Frontend (Vite + React)

```bash
npm install
```

`public/build` **no está versionado en git**, así que un clon nuevo no tiene ningún asset compilado. Elige uno de estos dos modos:

- **Mientras desarrollas el frontend** (hot reload al guardar cambios en `.jsx`/`.css`):

  ```bash
  npm run dev
  ```

  Deja esta terminal abierta; Vite sirve los assets en caliente mientras `php artisan serve` corre en otra terminal.

- **Si solo vas a levantar el sistema o solo tocas backend** (no necesitas hot reload, pero sí compilar al menos una vez):

  ```bash
  npm run build
  ```

  ⚠️ **Cada vez que hagas `git pull` y haya cambios en `resources/js` o `resources/css`, vuelve a correr `npm run build`** (si no estás usando `npm run dev`). Laravel sirve los archivos ya compilados de `public/build`; si no los regeneras, seguirás viendo la versión vieja de la interfaz aunque el código ya haya cambiado.

## 4. Levantar el proyecto

```bash
php artisan serve
```

Abre en el navegador:

```text
http://127.0.0.1:8000
```

Si vas a usar Apache con XAMPP, apunta el DocumentRoot al directorio `public/` del proyecto.

### Nota sobre sesión/CSRF (Sanctum)

El login usa sesión + cookies (Sanctum SPA), no tokens. Si accedes desde una URL distinta a `localhost:8000` / `127.0.0.1:8000` (por ejemplo otro puerto), actualiza en `.env`:

```env
APP_URL=http://localhost:TU_PUERTO
SANCTUM_STATEFUL_DOMAINS=localhost:TU_PUERTO,127.0.0.1:TU_PUERTO
SESSION_DOMAIN=localhost
```

Si no coinciden, verás sesiones que "expiran" solas o errores 419/401 al guardar formularios.

## 5. Limpiar caché (si algo no actualiza)

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## 6. Correr las pruebas automatizadas (opcional)

El proyecto trae pruebas de Feature que cubren crear/editar/eliminar en cada módulo (`tests/Feature/Api`). Usan **una base de datos separada** (ver `phpunit.xml`, actualmente `dbsysprovale_test`) porque las pruebas hacen `migrate:fresh` y borrarían tus datos si apuntaran a la base de desarrollo.

```sql
CREATE DATABASE dbsysprovale_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan test
```

## Problemas comunes

- **Página en blanco / sin estilos / error de "Vite manifest not found"**: te faltó correr `npm install` + (`npm run dev` o `npm run build`).
- **Los cambios de un `git pull` no se ven en el navegador**: te faltó `npm run build` (o no tienes `npm run dev` corriendo).
- **No hay nada en el menú lateral / no puedo iniciar sesión**: la base de datos está vacía porque migraste sin importar el dump (ver sección 2.2).
- **Error de conexión a la base de datos**: revisa `.env` y que MySQL/MariaDB esté corriendo.
- **Error 500 o de permisos**: asegúrate de tener permisos de escritura en `storage/` y `bootstrap/cache/`.
- **Sesión expira sola / error 419 al guardar**: revisa `SANCTUM_STATEFUL_DOMAINS` y `SESSION_DOMAIN` en `.env` (ver sección 4).
- **`php artisan test` falla con errores de tabla/columna**: crea la base `dbsysprovale_test` (sección 6); no reutilices la base de desarrollo.

## Resumen rápido

```bash
composer install
cp .env.example .env
php artisan key:generate
# crear BD + importar dump.sql (ver sección 2.2)
php artisan migrate
npm install
npm run build   # o `npm run dev` si vas a tocar frontend
php artisan serve
```
