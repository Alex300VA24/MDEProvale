<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - PROVALE</title>
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
    
    <div class="w-full max-w-2xl px-6 py-8">
        <!-- Logo y Título -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-leaf rounded-2xl shadow-lg mb-3 overflow-hidden">
                <img src="{{ asset('img/logo.png') }}" alt="PROVALE" class="w-12 h-12 object-contain">
            </div>
            <h1 class="text-xl font-extrabold text-charcoal">PROVALE</h1>
            <p class="text-earth text-xs">Programa Vaso de Leche - M.D. La Esperanza</p>
        </div>

        <!-- Formulario -->
        <div class="bg-white rounded-3xl shadow-xl border-2 border-wheat p-6">
            <h2 class="text-lg font-bold text-charcoal text-center mb-5">Crear Cuenta</h2>

            <!-- Errores de validación -->
            @if ($errors->any())
                <div class="mb-4 p-4 bg-clay-light border border-clay rounded-xl">
                    <ul class="text-sm text-clay">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nombres -->
                    <div>
                        <label for="names" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">Nombres</label>
                        <input id="names" type="text" name="names" value="{{ old('names') }}" required
                            class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>

                    <!-- Apellido Paterno -->
                    <div>
                        <label for="father_surname" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">Apellido Paterno</label>
                        <input id="father_surname" type="text" name="father_surname" value="{{ old('father_surname') }}" required
                            class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>

                    <!-- Apellido Materno -->
                    <div>
                        <label for="mother_surname" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">Apellido Materno</label>
                        <input id="mother_surname" type="text" name="mother_surname" value="{{ old('mother_surname') }}" required
                            class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>

                    <!-- Usuario -->
                    <div>
                        <label for="username" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">Usuario</label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" required
                            class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>

                    <!-- DNI -->
                    <div>
                        <label for="dni" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">DNI</label>
                        <input id="dni" type="text" name="dni" value="{{ old('dni') }}" required maxlength="8"
                            class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>

                    <!-- CUI -->
                    <div>
                        <label for="cui" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">CUI</label>
                        <input id="cui" type="text" name="cui" value="{{ old('cui') }}" required
                            class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>

                    <!-- Email -->
                    <div class="md:col-span-2">
                        <label for="email" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">Correo Electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">Contraseña</label>
                        <input id="password" type="password" name="password" required
                            class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-earth uppercase tracking-wider mb-2">Confirmar Contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-cream focus:outline-none focus:border-leaf transition-all">
                    </div>
                </div>

                <!-- Botón -->
                <div class="mt-6">
                    <button type="submit" class="w-full py-3 bg-gradient-to-r from-leaf to-leaf-dark text-white font-bold rounded-xl hover:opacity-90 transition-all shadow-lg">
                        <i class="fas fa-user-plus mr-2"></i> Registrarse
                    </button>
                </div>
            </form>

            <!-- Link login -->
            <div class="mt-4 text-center">
                <span class="text-sm text-earth">¿Ya tienes cuenta? </span>
                <a href="{{ route('login') }}" class="text-sm text-leaf font-bold hover:underline">
                    Iniciar Sesión
                </a>
            </div>
        </div>

        <!-- Pie -->
        <div class="text-center mt-4">
            <p class="text-earth text-xs">© 2024 PROVALE - Todos los derechos reservados</p>
        </div>
    </div>

</body>
</html>
