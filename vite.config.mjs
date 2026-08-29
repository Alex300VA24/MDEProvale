import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    // Rutas relativas permiten cargar chunks y fuentes tanto desde la raiz de
    // `artisan serve` como desde `/MDEProvale/public` bajo XAMPP.
    base: './',
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
    ],
});
