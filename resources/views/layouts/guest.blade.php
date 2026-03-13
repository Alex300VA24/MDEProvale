<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: { 'jakarta': ['"Plus Jakarta Sans"', 'sans-serif'] },
                        colors: {
                            cream: '#F8FAFC',
                            slate: '#475569',
                            charcoal: '#1E293B',
                            primary: { DEFAULT: '#0F766E', light: '#14B8A6', dark: '#0D5D56' },
                            secondary: '#64748B',
                            accent: '#F59E0B',
                        }
                    }
                }
            }
        </script>
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
                border-top: 4px solid #14B8A6;
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
            <div class="w-20 h-20 bg-primary rounded-xl shadow-xl flex items-center justify-center mb-6 overflow-hidden">
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
