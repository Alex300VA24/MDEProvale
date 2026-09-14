# MDEProvale — Sistema de Gestión PROVALE

Aplicación web para el **Programa Vaso de Leche** (MDE): gestión de socios y
beneficiarios, clubes/reconocimientos, productos y pecosas, movimientos,
raciones, reportes y portal de presidentas.

**Stack:** Laravel 10.50 (PHP 8.1) · MySQL/MariaDB · Blade + Alpine.js y
Tailwind para los módulos principales · React + Inertia para el dashboard ·
Vite (SPA) y Laravel Mix (vistas Blade) · dompdf · QR (endroid).

El proyecto se puede levantar de **dos formas**:

| Modalidad | URL de acceso | Uso típico |
|-----------|---------------|------------|
| **A. `php artisan serve`** | `http://127.0.0.1:8000` | Desarrollo rápido, sin Apache |
| **B. XAMPP (carpeta `htdocs`)** | `http://localhost/<CARPETA>/public` | Entorno tipo producción |

Ambos modos funcionan con **las mismas rutas**, sin cambiar código: los enlaces,
recursos (CSS/JS/fuentes/Vite) y peticiones AJAX se generan de forma relativa al
request, así que sirven tanto en la raíz (`artisan serve`) como dentro de una
subcarpeta de `htdocs`.

---

## 1. Requisitos

| Herramienta | Versión | Notas |
|-------------|---------|-------|
| PHP | **8.1.x** (`>=8.1 <8.2`) | `composer.json` fija el rango; PHP 7.x u 8.2+ **no** funcionan |
| Composer | 2.x | Necesario para `composer install` |
| MySQL / MariaDB | 5.7+ / 10.4+ | Incluido en XAMPP |
| Node.js | **20 LTS o superior** | Con npm, para compilar el frontend (Vite 8) |
| Apache (solo para el modo B) | 2.4+ | Incluido en XAMPP; requiere `mod_rewrite` y `AllowOverride All` |
| Git | opcional | Solo si clonas el repositorio |

**Extensiones PHP obligatorias** (activas por defecto en un XAMPP con PHP 8.1):
`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`,
`bcmath`, `fileinfo`, `gd`, `curl`, `zip`.

> ⚠️ Con **XAMPP 7.4** no se puede instalar (PHP 7.4). Usa un XAMPP con **PHP 8.1.x**.

---

## 2. Cómo funcionan las rutas (importante)

Laravel genera las URLs a partir del **request actual**, no de rutas absolutas
“quemadas”:

- `route('...')`, `url(...)` y `asset(...)` en plantillas Blade → relativos al
  request (funcionan en raíz y en subcarpeta).
- El frontend SPA (React/Inertia) usa `window.APP_URL`, que la vista `app.blade.php`
  inyecta automáticamente (`<script>window.APP_URL = ...</script>`). Con eso los
  `fetch('/api/...')`, enlaces de PDF/comprobantes y el logout se resuelven
  contra la base real del sitio.
- Los assets de Vite (`public/build`) se sirven con el mismo prefijo en tiempo
  de ejecución, así que **un solo `npm run build` sirve para ambos modos**.

`APP_URL` del `.env` solo se usa **fuera del navegador** (cola, consola, tests).
Aun así, conviene dejarla apuntando a la URL con la que entras al sistema.

---

## 3. Instalación (pasos comunes a ambos modos)

### 3.1 Copiar el proyecto

**Opción A (`artisan serve`) — donde quieras:**

```bash
git clone <url-del-repositorio> MDEProvale
cd MDEProvale
```

**Opción B (XAMPP) — dentro de `htdocs`:**

Copia la carpeta completa del proyecto a `C:\xampp\htdocs\MDEProvale` (o el
nombre que quieras; la URL usará ese nombre).

> ⚠️ Si usas XAMPP, asegúrate de que el proyecto quede en `htdocs` y que sea **el
> único XAMPP que Apache sirve** (si tienes dos instalaciones con copias, cada
> instalación sirve su propia copia). También podes copiar el proyecto a un
> subdirectorio de uno de los dos `htdocs` y acceder por URL con subcarpeta.

### 3.2 Instalar dependencias

```bash
composer install
npm ci
```

`composer.lock` y `package-lock.json` están versionados; por eso se recomienda
usar `npm ci` para instalar exactamente las versiones probadas. Usa `npm install`
solo si necesitas actualizar el lockfile.

### 3.3 Archivo `.env` y clave de la aplicación

```bash
copy .env.example .env        # Windows
# o: cp .env.example .env
php artisan key:generate
```

Edita `.env` con los datos de tu base:

```env
APP_NAME="Sistema PROVALE"
APP_ENV=local
APP_DEBUG=true

# Modo A: http://localhost:8000
# Modo B: http://localhost/MDEProvale/public   (tu nombre de carpeta)
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=DBSYSPROVALE
DB_USERNAME=root
DB_PASSWORD=
```

En **XAMPP** el usuario suele ser `root` y la clave vacía (`DB_PASSWORD=`). Si tu
MySQL tiene clave, complétala.

### 3.4 Crear la base de datos

En XAMPP Control Panel arranca **MySQL** y crea la base (también desde phpMyAdmin):

```sql
CREATE DATABASE DBSYSPROVALE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Con `php artisan` no hay comando para crearla; usa la consulta SQL o phpMyAdmin.

### 3.5 Migraciones y datos iniciales

El repositorio incluye los seeders y los JSON necesarios para una instalación
completa. Después de crear `DBSYSPROVALE`, ejecuta:

```bash
php artisan migrate:fresh --seed
```

Esto crea las tablas y carga catálogos, usuarios, socios, beneficiarios,
productos, PECOSAs y demás datos iniciales. Para una actualización que conserve
los datos existentes, usa solo:

```bash
php artisan migrate
```

Los archivos `.sql` no forman parte del repositorio. Si recibes un dump de otro
entorno, impórtalo desde phpMyAdmin o con el cliente MySQL antes de ejecutar las
migraciones pendientes.

> ⚠️ **Nunca dejes config en caché.** Si existe `bootstrap/cache/config.php`, los
> tests y algunos comandos pueden quedarse apuntando a la BD de desarrollo y
> ejecutar `migrate:fresh` ahí (¡borra tus datos!). Antes de trabajar, sobre todo
> antes de correr tests: `php artisan config:clear`. Y no ejecutes `config:cache`
> en desarrollo.

### 3.6 Compilar el frontend (assets)

El proyecto genera **dos builds** distintos:

| Build | Comando | A dónde va |
|-------|---------|-----------|
| SPA (React/Vite) | `npm run build` | `public/build/` (dashboard) |
| Vistas Blade (Alpine/jQuery) | `npm run build:blade` | `public/css`, `public/js`, fuentes, webfonts |

Esas salidas se generan en cada equipo y están excluidas por `.gitignore`; no
deben agregarse con `git add -f`. El código fuente vive en `resources/` y las
versiones de dependencias quedan fijadas en `package-lock.json`.

Para producción/levantarlo, compila ambos de una vez:

```bash
npm run build:assets
```

Para desarrollo con recarga en caliente del frontend:

```bash
npm run dev        # Vite (SPA)          — dejar la terminal abierta
npm run dev:blade  # Mix watch (Blade)   — en otra terminal, opcional
```

> ⚠️ Cada `git pull` (o actualización) que toque `resources/js` o `resources/css`
> obliga a **volver a compilar** (`npm run build:assets`), o verás la interfaz vieja.

### 3.7 Verificación rápida

```bash
php artisan about           # versión de PHP, entorno, conexión a BD
php artisan migrate:status  # estado de las migraciones
```

---

## 4. Opción A — Levantar con `php artisan serve`

No usa Apache; Laravel levanta su propio servidor:

```bash
php artisan serve
```

Abre **http://127.0.0.1:8000**. Para otro puerto/host:

```bash
php artisan serve --host=127.0.0.1 --port=8080
```

En `.env` deja las variables del servidor de desarrollo (puerto = 8000):

```env
APP_URL=http://localhost:8000
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:*,127.0.0.1,127.0.0.1:*,::1,[::1]:*
SESSION_DOMAIN=
SESSION_COOKIE=provale_session
```

Si cambias cualquier cosa del `.env`, limpia config:

```bash
php artisan config:clear
```

---

## 5. Opción B — Levantar con XAMPP (carpeta `htdocs`)

### 5.1 Sin VirtualHost (subcarpeta — funciona igual)

Con el proyecto en `C:\xampp\htdocs\MDEProvale`:

1. En XAMPP Control Panel arranca **Apache** y **MySQL**.
2. Edita `.env`:

   ```env
   APP_URL=http://localhost/MDEProvale/public
   SANCTUM_STATEFUL_DOMAINS=localhost,localhost:*,127.0.0.1,127.0.0.1:*,::1,[::1]:*
   SESSION_DOMAIN=
   SESSION_COOKIE=provale_session
   ```

   Luego: `php artisan config:clear`.
3. Entra a:

   ```
   http://localhost/MDEProvale/public
   ```

Todo funciona en subcarpeta: rutas, assets (Mix y Vite), AJAX del SPA, PDFs y
comprobantes se generan con el prefijo correcto. Si quieres otra URL más corta,
cambia el nombre de la carpeta (y el `APP_URL`).

> Nota: nunca apuntes Apache al directorio del proyecto, sino a la subcarpeta
> `public/`, y revisa que ese directorio tenga `AllowOverride All` (por defecto
> en XAMPP para `htdocs`).

### 5.2 (Opcional) VirtualHost — URL limpia en la raíz

Si prefieres `http://mdeprovale.test` en lugar de la subcarpeta:

1. **`mod_rewrite`** activo (en `conf/httpd.conf` de tu XAMPP):
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
2. Al final de `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:
   ```apache
   <VirtualHost *:80>
       ServerName mdeprovale.test
       DocumentRoot "C:/xampp/htdocs/MDEProvale/public"
       <Directory "C:/xampp/htdocs/MDEProvale/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. En `C:\Windows\System32\drivers\etc\hosts` (como Administrador):
   ```
   127.0.0.1    mdeprovale.test
   ```
4. `.env`:
   ```env
   APP_URL=http://mdeprovale.test
   SANCTUM_STATEFUL_DOMAINS=mdeprovale.test
   SESSION_DOMAIN=mdeprovale.test
   ```
   `php artisan config:clear` y reinicia Apache. Listo: `http://mdeprovale.test`.

---

## 6. Credenciales iniciales y Portal de Presidentas

### 6.1 Usuarios que crea el `DatabaseSeeder`

Las credenciales de desarrollo no se publican en el repositorio. Solicítalas al
responsable del entorno o crea un usuario local mediante un seeder privado. No
reutilices cuentas ni contraseñas de prueba en producción.

### 6.2 Portal de Presidentas (Socia Presidenta)

Los usuarios con rol **Socia Presidenta** entran por un portal separado:

- **Login:** `/portal-presidentas/login` — usuario = **DNI**, contraseña = **DNI**.
- **Primer ingreso:** el sistema fuerza el cambio de contraseña en
  `/portal-presidentas/cambiar-contrasena` (los usuarios creados tienen
  `must_change_password = 1`).
- **Administración:** al **asignar una presidenta** en Comités/Reconocimientos,
  el sistema crea (o sincroniza) su cuenta de portal automáticamente con su DNI.
- **Restablecer contraseña:** en el panel de Administración
  (Sistema → Usuarios → *Restablecer contraseña*), se vuelve a poner como DNI y
  se fuerza el cambio. Comando equivalente:

  ```bash
  php artisan tinker --execute="\$u = App\Models\User::find(1); \$u->update(['password' => Illuminate\Support\Facades\Hash::make(\$u->dni), 'must_change_password' => true]);"
  ```

---

## 7. Pruebas automatizadas (phpunit)

Los tests usan una **base separada** (`dbsysprovale_test`, configurada en
`phpunit.xml`), porque `RefreshDatabase` ejecuta `migrate:fresh` y **borraría
tus datos de desarrollo**.

1. Crea la base de test una sola vez:
   ```sql
   CREATE DATABASE dbsysprovale_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. **Clave** (¡importante!):
   ```bash
   php artisan config:clear
   ```
   Stale config cache (`bootstrap/cache/config.php`) congela la conexión a la BD
   de desarrollo y los tests la migran en blanco. Sin `config:clear` antes de
   correr tests, **puedes perder la BD dev**.
3. Ejecuta:
   ```bash
   php vendor/bin/phpunit
   # o: php artisan test
   ```
   Para un grupo específico:
   ```bash
   php vendor/bin/phpunit tests/Unit/PresidentAccountServiceTest.php tests/Feature/Auth/PresidentPortalAuthenticationTest.php
   ```

> El suite completo puede mostrar fallos **preexistentes** en módulos no
> relacionados (auth por email de Breeze, productos/pecosas, `partner positions`).
> Los tests de la feature Portal de Presidentas y los `Api` principales corren en
> verde.

---

## 8. Comandos útiles

```bash
# Limpiar todas las cachés (tras cambios en .env, rutas, vistas)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Precompilar vistas (opcional, más rápido en producción)
php artisan view:cache

# Recargar seeders y volver a poblar (¡borra la BD!)
php artisan migrate:fresh --seed

# Crear enlace public/storage (solo si se usan archivos subidos públicos)
php artisan storage:link

# Frontend
npm run dev            # Vite con hot reload (SPA)
npm run dev:blade      # Mix watch (vistas Blade)
npm run build:assets   # Build definitivo: Vite + Mix
```

---

## 9. Problemas comunes

| Síntoma | Causa / solución |
|---------|------------------|
| Página en blanco / sin estilos / `Vite manifest not found` | Falta `npm ci` + `npm run build:assets` (o `npm run dev`). |
| Se ve la interfaz vieja tras actualizar | Falta `npm run build:assets` (los `resources/js`/`css` cambiaron). |
| Error de conexión a BD | Revisa `.env` (host, puerto, base, usuario, clave) y que MySQL esté arrancado. |
| Sesión expira sola / error 419 al guardar | `APP_URL`/`SESSION_DOMAIN` no coinciden con la URL real (sección 4/5). |
| 404 en Apache salvo `/` | Falta `mod_rewrite` o `AllowOverride All` en el `Directory` de `public/`. |
| 404 en los assets/`public/build` | Entras por el directorio del proyecto en vez de por `public/`, o la copia que Apache sirve no es la que editaste (dos XAMPP con copias distintas). |
| Apache: “Forbidden” | `DocumentRoot` mal apuntado o falta `Require all granted`. |
| `composer install` falla por versión de PHP | Necesitas PHP **8.1.x** (XAMPP 7.4 es PHP 7.4 y no sirve). |
| `php artisan test` borró mis datos | Existía `bootstrap/cache/config.php`: corre siempre `php artisan config:clear` antes de testear (sección 7). |
| Error MySQL `caching_sha2_password` | El cliente antiguo (`mysql.exe`) no soporta ese plugin; usa phpMyAdmin o `php artisan tinker`. |
| Puerto 8000 ocupado | `php artisan serve --port=8080` y ajusta `APP_URL` (fallo común). |

---

## 10. Resumen rápido

```bash
# 1. Clonar y entrar al proyecto
git clone <url-del-repositorio> MDEProvale
cd MDEProvale

# 2. Dependencias
composer install
npm ci

# 3. Configuración
copy .env.example .env
php artisan key:generate
# editar .env: APP_URL (sección 3.3), DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 4. Base de datos (crear en MySQL/phpMyAdmin)
# CREATE DATABASE DBSYSPROVALE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 5. Datos
php artisan migrate:fresh --seed
php artisan config:clear

# 6. Assets
npm run build:assets

# 7a. Opción A
php artisan serve                 # http://127.0.0.1:8000

# 7b. Opción B (XAMPP)
#   proyecto en C:\xampp\htdocs\MDEProvale
#   start Apache + MySQL en el Control Panel
#   abrir http://localhost/MDEProvale/public
```

**Portal de presidentas:** `/portal-presidentas/login` (DNI / DNI, con cambio de
contraseña obligatorio). Las credenciales administrativas se entregan fuera del
repositorio.

---

## 11. Higiene del repositorio

No se versionan dependencias, archivos `.env`, claves, cachés, logs, cobertura,
artefactos compilados ni salidas temporales de herramientas. Los seeders
invocados por `DatabaseSeeder` y sus JSON de `database/seeders/data/` sí se
versionan porque forman parte de la instalación inicial. Tampoco se suben
prototipos HTML de la raíz que ya tengan su vista Blade equivalente.

Los insumos locales de migración pueden contener DNI, nombres u otros datos
personales. Por eso se excluyen las carpetas de migración, los CSV de
`migracion_responsables/` y `public/fichas/`. Los scripts reutilizables de
`migracion_responsables/` sí se conservan, pero sus datos de entrada no.

Antes de confirmar cambios:

```bash
git status --short
git diff --check
git check-ignore -v RUTA_DEL_ARCHIVO
```

Si un archivo sensible ya fue confirmado alguna vez, agregarlo a `.gitignore`
no lo elimina del historial. Hay que retirarlo del índice, rotar cualquier
secreto expuesto y, cuando corresponda, limpiar el historial con coordinación
del equipo.
