import '../css/app.css';
// Sistema tipográfico institucional: Source Sans 3 para cuerpo/datos (alta
// legibilidad, usado en gobierno/salud/finanzas) y Lexend para títulos.
import '@fontsource/source-sans-3/400.css';
import '@fontsource/source-sans-3/500.css';
import '@fontsource/source-sans-3/600.css';
import '@fontsource/source-sans-3/700.css';
import '@fontsource/lexend/400.css';
import '@fontsource/lexend/500.css';
import '@fontsource/lexend/600.css';
import '@fontsource/lexend/700.css';
import '@fortawesome/fontawesome-free/css/all.min.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ToastProvider } from './Components/Toast';

const appName = import.meta.env.VITE_APP_NAME || 'MDEProvale';

createInertiaApp({
    title: (title) => `${title ? `${title} — ` : ''}${appName}`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        createRoot(el).render(
            <ToastProvider>
                <App {...props} />
            </ToastProvider>
        );
    },
});
