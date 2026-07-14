# MDEProvale

Aplicación Laravel para la gestión de socios, beneficiarios, productos, raciones y transacciones del sistema MDEProvale.

## Requisitos

Antes de instalar el proyecto asegúrate de tener instalado:

- PHP 7.4 o 8.0
- Composer
- MySQL o MariaDB
- Apache o Nginx
- Node.js y npm (para compilar assets)
- XAMPP, WAMP o un entorno similar si vas a trabajar en Windows

## 1. Clonar y preparar el proyecto

```bash
git clone <url-del-repositorio>
cd MDEProvale
composer install
cp .env.example .env
php artisan key:generate
```

## 2. Configurar la base de datos

Crea una base de datos en MySQL/MariaDB, por ejemplo:

```sql
CREATE DATABASE mdeprovale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Luego edita el archivo `.env` y configura estas variables:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mdeprovale
DB_USERNAME=root
DB_PASSWORD=
```

Si estás usando XAMPP en Windows, normalmente `DB_USERNAME=root` y `DB_PASSWORD=` funcionan si no has configurado una contraseña.

## 3. Ejecutar migraciones

Para crear las tablas del sistema:

```bash
php artisan migrate
```

## 4. Cargar los seeders

El proyecto incluye varios seeders para dejar el sistema funcional con datos base. Para ejecutarlos:

```bash
php artisan db:seed
```

Esto correrá los seeders registrados en `database/seeders/DatabaseSeeder.php`, como:

- estados, roles y módulos
- tipos de transacciones, unidades de medida y beneficios
- sectores, lugares, relaciones y posiciones
- usuarios, personas, asociaciones, socios y beneficiarios
- productos, resoluciones y raciones

> Nota: en este proyecto, `TransactionSeeder` y `PecosaSeeder` aparecen comentados en `DatabaseSeeder.php`, por lo que no se ejecutan por defecto. Si necesitas esos datos, descomenta esas líneas antes de correr `php artisan db:seed`.

## 5. Compilar los assets frontend

Si la interfaz no carga correctamente los estilos o scripts, ejecuta:

```bash
npm install
npm run dev
```

Para producción puedes usar:

```bash
npm run prod
```

## 6. Levantar el proyecto

Opción simple para desarrollo:

```bash
php artisan serve
```

Luego abre en el navegador:

```text
http://127.0.0.1:8000
```

Si vas a usar Apache con XAMPP, apunta el DocumentRoot al directorio `public/` del proyecto.

## 7. Limpiar caché (si es necesario)

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Problemas comunes

- Error de conexión a la base de datos: revisa `.env` y que MySQL/MariaDB esté corriendo.
- Error 500 o permisos: asegúrate de tener permisos de escritura en `storage/` y `bootstrap/cache/`.
- Si los seeders fallan: verifica que las migraciones se hayan ejecutado correctamente y que la base de datos exista.

## Resumen rápido

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run dev
php artisan serve
```
