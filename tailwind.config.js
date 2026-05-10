const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                jakarta: ['"Plus Jakarta Sans"', 'sans-serif'],
            },
            colors: {
                base:  '#EEF4FC',
                mist:  '#D4E4F7',
                slate: '#5A7FA8',
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
                },
                coral: {
                    DEFAULT: '#D94F3D',
                    light:   '#FCEAE8',
                },
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
};
