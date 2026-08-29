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
                base:  '#F1F5F9',
                mist:  '#D6E1EC',
                slate: '#506E8D',
                wheat: '#E8DCC8',
                charcoal: '#2D3748',
                earth: '#6B7280',
                leaf: {
                    DEFAULT: '#166534',
                    light: '#DCFCE7',
                },
                cream: '#FDF8F0',
                clay: {
                    DEFAULT: '#DC2626',
                    light: '#FEE2E2',
                },
                sun: {
                    DEFAULT: '#92400E',
                    light: '#FEF3C7',
                },
                navy: {
                    DEFAULT: '#0B3A66',
                    dark:    '#072A4D',
                },
                blue: {
                    DEFAULT: '#0B3A66',
                    mid:     '#175A91',
                    light:   '#E1EDF7',
                },
                sky: {
                    DEFAULT: '#075985',
                    light:   '#E0F2FE',
                },
                teal: {
                    DEFAULT: '#115E59',
                    light:   '#CCFBF1',
                },
                amber: {
                    DEFAULT: '#E5930A',
                    light:   '#FEF3DC',
                    dark:    '#B87300',
                },
                coral: {
                    DEFAULT: '#B4232D',
                    light:   '#FEE2E2',
                },
                'purple-light': '#F3E8FF',
                'green-light': '#DCFCE7',
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
};
