<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateModuleIconsTable extends Migration
{
    public function up()
    {
        Schema::create('module_icons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('class_name', 50)->unique();
            $table->string('category', 50);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('category');
        });

        $icons = [
            ['Inicio', 'fa-home', 'Navegación'],
            ['Panel de control', 'fa-gauge', 'Navegación'],
            ['Usuarios', 'fa-users', 'Personas'],
            ['Usuario', 'fa-user', 'Personas'],
            ['Grupo de usuarios', 'fa-user-friends', 'Personas'],
            ['Persona', 'fa-person', 'Personas'],
            ['Mujer', 'fa-female', 'Personas'],
            ['Bebé', 'fa-baby', 'Personas'],
            ['Niño', 'fa-child', 'Personas'],
            ['Niños', 'fa-children', 'Personas'],
            ['Comunidad', 'fa-people-group', 'Personas'],
            ['Responsable', 'fa-user-tie', 'Personas'],
            ['Usuario protegido', 'fa-user-shield', 'Personas'],
            ['Usuario verificado', 'fa-user-check', 'Personas'],
            ['Tarjeta de contacto', 'fa-address-card', 'Personas'],
            ['Documento de identidad', 'fa-id-card', 'Personas'],
            ['Producto', 'fa-box', 'Inventario'],
            ['Caja abierta', 'fa-box-open', 'Inventario'],
            ['Cajas apiladas', 'fa-boxes-stacked', 'Inventario'],
            ['Almacén', 'fa-warehouse', 'Inventario'],
            ['Pallet', 'fa-pallet', 'Inventario'],
            ['Carretilla', 'fa-dolly', 'Inventario'],
            ['Camión', 'fa-truck', 'Distribución'],
            ['Entrega rápida', 'fa-truck-fast', 'Distribución'],
            ['Carga de camión', 'fa-truck-ramp-box', 'Distribución'],
            ['Balanza', 'fa-scale-balanced', 'Inventario'],
            ['Control de peso', 'fa-weight-scale', 'Inventario'],
            ['Cubos', 'fa-cubes', 'Inventario'],
            ['Transporte de carga', 'fa-cart-flatbed', 'Distribución'],
            ['Movimientos', 'fa-exchange-alt', 'Distribución'],
            ['Hojuelas o cereal', 'fa-wheat-awn', 'Alimentos'],
            ['Botella de agua', 'fa-bottle-water', 'Alimentos'],
            ['Vaso de agua', 'fa-glass-water', 'Alimentos'],
            ['Leche', 'fa-cow', 'Alimentos'],
            ['Ración de alimentos', 'fa-bowl-food', 'Alimentos'],
            ['Alimentación', 'fa-utensils', 'Alimentos'],
            ['Preparación de alimentos', 'fa-kitchen-set', 'Alimentos'],
            ['Fruta', 'fa-apple-whole', 'Alimentos'],
            ['Verdura', 'fa-carrot', 'Alimentos'],
            ['Pan', 'fa-bread-slice', 'Alimentos'],
            ['Lácteos', 'fa-cheese', 'Alimentos'],
            ['Huevo', 'fa-egg', 'Alimentos'],
            ['Archivo', 'fa-file', 'Documentos'],
            ['Documento', 'fa-file-alt', 'Documentos'],
            ['Documento aprobado', 'fa-file-circle-check', 'Documentos'],
            ['Documento firmado', 'fa-file-signature', 'Documentos'],
            ['Contrato', 'fa-file-contract', 'Documentos'],
            ['Comprobante', 'fa-file-invoice', 'Documentos'],
            ['Carpeta', 'fa-folder', 'Documentos'],
            ['Carpeta abierta', 'fa-folder-open', 'Documentos'],
            ['Portapapeles', 'fa-clipboard', 'Documentos'],
            ['Lista aprobada', 'fa-clipboard-check', 'Documentos'],
            ['Lista de registros', 'fa-clipboard-list', 'Documentos'],
            ['Libro', 'fa-book', 'Documentos'],
            ['Libro abierto', 'fa-book-open', 'Documentos'],
            ['Recibo', 'fa-receipt', 'Documentos'],
            ['Resolución', 'fa-scroll', 'Documentos'],
            ['Certificado', 'fa-certificate', 'Reconocimientos'],
            ['Reconocimiento', 'fa-award', 'Reconocimientos'],
            ['Medalla', 'fa-medal', 'Reconocimientos'],
            ['Configuración general', 'fa-cogs', 'Sistema'],
            ['Configuración', 'fa-gear', 'Sistema'],
            ['Ajustes', 'fa-sliders', 'Sistema'],
            ['Herramientas', 'fa-toolbox', 'Sistema'],
            ['Mantenimiento', 'fa-screwdriver-wrench', 'Sistema'],
            ['Clave', 'fa-key', 'Seguridad'],
            ['Bloqueado', 'fa-lock', 'Seguridad'],
            ['Desbloqueado', 'fa-unlock', 'Seguridad'],
            ['Protección', 'fa-shield-halved', 'Seguridad'],
            ['Administrar usuario', 'fa-user-gear', 'Sistema'],
            ['Administrar usuarios', 'fa-users-gear', 'Sistema'],
            ['Base de datos', 'fa-database', 'Sistema'],
            ['Servidor', 'fa-server', 'Sistema'],
            ['Computadora portátil', 'fa-laptop', 'Sistema'],
            ['Computadora', 'fa-desktop', 'Sistema'],
            ['Código', 'fa-code', 'Sistema'],
            ['Módulo', 'fa-puzzle-piece', 'Sistema'],
            ['Calendario', 'fa-calendar-days', 'Programación'],
            ['Fecha confirmada', 'fa-calendar-check', 'Programación'],
            ['Horario', 'fa-clock', 'Programación'],
            ['Notificaciones', 'fa-bell', 'Comunicación'],
            ['Correo', 'fa-envelope', 'Comunicación'],
            ['Teléfono', 'fa-phone', 'Comunicación'],
            ['Mensajes', 'fa-comments', 'Comunicación'],
            ['Anuncio', 'fa-bullhorn', 'Comunicación'],
            ['Información', 'fa-circle-info', 'Comunicación'],
            ['Ayuda', 'fa-circle-question', 'Comunicación'],
            ['Ubicación', 'fa-location-dot', 'Ubicaciones'],
            ['Mapa', 'fa-map', 'Ubicaciones'],
            ['Ubicación en mapa', 'fa-map-location-dot', 'Ubicaciones'],
            ['Edificio', 'fa-building', 'Ubicaciones'],
            ['Municipalidad', 'fa-landmark', 'Ubicaciones'],
            ['Ciudad', 'fa-city', 'Ubicaciones'],
            ['Local comunal', 'fa-school', 'Ubicaciones'],
            ['Local', 'fa-store', 'Ubicaciones'],
            ['Camino', 'fa-road', 'Ubicaciones'],
            ['Ruta', 'fa-route', 'Ubicaciones'],
            ['Reporte de barras', 'fa-chart-bar', 'Reportes'],
            ['Reporte de tendencias', 'fa-chart-line', 'Reportes'],
            ['Reporte circular', 'fa-chart-pie', 'Reportes'],
        ];

        $now = now();
        DB::table('module_icons')->insert(array_map(function ($icon, $index) use ($now) {
            return [
                'name' => $icon[0],
                'class_name' => $icon[1],
                'category' => $icon[2],
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $icons, array_keys($icons)));
    }

    public function down()
    {
        Schema::dropIfExists('module_icons');
    }
}
