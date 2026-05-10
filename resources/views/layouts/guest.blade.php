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
            
            #loading-screen {
                position: fixed;
                inset: 0;
                background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
                display: flex;
                flex-col;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                transition: opacity 0.4s ease, visibility 0.4s ease;
            }
            #loading-screen.hidden {
                opacity: 0;
                visibility: hidden;
            }
            .loader-spin {
                width: 50px;
                height: 50px;
                border: 4px solid rgba(255,255,255,0.2);
                border-top: 4px solid #1E5799;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body class="bg-cream min-h-screen">
        <div id="loading-screen">
            <div class="w-20 h-20 bg-blue rounded-xl shadow-xl flex items-center justify-center mb-6 overflow-hidden">
                <img src="{{ asset('img/muni2.png') }}" alt="PROVALE" class="w-14 h-14 object-contain">
            </div>
            <h2 class="text-xl font-extrabold text-white mb-2">PROVALE</h2>
            <div class="loader-spin mb-4"></div>
            <p class="text-slate-400 text-sm">Cargando...</p>
        </div>
        
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
