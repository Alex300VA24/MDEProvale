const mix = require('laravel-mix');

mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [
        require('postcss-import'),
        require('tailwindcss'),
        require('autoprefixer'),
    ])
    // Font Awesome: copiar CSS ya compilado y sus webfonts
    .copy('node_modules/@fortawesome/fontawesome-free/css/all.min.css', 'public/css/fontawesome.min.css')
    .copyDirectory('node_modules/@fortawesome/fontawesome-free/webfonts', 'public/webfonts')
    // Plus Jakarta Sans: copiar CSS y archivos de fuente
    .copyDirectory('node_modules/@fontsource/plus-jakarta-sans/files', 'public/fonts/plus-jakarta-sans/files')
    .copy('node_modules/@fontsource/plus-jakarta-sans/400.css', 'public/fonts/plus-jakarta-sans/400.css')
    .copy('node_modules/@fontsource/plus-jakarta-sans/500.css', 'public/fonts/plus-jakarta-sans/500.css')
    .copy('node_modules/@fontsource/plus-jakarta-sans/600.css', 'public/fonts/plus-jakarta-sans/600.css')
    .copy('node_modules/@fontsource/plus-jakarta-sans/700.css', 'public/fonts/plus-jakarta-sans/700.css')
    .copy('node_modules/@fontsource/plus-jakarta-sans/800.css', 'public/fonts/plus-jakarta-sans/800.css')
    // Select2
    .copy('node_modules/select2/dist/css/select2.min.css', 'public/css/select2.min.css')
    .copy('node_modules/select2/dist/js/select2.min.js', 'public/js/select2.min.js');

// Cache-busting: sin esto, css/app.css y js/app.js se sirven siempre con la
// misma URL, así que el navegador puede quedarse con una copia vieja en caché
// aunque el archivo haya cambiado en el build.
mix.version();
