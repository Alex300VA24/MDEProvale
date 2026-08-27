# MDEProvale

Aplicación **Laravel 10 + React/Inertia** para la gestión de socios, beneficiarios, comités, productos, pecosas, raciones y movimientos del Programa Vaso de Leche.

---

## 1. Requisitos

| Herramienta | Versión | Notas |
|-------------|---------|-------|
| PHP | **8.1.x** (`>=8.1 <8.2`) | Con PHP 8.2+ o 7.x, `composer install` falla. XAMPP 7.4 **no sirve**; necesitas un XAMPP/PHP 8.1. |
| Composer | 2.x | |
| MySQL / MariaDB | 5.7+ / 10.4+ | Incluido en XAMPP. |
| Node.js | **18 LTS o superior** | Con npm, para compilar el frontend con Vite. |
| Git | cualquiera | |

Extensiones PHP necesarias (vienen activas en XAMPP): `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`.

> El proyecto **no incluye seeders con datos reales** ni un dump. Migrar desde cero solo crea tablas vacías (sin roles, sin módulos de menú, sin estados, sin usuarios): no podrás iniciar sesión. Para datos reales necesitas un **dump SQL** que te comparta el equipo (ver sección 4.2).

---

## 2. Elegir cómo vas a levantar el proyecto

Hay dos formas. Los pasos 3, 4 y 5 son **comunes**; solo cambia la sección 6.

- **Opción A — `php artisan serve`** (recomendada para desarrollo). No usa Apache; Laravel levanta su propio servidor en `http://127.0.0.1:8000`.
- **Opción B — Apache de XAMPP** (proyecto en `htdocs`). Más parecido a producción; requiere configurar un VirtualHost apuntando a `public/`.

---

## 3. Clonar e instalar dependencias

### Opción A (artisan serve)

Clónalo donde quieras:

```bash
git clone <url-del-repositorio> MDEProvale
cd MDEProvale
composer install
npm install
```

### Opción B (XAMPP)

Clónalo **dentro de `htdocs`**:

```bash
cd C:\xampp\htdocs
git clone <url-del-repositorio> MDEProvale
cd MDEProvale
composer install
npm install
```

---

## 4. Configuración común

### 4.1 Archivo `.env` y clave de aplicación

```bash
cp .env.example .env
php artisan key:generate
```

Edita `.env` con los datos de tu base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mdeprovale
DB_USERNAME=root
DB_PASSWORD=
```

Con XAMPP por defecto: `DB_USERNAME=root` y `DB_PASSWORD=` (vacío).

> `APP_URL`, `SANCTUM_STATEFUL_DOMAINS` y `SESSION_DOMAIN` se ajustan **en la sección 6**, según la opción que elijas. El login usa sesión + cookies (Sanctum SPA); si esos valores no coinciden con la URL real verás errores 419/401 o sesiones que "expiran" solas.

### 4.2 Crear la base de datos e importar datos

En XAMPP Control Panel arranca **MySQL**. Luego crea la base:

```sql
CREATE DATABASE mdeprovale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

(También sirve desde phpMyAdmin → **Nueva** → nombre `mdeprovale` → cotejamiento `utf8mb4_unicode_ci`.)

Importa el dump que te compartan:

```bash
mysql -u root mdeprovale < dump.sql
```

O en phpMyAdmin: selecciona la base `mdeprovale` → pestaña **Importar** → elige el `.sql` → **Continuar**.

### 4.3 Ejecutar migraciones pendientes

Siempre, tanto si importaste dump como si no:

```bash
php artisan migrate
```

Laravel aplica solo las migraciones que falten (revisa la tabla `migrations`), sin duplicar nada.

> Si clonas **sin dump** y solo quieres explorar el código: `php artisan migrate` crea las tablas, pero tendrás que insertar a mano al menos un registro en `states`, `rols`, `modules` / `module_rol` y `users` para poder entrar.

### 4.4 Permisos de escritura

Asegúrate de que estas carpetas sean escribibles (en Windows normalmente ya lo son):

```
storage/
bootstrap/cache/
```

Si más adelante ves errores 500 de permisos:

```bash
php artisan storage:link
```

### 4.5 Compilar el frontend (Vite)

`public/build` **no está versionado**, así que un clon nuevo no trae assets compilados. Elige un modo:

- **Vas a tocar el frontend** (`.jsx` / `.css`) y quieres hot reload:

  ```bash
  npm run dev
  ```

  Deja esa terminal abierta mientras trabajas.

- **Solo backend / solo levantar el sistema** (compilar una vez):

  ```bash
  npm run build
  ```

  ⚠️ Cada `git pull` que traiga cambios en `resources/js` o `resources/css` obliga a **volver a `npm run build`** (si no tienes `npm run dev` corriendo). Si no, seguirás viendo la interfaz vieja.

---

## 5. Verificación rápida

```bash
php artisan about        # muestra versión de PHP, entorno, conexión BD
php artisan migrate:status
```

---

## 6. Levantar el proyecto

### Opción A — `php artisan serve`

1. En `.env` deja:

   ```env
   APP_URL=http://127.0.0.1:8000
   SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000
   SESSION_DOMAIN=localhost
   ```

2. Limpia config si cambiaste `.env`:

   ```bash
   php artisan config:clear
   ```

3. Arranca:

   ```bash
   php artisan serve
   ```

   Si vas a tocar frontend, en **otra terminal**:

   ```bash
   npm run dev
   ```

4. Abre `http://127.0.0.1:8000`.

Si necesitas otro puerto: `php artisan serve --port=8080` y ajusta `APP_URL` y `SANCTUM_STATEFUL_DOMAINS` a ese puerto.

---

### Opción B — Apache de XAMPP (VirtualHost)

El proyecto ya está en `C:\xampp\htdocs\MDEProvale`. Apache debe apuntar a la carpeta `public/`, no a la raíz del proyecto.

1. **Habilitar `mod_rewrite`** — en `C:\xampp\apache\conf\httpd.conf` verifica que esta línea **no** tenga `#`:

   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

2. **Definir el VirtualHost** — al final de `C:\xampp\apache\conf\extra\httpd-vhosts.conf` añade:

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

3. **Mapear el dominio** — en `C:\Windows\System32\drivers\etc\hosts` (editar como Administrador) añade:

   ```
   127.0.0.1    mdeprovale.test
   ```

4. **Ajustar `.env`**:

   ```env
   APP_URL=http://mdeprovale.test
   SANCTUM_STATEFUL_DOMAINS=mdeprovale.test
   SESSION_DOMAIN=mdeprovale.test
   ```

   ```bash
   php artisan config:clear
   ```

5. **Compilar assets para Apache** (Apache no usa el hot reload de Vite):

   ```bash
   npm run build
   ```

6. En **XAMPP Control Panel** arranca **Apache** y **MySQL** y abre `http://mdeprovale.test`.

> **Alternativa sin VirtualHost** (`http://localhost/MDEProvale/public`): funciona para ver la app, pero la ruta con subcarpeta complica las cookies de sesión de Sanctum y las URLs de assets. Se recomienda el VirtualHost.

---

## 7. Limpiar caché (si algo no se actualiza)

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

---

## 8. Pruebas automatizadas (opcional)

Las pruebas de Feature (`tests/Feature/Api`) usan **una base separada** (`phpunit.xml` → `dbsysprovale_test`) porque hacen `migrate:fresh` y borrarían tus datos.

```sql
CREATE DATABASE dbsysprovale_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan test
```

---

## 9. Problemas comunes

| Síntoma | Causa / solución |
|---------|------------------|
| Página en blanco / sin estilos / `Vite manifest not found` | Falta `npm install` + (`npm run dev` o `npm run build`). |
| Los cambios de un `git pull` no se ven | Falta `npm run build` (o no tienes `npm run dev` corriendo). |
| Menú lateral vacío / no puedo iniciar sesión | Base de datos sin datos: migraste sin importar el dump (sección 4.2). |
| Error de conexión a BD | Revisa `.env` y que MySQL esté arrancado en XAMPP. |
| Error 500 / permisos | Permisos de escritura en `storage/` y `bootstrap/cache/`. |
| Sesión expira sola / error 419 al guardar | `APP_URL`, `SANCTUM_STATEFUL_DOMAINS` y `SESSION_DOMAIN` no coinciden con la URL real (sección 6). |
| Apache: 404 en todas las rutas menos `/` | Falta `mod_rewrite` o `AllowOverride All` en el VirtualHost. |
| Apache: "Forbidden" | `DocumentRoot` mal, o falta `Require all granted`. |
| `composer install` falla por versión de PHP | Necesitas PHP **8.1.x**; XAMPP 7.4 no sirve. |
| `php artisan test` falla por tablas/columnas | Crea la base `dbsysprovale_test` (sección 8); no reutilices la de desarrollo. |

---

## 10. Resumen rápido

```bash
# común
git clone <url> MDEProvale && cd MDEProvale
composer install
npm install
cp .env.example .env
php artisan key:generate
# crear BD mdeprovale + importar dump.sql (sección 4.2)
php artisan migrate
npm run build            # o `npm run dev` si vas a tocar frontend

# Opción A
php artisan serve        # http://127.0.0.1:8000

# Opción B (XAMPP): configurar VirtualHost a /public + hosts + .env (sección 6),
# luego arrancar Apache y MySQL     # http://mdeprovale.test
```
