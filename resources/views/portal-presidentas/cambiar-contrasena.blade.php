<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña - Portal de Presidentas</title>
    <link rel="icon" href="{{ asset('img/logo-provale-sin-fondo.png') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/400.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/700.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/700.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <style>
        :root { --navy:#1A2E4A; --blue:#1E5799; --teal:#0E8A7A; --sky:#EEF4FC; --line:#D4E4F7; --muted:#5A7FA8; --success:#15803D; --danger:#B91C1C; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:linear-gradient(145deg,#EAF3FC,#F7FBFF); color:var(--navy); font:16px/1.5 'Source Sans 3',sans-serif; }
        .skip-link { position:fixed; left:12px; top:-60px; z-index:100; padding:10px 14px; border-radius:10px; background:var(--navy); color:#fff; font-weight:700; }
        .skip-link:focus { top:12px; }
        .topbar { position:sticky; top:0; z-index:10; background:rgba(255,255,255,.96); border-bottom:1px solid var(--line); backdrop-filter:blur(12px); }
        .topbar-inner,.shell { width:min(1180px,calc(100% - 32px)); margin:auto; }
        .topbar-inner { min-height:76px; display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .brand { display:flex; align-items:center; gap:12px; }
        .brand img { width:48px; height:48px; object-fit:contain; }
        .brand strong { display:block; font:700 17px 'Lexend',sans-serif; }
        .brand span { color:var(--teal); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; }
        .logout { min-width:112px; min-height:44px; border:1px solid var(--line); background:#fff; color:var(--blue); border-radius:13px; font-weight:700; cursor:pointer; }
        .logout:hover { background:#F4F8FC; }
        button:focus-visible,a:focus-visible,input:focus-visible { outline:3px solid rgba(30,87,153,.28); outline-offset:2px; }
        .shell { min-height:calc(100vh - 76px); display:grid; place-items:center; padding:30px 0 50px; }
        .card { width:min(100%,460px); padding:34px; border:1px solid rgba(212,228,247,.9); border-radius:24px; background:rgba(255,255,255,.96); box-shadow:0 28px 70px -38px rgba(26,46,74,.55); }
        h1 { margin:0; font:700 25px/1.2 'Lexend',sans-serif; }
        .intro { margin:8px 0 4px; color:var(--muted); }
        .alert { padding:13px 15px; border-radius:13px; font-weight:600; font-size:14px; margin-top:16px; }
        .alert-success { border:1px solid #BBF7D0; background:#F0FDF4; color:var(--success); }
        .alert-danger { border:1px solid #FECACA; background:#FEF2F2; color:var(--danger); }
        .field { margin-top:18px; }
        label { display:block; margin-bottom:7px; color:var(--muted); font-size:13px; font-weight:700; }
        .input-wrap { position:relative; }
        .input-wrap > i { position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#88A8CB; pointer-events:none; }
        input { width:100%; min-height:48px; padding:11px 46px; border:2px solid var(--line); border-radius:13px; background:#F8FBFE; color:var(--navy); font:600 16px 'Source Sans 3',sans-serif; }
        input:focus { outline:0; border-color:var(--blue); box-shadow:0 0 0 4px rgba(30,87,153,.13); background:#fff; }
        .toggle { position:absolute; right:5px; top:4px; width:40px; height:40px; border:0; border-radius:10px; background:transparent; color:#6D91B8; cursor:pointer; }
        .toggle:hover { background:#EAF2FB; color:var(--blue); }
        .error { margin-top:7px; color:var(--danger); font-size:13px; font-weight:600; }
        .hint { margin-top:8px; color:var(--muted); font-size:13px; }
        .submit { width:100%; min-height:48px; margin-top:22px; border:0; border-radius:13px; background:linear-gradient(135deg,var(--blue),#2E6DB4); color:#fff; font-size:15px; font-weight:700; cursor:pointer; box-shadow:0 12px 24px -14px rgba(30,87,153,.8); }
        .submit:hover { filter:brightness(1.05); }
        @media(max-width:480px) { .shell { padding:20px 0 40px; } .topbar-inner,.shell { width:min(100% - 20px,1180px); } .brand span { display:none; } .logout { min-width:48px; font-size:0; } .logout i { font-size:16px; } .card { padding:26px 20px; border-radius:20px; } }
        @media(prefers-reduced-motion:reduce) { * { transition:none!important; } }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Saltar al contenido</a>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand"><img src="{{ asset('img/logo-provale-sin-fondo.png') }}" width="48" height="48" alt="Logo PROVALE"><div><strong>PROVALE</strong><span>Portal de Presidentas</span></div></div>
            <form method="POST" action="{{ route('president.logout') }}">@csrf<button class="logout" type="submit"><i class="fas fa-right-from-bracket" aria-hidden="true"></i> Cerrar sesión</button></form>
        </div>
    </header>

    <main class="shell" id="main-content">
        <section class="card" aria-labelledby="password-title">
            <h1 id="password-title">Cambia tu contraseña</h1>
            <p class="intro">Por seguridad debes definir una nueva contraseña antes de continuar.</p>

            @if(session('success'))
                <div class="alert alert-success" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> Revisa los errores en el formulario.</div>
            @endif

            <form method="POST" action="{{ route('president.password.update') }}" id="password-form">
                @csrf
                <div class="field">
                    <label for="current_password">Contraseña actual</label>
                    <div class="input-wrap"><i class="fas fa-lock" aria-hidden="true"></i><input id="current_password" name="current_password" type="password" autocomplete="current-password" required aria-describedby="current-password-error"><button class="toggle" type="button" data-toggle-for="current_password" aria-label="Mostrar contraseña"><i class="fas fa-eye" aria-hidden="true"></i></button></div>
                    @error('current_password')<div class="error" id="current-password-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password">Nueva contraseña</label>
                    <div class="input-wrap"><i class="fas fa-key" aria-hidden="true"></i><input id="password" name="password" type="password" autocomplete="new-password" required aria-describedby="password-error"><button class="toggle" type="button" data-toggle-for="password" aria-label="Mostrar contraseña"><i class="fas fa-eye" aria-hidden="true"></i></button></div>
                    <p class="hint">Mínimo 8 caracteres y diferente a tu DNI.</p>
                    @error('password')<div class="error" id="password-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmar nueva contraseña</label>
                    <div class="input-wrap"><i class="fas fa-key" aria-hidden="true"></i><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button class="toggle" type="button" data-toggle-for="password_confirmation" aria-label="Mostrar contraseña"><i class="fas fa-eye" aria-hidden="true"></i></button></div>
                </div>
                <button class="submit" id="submit" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Guardar y continuar</button>
            </form>
        </section>
    </main>
    <script>
        document.querySelectorAll('.toggle').forEach(button => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.toggleFor);
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
                button.querySelector('i').className = visible ? 'fas fa-eye' : 'fas fa-eye-slash';
            });
        });
        document.getElementById('password-form').addEventListener('submit', () => {
            const button = document.getElementById('submit');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Guardando...';
        });
    </script>
</body>
</html>