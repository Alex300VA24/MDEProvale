<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PROVALE</title>
    <link rel="icon" href="{{ asset('img/logovaso.svg') }}">
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/400.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/500.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/700.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/plus-jakarta-sans/800.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script src="{{ mix('js/app.js') }}" defer></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        .login-bg {
            background-image: url('{{ asset("img/banner.png") }}');
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
            background: linear-gradient(135deg, #1E5799 0%, #2E6DB4 100%);
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
<body class="login-bg min-h-screen flex items-center justify-center p-4 overflow-y-auto">
    
    <x-loading-screen subtitle="Programa Vaso de Leche" />

    <div class="w-full max-w-md relative z-10 py-4">

        <div class="glass-card rounded-3xl p-6 sm:p-8">
            <div class="text-center mb-6 sm:mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 bg-white rounded-3xl shadow-2xl mb-4 sm:mb-6 overflow-hidden">
                <img src="{{ asset('img/muni2.png') }}" alt="PROVALE" class="w-18 h-18 object-contain">
            </div>
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

            <form method="POST" action="{{ route('login') }}" class="space-y-4 sm:space-y-5">
                @csrf

                <div>
                    <label for="username" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Usuario</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                            class="w-full pl-12 pr-4 py-3 sm:py-4 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-700 bg-slate-50 input-focus focus:outline-none focus:border-blue focus:bg-white transition-all"
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
                            class="w-full pl-12 pr-14 py-3 sm:py-4 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-700 bg-slate-50 input-focus focus:outline-none focus:border-blue focus:bg-white transition-all"
                            placeholder="••••••••">
                        <button type="button" id="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue transition-all">
                            <i class="fas fa-eye-slash" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" name="remember" class="w-5 h-5 rounded border-slate-300 text-blue focus:ring-primary/20">
                        <span class="ml-2 text-sm text-slate-600">Recordarme</span>
                    </label>
                </div>

                <button type="submit" class="btn-login w-full py-3 sm:py-4 text-white font-bold rounded-2xl hover:opacity-90 transition-all shadow-lg flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Iniciar Sesión</span>
                </button>
            </form>

            @if (Route::has('password.request'))
                <div class="mt-6 text-center">
                    <button type="button" class="text-sm text-slate-500 hover:text-blue transition-all" onclick="openModal('modal-forgot-password')">
                        ¿Olvidaste tu contraseña?
                    </button>
                </div>
            @endif
        </div>

        <div class="text-center mt-8">
            <p class="text-slate-400 text-xs">© 2026 PROVALE - Todos los derechos reservados</p>
        </div>
    </div>

    @if(session('session_expired') || request()->get('expired') == 1)
    <div id="session-expired-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-sm rounded-3xl p-6 shadow-2xl border-2 border-red-200">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold text-red-700">Sesión expirada</h3>
                    <p class="text-sm font-medium text-red-500">Tu sesión ha expirado</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente para continuar.
            </p>
            <button id="session-expired-confirm" class="w-full py-3 text-white font-bold rounded-xl hover:bg-red-700 transition-all shadow-lg" style="background-color: #DC2626 !important;">
                Entendido
            </button>
        </div>
    </div>
    @endif

    <div id="modal-forgot-password" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" style="display: none;">
        <div class="glass-card w-full max-w-sm rounded-3xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue/10 rounded-xl flex items-center justify-center text-blue">
                        <i class="fas fa-key text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Restablecer Contraseña</h3>
                </div>
                <button onclick="closeModal('modal-forgot-password')" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <p class="text-sm text-slate-500 mb-4">Ingresa tu correo electrónico para solicitar la recuperación de contraseña. Un administrador aprobará tu solicitud.</p>
            <form id="form-forgot-password" onsubmit="submitForgotPassword(event)">
                @csrf
                <div class="mb-4">
                    <label for="forgot-email" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Correo Electrónico</label>
                    <input type="email" id="forgot-email" name="email" required
                        class="w-full px-4 py-3 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-700 bg-slate-50 focus:outline-none focus:border-blue focus:bg-white transition-all"
                        placeholder="correo@ejemplo.com">
                </div>
                <div id="forgot-message" class="mb-4 p-3 rounded-xl text-sm" style="display: none;"></div>
                <button type="submit" id="forgot-submit" class="w-full py-3 bg-blue text-white font-bold rounded-2xl hover:opacity-90 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-paper-plane"></i>
                    <span>Enviar Solicitud</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function showSwalWhenReady(config, retries = 20) {
            if (window.Swal) {
                window.Swal.fire(config);
                return;
            }

            if (retries > 0) {
                setTimeout(() => showSwalWhenReady(config, retries - 1), 150);
            }
        }

        window.addEventListener('load', function() {
            // No mostrar loading al cargar la página
        });

        document.addEventListener('DOMContentLoaded', function() {
            const statusMessage = @json(session('status'));

            if (statusMessage) {
                showSwalWhenReady({
                    icon: 'success',
                    title: 'Contraseña restablecida correctamente',
                    text: statusMessage,
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#1E5799'
                });
            }

            // Mostrar loading al enviar el formulario de login
            const loginForm = document.querySelector('form[action*="login"]');
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    document.getElementById('loading-screen').classList.add('active');
                });
            }

            const button = document.getElementById('session-expired-confirm');
            if (button) {
                button.addEventListener('click', function() {
                    document.getElementById('session-expired-modal')?.remove();
                    if (window.history.replaceState) {
                        const cleanUrl = window.location.pathname;
                        window.history.replaceState({}, document.title, cleanUrl);
                    }
                });
            }

            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    if (type === 'text') {
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    } else {
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                    }
                });
            }
        });

        // Funciones para modales
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('[class*="fixed inset-0"]').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        // Enviar solicitud de recuperación de contraseña
        async function submitForgotPassword(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = document.getElementById('forgot-submit');
            const messageDiv = document.getElementById('forgot-message');
            const email = document.getElementById('forgot-email').value;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            try {
                const response = await fetch('{{ route("password-reset-request") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ email: email })
                });

                const data = await response.json();

                if (data.success) {
                    messageDiv.style.display = 'block';
                    messageDiv.className = 'mb-4 p-3 rounded-xl text-sm bg-green-50 text-green-600 border border-green-200';
                    messageDiv.innerHTML = '<i class="fas fa-check-circle mr-1"></i> ' + data.message;
                    setTimeout(() => {
                        closeModal('modal-forgot-password');
                        document.getElementById('forgot-email').value = '';
                        messageDiv.style.display = 'none';
                    }, 3000);
                } else {
                    messageDiv.style.display = 'block';
                    messageDiv.className = 'mb-4 p-3 rounded-xl text-sm bg-red-50 text-red-600 border border-red-200';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> ' + (data.message || 'Error al enviar solicitud');
                }
            } catch (error) {
                messageDiv.style.display = 'block';
                messageDiv.className = 'mb-4 p-3 rounded-xl text-sm bg-red-50 text-red-600 border border-red-200';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Error de conexión';
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i><span>Enviar Solicitud</span>';
        }
    </script>
</body>
</html>
