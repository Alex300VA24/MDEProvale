<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="provale-loading-logo" content="{{ asset('img/muni2.png') }}">
        <link rel="preload" as="image" href="{{ asset('img/muni2.png') }}" fetchpriority="high">
        <title inertia>{{ config('app.name', 'MDEProvale') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body class="font-jakarta antialiased">
        @inertia
    </body>
</html>
