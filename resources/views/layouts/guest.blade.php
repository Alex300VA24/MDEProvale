<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/400.css') }}">
        <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/500.css') }}">
        <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/600.css') }}">
        <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/700.css') }}">
        <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/800.css') }}">
        <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <script src="{{ asset('js/app.js') }}" defer></script>
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; }
        </style>
    </head>
    <body class="bg-cream min-h-screen">
        <x-loading-screen class="active" subtitle="Cargando..." />

        <div class="font-sans text-slate-700 antialiased">
            {{ $slot }}
        </div>

        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    document.getElementById('loading-screen')?.classList.add('hidden');
                }, 600);
            });
        </script>
    </body>
</html>
