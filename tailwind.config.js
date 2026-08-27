const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
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
                base:  '#EEF4FC',
                mist:  '#D4E4F7',
                slate: '#5A7FA8',
                wheat: '#E8DCC8',
                charcoal: '#2D3748',
                earth: '#6B7280',
                leaf: {
                    DEFAULT: '#16A34A',
                    light: '#DCFCE7',
                },
                cream: '#FDF8F0',
                clay: {
                    DEFAULT: '#DC2626',
                    light: '#FEE2E2',
                },
                sun: {
                    DEFAULT: '#D97706',
                    light: '#FEF3C7',
                },
                navy: {
                    DEFAULT: '#1A2E4A',
                    dark:    '#0F1E30',
                },
                blue: {
                    DEFAULT: '#1E5799',
                    mid:     '#2E6DB4',
                    light:   '#EBF3FD',
                },
                sky: {
                    DEFAULT: '#4A90D9',
                    light:   '#D6EAFC',
                },
                teal: {
                    DEFAULT: '#0E8A7A',
                    light:   '#D4F2EE',
                },
                amber: {
                    DEFAULT: '#E5930A',
                    light:   '#FEF3DC',
                    dark:    '#B87300',
                },
                coral: {
                    DEFAULT: '#D94F3D',
                    light:   '#FCEAE8',
                },
                'purple-light': '#F3E8FF',
                'green-light': '#DCFCE7',
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
};
