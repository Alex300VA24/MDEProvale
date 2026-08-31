const defaultTheme = require('tailwindcss/defaultTheme');

/**
 * Token de color respaldado por CSS custom properties.
 *
 * Cada color de la paleta institucional se resuelve en tiempo de ejecución
 * contra una variable `--c-*` (definida en resources/css/app.css). En `:root`
 * esas variables valen exactamente los mismos hex que antes, así que el modo
 * claro se renderiza idéntico. En `.dark` se sobreescriben, y todas las
 * utilidades (`bg-navy`, `text-charcoal`, `border-mist`, ...) cambian de tema
 * sin tocar el marcado. El placeholder `<alpha-value>` deja que Tailwind siga
 * generando las variantes con opacidad (`text-navy/70`, `bg-blue/10`, ...).
 */
function tok(cssVar) {
    return ({ opacityValue } = {}) =>
        opacityValue === undefined
            ? `rgb(var(${cssVar}))`
            : `rgb(var(${cssVar}) / ${opacityValue})`;
}

module.exports = {
    // Modo oscuro opt-in: se activa con la clase `.dark` en <html>. Nunca se
    // activa solo (sin preferencia de sistema); lo controla el interruptor y
    // se persiste en localStorage('mde-theme').
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/js/**/*.jsx',
    ],
    theme: {
        extend: {
            fontFamily: {
                // Cuerpo, formularios y tablas: Source Sans 3 (legibilidad institucional).
                sans: ['"Source Sans 3"', ...defaultTheme.fontFamily.sans],
                // Títulos, encabezados de sección y de modal: Lexend.
                heading: ['"Lexend"', '"Source Sans 3"', 'sans-serif'],
                jakarta: ['"Source Sans 3"', 'sans-serif'],
            },
            colors: {
                base:  tok('--c-base'),
                mist:  tok('--c-mist'),
                slate: tok('--c-slate'),
                wheat: tok('--c-wheat'),
                charcoal: tok('--c-charcoal'),
                earth: tok('--c-earth'),
                leaf: {
                    DEFAULT: tok('--c-leaf'),
                    light:   tok('--c-leaf-light'),
                },
                cream: tok('--c-cream'),
                clay: {
                    DEFAULT: tok('--c-clay'),
                    light:   tok('--c-clay-light'),
                },
                sun: {
                    DEFAULT: tok('--c-sun'),
                    light:   tok('--c-sun-light'),
                },
                navy: {
                    DEFAULT: tok('--c-navy'),
                    dark:    tok('--c-navy-dark'),
                },
                blue: {
                    DEFAULT: tok('--c-blue'),
                    mid:     tok('--c-blue-mid'),
                    light:   tok('--c-blue-light'),
                },
                sky: {
                    DEFAULT: tok('--c-sky'),
                    light:   tok('--c-sky-light'),
                },
                teal: {
                    DEFAULT: tok('--c-teal'),
                    light:   tok('--c-teal-light'),
                },
                amber: {
                    DEFAULT: tok('--c-amber'),
                    light:   tok('--c-amber-light'),
                    dark:    tok('--c-amber-dark'),
                },
                coral: {
                    DEFAULT: tok('--c-coral'),
                    light:   tok('--c-coral-light'),
                },
                'purple-light': tok('--c-purple-light'),
                'green-light':  tok('--c-green-light'),

                // Tokens semánticos de superficie/texto para modo oscuro. En claro
                // valen blanco/lienzo/tinta actuales; en `.dark` se invierten.
                surface:   tok('--c-surface'),
                'surface-2': tok('--c-surface-2'),
                canvas:    tok('--c-canvas'),
                content:   tok('--c-content'),
                'content-muted': tok('--c-content-muted'),
                hairline:  tok('--c-border'),
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
};
