<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - PROVALE</title>
    <link rel="icon" href="{{ asset('img/logo-provale-sin-fondo.png') }}">
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

        .login-shell {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            background: #EEF4FC;
            overflow: hidden;
        }

        /* ===== Panel del formulario (derecha) ===== */
        .form-panel {
            flex: 0 1 560px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 5vw 2rem 1.5rem;
            position: relative;
            z-index: 2;
        }

        /* ===== Banner completo de fondo (cortado tras la niña, resto atenuado) ===== */
        @media (min-width: 1024px) {
            .login-shell {
                background-image: url('{{ asset("img/banner.jfif") }}');
                background-size: cover;
                background-position: left center;
                background-repeat: no-repeat;
            }
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 1.75rem;
            box-shadow: 0 30px 60px -20px rgba(15, 30, 48, 0.25), 0 10px 30px -15px rgba(30, 87, 153, 0.15);
            padding: 2.25rem 2rem;
            animation: card-in 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .login-logo {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            margin-bottom: 1.75rem;
        }

        .login-logo img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            border-radius: 1rem;
            padding: 0.35rem;
            background: #EBF3FD;
            box-shadow: 0 8px 20px -8px rgba(30, 87, 153, 0.4);
        }

        .login-logo div strong {
            display: block;
            font-size: 1.05rem;
            font-weight: 800;
            color: #1A2E4A;
            letter-spacing: -0.01em;
            line-height: 1.1;
        }

        .login-logo div span {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #0E8A7A;
        }

        .login-heading h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1A2E4A;
            letter-spacing: -0.02em;
            margin-bottom: 0.4rem;
        }

        .login-heading p {
            font-size: 0.9rem;
            color: #5A7FA8;
            margin-bottom: 1.75rem;
        }

        .field {
            margin-bottom: 1.25rem;
            animation: field-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .field:nth-child(2) { animation-delay: 0.08s; }
        .field:nth-child(3) { animation-delay: 0.16s; }
        .field:nth-child(4) { animation-delay: 0.24s; }
        .login-submit-wrap { animation: field-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.3s both; }

        .field label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #5A7FA8;
            margin-bottom: 0.5rem;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap > i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9DB7D4;
            font-size: 0.95rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .input-wrap input {
            width: 100%;
            padding: 0.95rem 1rem 0.95rem 2.85rem;
            border: 2px solid #D4E4F7;
            border-radius: 1rem;
            background: #F8FBFE;
            font-size: 0.92rem;
            font-weight: 600;
            color: #1A2E4A;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .input-wrap input::placeholder { color: #A8BFD9; font-weight: 500; }

        .input-wrap input:hover { border-color: #B7D0EA; }

        .input-wrap input:focus {
            border-color: #1E5799;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(30, 87, 153, 0.12);
        }

        .input-wrap:focus-within > i { color: #1E5799; }

        .toggle-password {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            width: 2.4rem;
            height: 2.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.7rem;
            color: #9DB7D4;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: color 0.2s ease, background 0.2s ease;
        }

        .toggle-password:hover { color: #1E5799; background: #EBF3FD; }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .remember label {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #2D3748;
            cursor: pointer;
        }

        .remember input {
            width: 1.1rem;
            height: 1.1rem;
            accent-color: #1E5799;
            cursor: pointer;
        }

        .link-forgot {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1E5799;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            transition: color 0.2s ease;
        }

        .link-forgot:hover { color: #0E8A7A; text-decoration: underline; }

        .btn-login {
            position: relative;
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 1rem;
            background: linear-gradient(135deg, #1E5799 0%, #2E6DB4 100%);
            color: #fff;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            overflow: hidden;
            box-shadow: 0 12px 24px -10px rgba(30, 87, 153, 0.5);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -120%;
            width: 60%;
            height: 100%;
            background: linear-gradient(100deg, transparent, rgba(255, 255, 255, 0.28), transparent);
            transform: skewX(-20deg);
            transition: left 0.6s ease;
        }

        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 16px 32px -10px rgba(30, 87, 153, 0.6); filter: brightness(1.04); }
        .btn-login:hover::before { left: 160%; }
        .btn-login:active { transform: translateY(0); }
        .btn-login:focus-visible { outline: none; box-shadow: 0 0 0 4px rgba(30, 87, 153, 0.25), 0 12px 24px -10px rgba(30, 87, 153, 0.5); }
        .btn-login:disabled { opacity: 0.75; cursor: not-allowed; transform: none; }

        .error-banner {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #B91C1C;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            animation: shake 0.4s ease both;
        }

        .login-copy {
            margin-top: 1.75rem;
            text-align: center;
            font-size: 0.75rem;
            color: #9DB7D4;
        }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(24px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes field-in {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

        @media (prefers-reduced-motion: reduce) {
            .login-card, .field, .login-submit-wrap { animation: none; }
            .btn-login::before { display: none; }
            .error-banner { animation: none; }
        }
    </style>
</head>
<body>
    <x-loading-screen subtitle="Programa Vaso de Leche" />

    <div class="login-shell">
        <!-- Panel del formulario -->
        <main class="form-panel">
            <div class="login-card">
                <div class="login-logo">
                    <img src="{{ asset('img/logo-provale-sin-fondo.png') }}" alt="PROVALE">
                    <div>
                        <strong>PROVALE</strong>
                        <span>Vaso de Leche</span>
                    </div>
                </div>

                <div class="login-heading">
                    <h1>Bienvenido</h1>
                    <p>Ingresa tus credenciales para continuar.</p>
                </div>

                @if ($errors->any())
                    <div class="error-banner">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Credenciales incorrectas. Verifica tus datos.</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf

                    <div class="field">
                        <label for="username">Usuario</label>
                        <div class="input-wrap">
                            <i class="fas fa-user"></i>
                            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                                placeholder="Ingresa tu usuario" autocomplete="username">
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Contraseña</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock"></i>
                            <input id="password" type="password" name="password" required
                                placeholder="••••••••" autocomplete="current-password">
                            <button type="button" id="togglePassword" class="toggle-password" aria-label="Mostrar contraseña">
                                <i class="fas fa-eye-slash" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <span class="remember">
                            <label for="remember_me">
                                <input id="remember_me" type="checkbox" name="remember">
                                <span>Recordarme</span>
                            </label>
                        </span>
                        @if (Route::has('password.request'))
                            <button type="button" class="link-forgot" onclick="openModal('modal-forgot-password')">
                                ¿Olvidaste tu contraseña?
                            </button>
                        @endif
                    </div>

                    <div class="login-submit-wrap">
                        <button type="submit" id="login-submit" class="btn-login">
                            <i class="fas fa-right-to-bracket"></i>
                            <span>Iniciar Sesión</span>
                        </button>
                    </div>
                </form>

                <p class="login-copy">
                    <i class="fas fa-lock text-[0.7rem]"></i> Acceso restringido al personal autorizado.
                </p>
            </div>
        </main>
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
        <div class="glass-card w-full max-w-sm rounded-3xl p-6" style="background: #fff; border: 1px solid #D4E4F7;">
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

            // Mostrar loading y estado del botón al enviar el formulario de login
            const loginForm = document.getElementById('login-form');
            const loginSubmit = document.getElementById('login-submit');
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    document.getElementById('loading-screen').classList.add('active');
                    if (loginSubmit) {
                        loginSubmit.disabled = true;
                        loginSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ingresando...';
                    }
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
                        togglePassword.setAttribute('aria-label', 'Ocultar contraseña');
                    } else {
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                        togglePassword.setAttribute('aria-label', 'Mostrar contraseña');
                    }
                });
            }
        });

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