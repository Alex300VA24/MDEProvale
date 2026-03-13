<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PROVALE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'jakarta': ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        charcoal: '#1E293B',
                        primary: { DEFAULT: '#0F766E', light: '#14B8A6', dark: '#0D5D56' },
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        #loading-screen {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #0F766E 0%, #1E293B 100%);
            display: flex;
            flex-col;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
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
        
        .login-bg {
            background-image: url('{{ asset("img/fondo.png") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            overflow: hidden;
        }
        
        .login-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.85) 0%, rgba(30, 41, 59, 0.9) 100%);
            z-index: 1;
        }
        
        .login-bg > * {
            position: relative;
            z-index: 2;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .input-focus:focus {
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.15);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #0F766E 0%, #14B8A6 100%);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">
    
    <div id="loading-screen">
        <div class="w-20 h-20 bg-white/10 rounded-2xl shadow-xl flex items-center justify-center mb-6 overflow-hidden backdrop-blur-sm">
            <img src="{{ asset('img/muni2.png') }}" alt="PROVALE" class="w-14 h-14 object-contain">
        </div>
        <h2 class="text-xl font-extrabold text-white mb-2">PROVALE</h2>
        <div class="loader-spin mb-4"></div>
        <p class="text-slate-400 text-sm">Cargando...</p>
    </div>
    
    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-3xl shadow-2xl mb-6 overflow-hidden">
                <img src="{{ asset('img/muni2.png') }}" alt="PROVALE" class="w-18 h-18 object-contain">
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">PROVALE</h1>
            <p class="text-teal-200 text-sm font-medium mt-2">Programa Vaso de Leche</p>
            <p class="text-slate-400 text-xs mt-1">Municipalidad Distrital de La Esperanza</p>
        </div>

        <div class="glass-card rounded-3xl p-8">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-slate-800">Bienvenido</h2>
                <p class="text-slate-500 text-sm mt-1">Ingresa tus credenciales para continuar</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-100 rounded-2xl">
                    <div class="flex items-center gap-2 text-red-600">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="text-sm font-medium">Credenciales incorrectas</span>
                    </div>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 p-4 bg-teal-50 border border-teal-100 rounded-2xl">
                    <span class="text-sm text-teal-600">{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="username" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Usuario</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                            class="w-full pl-12 pr-4 py-4 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-700 bg-slate-50 input-focus focus:outline-none focus:border-primary focus:bg-white transition-all"
                            placeholder="Ingresa tu usuario">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Contraseña</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input id="password" type="password" name="password" required
                            class="w-full pl-12 pr-14 py-4 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-700 bg-slate-50 input-focus focus:outline-none focus:border-primary focus:bg-white transition-all"
                            placeholder="••••••••">
                        <button type="button" id="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary transition-all">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" name="remember" class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary/20">
                        <span class="ml-2 text-sm text-slate-600">Recordarme</span>
                    </label>
                </div>

                <button type="submit" class="btn-login w-full py-4 text-white font-bold rounded-2xl hover:opacity-90 transition-all shadow-lg flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Iniciar Sesión</span>
                </button>
            </form>

            @if (Route::has('password.request'))
                <div class="mt-6 text-center">
                    <a href="{{ route('password.request') }}" class="text-sm text-slate-500 hover:text-primary transition-all">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            @endif
        </div>

        <div class="text-center mt-8">
            <p class="text-slate-400 text-xs">© 2024 PROVALE - Todos los derechos reservados</p>
        </div>
    </div>

    @if(session('session_expired'))
    <div id="session-expired-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="glass-card w-full max-w-sm rounded-3xl p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center text-red-500">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Sesión expirada</h3>
                    <p class="text-sm text-slate-500">Tu sesión ha expirado</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 mb-6">
                Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.
            </p>
            <button id="session-expired-confirm" class="w-full py-3 bg-slate-800 text-white font-bold rounded-2xl hover:bg-slate-700 transition-all">
                Entendido
            </button>
        </div>
    </div>
    @endif

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loading-screen')?.classList.add('hidden');
            }, 800);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const button = document.getElementById('session-expired-confirm');
            if (button) {
                button.addEventListener('click', function() {
                    document.getElementById('session-expired-modal')?.remove();
                });
            }

            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    eyeIcon.classList.toggle('fa-eye');
                    eyeIcon.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>
