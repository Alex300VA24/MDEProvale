<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Presidentas - PROVALE</title>
    <link rel="icon" href="{{ asset('img/logo-provale-sin-fondo.png') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/400.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/700.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/700.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <style>
        :root { --navy:#0B3A66; --blue:#175A91; --teal:#115E59; --sky:#F1F5F9; --line:#D6E1EC; --muted:#506E8D; --danger:#B4232D; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:#F3F6F9; color:var(--navy); font:16px/1.5 'Source Sans 3',sans-serif; }
        .shell { min-height:100vh; display:grid; place-items:center; padding:24px; }
        .card { width:min(100%,430px); padding:34px; border:1px solid rgba(212,228,247,.9); border-radius:24px; background:rgba(255,255,255,.96); box-shadow:0 28px 70px -38px rgba(26,46,74,.55); }
        .brand { display:flex; align-items:center; gap:13px; margin-bottom:26px; }
        .brand img { width:54px; height:54px; object-fit:contain; }
        .brand strong { display:block; font:700 18px 'Lexend',sans-serif; }
        .brand span { color:var(--teal); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
        h1 { margin:0; font:700 27px/1.2 'Lexend',sans-serif; }
        .intro { margin:8px 0 24px; color:var(--muted); }
        .field { margin-top:18px; }
        label { display:block; margin-bottom:7px; color:var(--muted); font-size:13px; font-weight:700; }
        .input-wrap { position:relative; }
        .input-wrap > i { position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#88A8CB; pointer-events:none; }
        input { width:100%; min-height:48px; padding:11px 46px; border:2px solid var(--line); border-radius:13px; background:#F8FBFE; color:var(--navy); font:600 16px 'Source Sans 3',sans-serif; }
        input:focus { outline:0; border-color:var(--blue); box-shadow:0 0 0 4px rgba(30,87,153,.13); background:#fff; }
        .toggle { position:absolute; right:5px; top:4px; width:40px; height:40px; border:0; border-radius:10px; background:transparent; color:#6D91B8; cursor:pointer; }
        .toggle:hover { background:#EAF2FB; color:var(--blue); }
        .error { margin-top:7px; color:var(--danger); font-size:13px; font-weight:600; }
        .remember { display:flex; align-items:center; gap:8px; margin:18px 0; color:#425F7F; font-size:14px; font-weight:600; }
        .remember input { width:18px; min-height:18px; padding:0; accent-color:var(--blue); }
        .submit { width:100%; min-height:48px; border:0; border-radius:6px; background:#0B3A66; color:#fff; font-size:15px; font-weight:700; cursor:pointer; box-shadow:0 12px 24px -14px rgba(11,58,102,.8); }
        .submit:hover { filter:brightness(1.05); }
        button:focus-visible,a:focus-visible { outline:3px solid rgba(30,87,153,.28); outline-offset:2px; }
        .help { margin:20px 0 0; text-align:center; color:var(--muted); font-size:13px; }
        @media(max-width:480px) { .shell { padding:14px; } .card { padding:26px 20px; border-radius:20px; } }
    </style>
    <link rel="stylesheet" href="{{ asset('css/president-portal.css') }}">
</head>
<body class="portal-login">
    <main class="shell">
        <section class="card" aria-labelledby="login-title">
            <div class="brand"><img src="{{ asset('img/logo-provale-sin-fondo.png') }}" alt="Logo PROVALE"><div><strong>Municipalidad Distrital de La Esperanza</strong><span>PROVALE · Portal de Presidentas</span></div></div>
            <h1 id="login-title">Consulta de comité</h1>
            <p class="intro">Ingresa con las credenciales asignadas a tu cuenta de Socia Presidenta.</p>

            <form method="POST" action="{{ route('president.login.store') }}" id="president-login-form">
                @csrf
                <div class="field">
                    <label for="username">Usuario</label>
                    <div class="input-wrap"><i class="fas fa-user" aria-hidden="true"></i><input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus aria-describedby="username-error"></div>
                    @error('username')<div class="error" id="username-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="input-wrap"><i class="fas fa-lock" aria-hidden="true"></i><input id="password" name="password" type="password" autocomplete="current-password" required aria-describedby="password-error"><button class="toggle" type="button" id="toggle-password" aria-label="Mostrar contraseña"><i class="fas fa-eye" aria-hidden="true"></i></button></div>
                    @error('password')<div class="error" id="password-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <label class="remember" for="remember"><input id="remember" name="remember" type="checkbox"> Recordarme</label>
                <button class="submit" id="submit" type="submit"><i class="fas fa-right-to-bracket" aria-hidden="true"></i> Ingresar al portal</button>
            </form>
            <p class="help">Acceso exclusivo para presidentas registradas y vigentes.</p>
        </section>
    </main>
    <script>
        const toggle = document.getElementById('toggle-password');
        const password = document.getElementById('password');
        toggle.addEventListener('click', () => {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            toggle.querySelector('i').className = visible ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
        document.getElementById('president-login-form').addEventListener('submit', () => {
            const button = document.getElementById('submit');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Ingresando...';
        });
    </script>
</body>
</html>
