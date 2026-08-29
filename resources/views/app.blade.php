<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="provale-loading-logo" content="{{ asset('img/muni2.png') }}">
<link rel="preload" as="image" href="{{ asset('img/muni2.png') }}" fetchpriority="high">
        <title inertia>{{ config('app.name', 'MDEProvale') }}</title>
        <script>window.APP_URL = @json(rtrim(url('/'), '/'));</script>

        @if (file_exists(public_path('hot')))
            {{-- Dev: servidor de Vite con HMR --}}
            @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @else
            @php
                $prodManifest = json_decode(
                    file_get_contents(public_path('build/manifest.json')),
                    true
                );
                $prodBase = rtrim(url('/'), '/');
                $prodCss = $prodManifest['resources/css/app.css'] ?? null;
                $prodJs = $prodManifest['resources/js/app.jsx'] ?? null;
            @endphp
            {{-- Prod: rutas con prefijo relativo para funcionar con `php artisan serve`
                 y tambien bajo una subcarpeta de htdocs (p. ej. /MDEProvale/public). --}}
            @if ($prodCss && isset($prodCss['file']))
                <link rel="stylesheet" href="{{ $prodBase . '/build/' . $prodCss['file'] }}">
            @endif
            @if ($prodJs)
                @foreach ($prodJs['css'] ?? [] as $prodCssFile)
                    <link rel="stylesheet" href="{{ $prodBase . '/build/' . $prodCssFile }}">
                @endforeach
                <script type="module" src="{{ $prodBase . '/build/' . $prodJs['file'] }}" crossorigin></script>
            @endif
        @endif
        @inertiaHead
    </head>
    <body class="font-jakarta antialiased">
        @inertia
    </body>
</html>
