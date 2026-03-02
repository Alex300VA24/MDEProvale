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
                        cream: '#FDF8F3',
                        wheat: '#F5E6D3',
                        earth: '#8B7355',
                        charcoal: '#2C2420',
                        leaf: { DEFAULT: '#4A7C59', light: '#E8F5E9', dark: '#3d6647' },
                        sun: { DEFAULT: '#F4A261', light: '#FEF3E2' },
                        clay: { DEFAULT: '#E76F51', light: '#FCE8E4' },
                        sky: { DEFAULT: '#87CEEB', light: '#E0F2FE' },
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-cream min-h-screen flex items-center justify-center">
    
    <div class="w-full max-w-md px-6 py-8">
        <!-- Logo y Título -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-leaf rounded-2xl shadow-lg mb-4 overflow-hidden">
                <img src="{{ asset('img/logo.png') }}" alt="PROVALE" class="w-14 h-14 object-contain">
            </div>
            <h1 class="text-2xl font-extrabold text-charcoal">PROVALE</h1>
            <p class="text-earth text-sm font-medium">Programa Vaso de Leche</p>
            <p class="text-earth text-xs mt-1">Municipalidad Distrital de La Esperanza</p>
        </div>

        <!-- Formulario -->
        <div class="bg-white rounded-3xl shadow-xl border-2 border-wheat p-8">
            <h2 class="text-xl font-bold text-charcoal text-center mb-6">Iniciar Sesión</h2>

            <!-- Errores de sesión -->
            @if ($errors->any())
                <div class="mb-4 p-4 bg-clay-light border border-clay rounded-xl">
                    <ul class="text-sm text-clay">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Estado de sesión -->
            @if (session('status'))
                <div class="mb-4 p-4 bg-leaf-light border border-leaf rounded-xl">
                    <span class="text-sm text-leaf">{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Username -->
                <div class="mb-4">
                    <label for="username" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">Usuario</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-earth">
                            <i class="fas fa-user"></i>
                        </span>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                            class="w-full pl-11 pr-4 py-3 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">Contraseña</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-earth">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input id="password" type="password" name="password" required
                            class="w-full pl-11 pr-4 py-3 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="mb-6">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" name="remember" class="rounded border-wheat text-leaf focus:ring-leaf">
                        <span class="ml-2 text-sm text-earth">Recordarme</span>
                    </label>
                </div>

                <!-- Botón -->
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-leaf to-leaf-dark text-white font-bold rounded-xl hover:opacity-90 transition-all shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i> Iniciar Sesión
                </button>
            </form>

            <!-- Link forgot password -->
            @if (Route::has('password.request'))
                <div class="mt-4 text-center">
                    <a href="{{ route('password.request') }}" class="text-sm text-earth hover:text-leaf transition-all">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            @endif
        </div>

        <!-- Pie -->
        <div class="text-center mt-6">
            <p class="text-earth text-xs">© 2024 PROVALE - Todos los derechos reservados</p>
        </div>
    </div>

</body>
</html>
