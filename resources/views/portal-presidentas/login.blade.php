<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>try{if(localStorage.getItem('mde-theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}</script>
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
        .session-alert-backdrop { position:fixed; inset:0; z-index:60; display:grid; place-items:center; padding:20px; background:rgba(5,25,45,.68); }
        .session-alert { width:min(100%,390px); padding:28px; border-radius:16px; background:#fff; box-shadow:0 28px 70px -24px rgba(5,25,45,.72); }
        .session-alert-icon { display:grid; place-items:center; width:48px; height:48px; margin-bottom:18px; border-radius:12px; background:#FFF7E0; color:#9A6700; font-size:21px; }
        .session-alert h2 { margin:0; font:700 22px/1.25 'Lexend',sans-serif; }
        .session-alert p { margin:10px 0 22px; color:var(--muted); line-height:1.55; }
        .session-alert button { width:100%; min-height:46px; border:0; border-radius:8px; background:var(--navy); color:#fff; font:700 15px 'Source Sans 3',sans-serif; cursor:pointer; }
        .session-alert button:hover { background:var(--blue); }
        @media(max-width:480px) { .shell { padding:14px; } .card { padding:26px 20px; border-radius:20px; } }

        .theme-fab { position:fixed; top:16px; right:16px; z-index:70; width:44px; height:44px; display:flex; align-items:center; justify-content:center; border-radius:9999px; border:1px solid var(--line); background:#fff; color:var(--blue); font-size:16px; cursor:pointer; box-shadow:0 8px 24px -14px rgba(26,46,74,.5); }

        /* ===== Modo oscuro (opt-in) ===== */
        .dark body { background:#0E1526; color:#E6EBF3; }
        .dark .card { background:rgba(22,31,51,.97); border-color:rgba(44,58,86,.9); box-shadow:0 28px 70px -38px rgba(0,0,0,.6); }
        .dark h1 { color:#E6EBF3; }
        .dark .intro, .dark label { color:#9DB0C7; }
        .dark input { background:#0E1526; border-color:#2C3A56; color:#E6EBF3; }
        .dark input:focus { background:#131C30; border-color:#5AA9E0; box-shadow:0 0 0 4px rgba(90,169,224,.16); }
        .dark .toggle:hover { background:#1F2A42; color:#7FB1E6; }
        .dark .remember { color:#C7D2E1; }
        .dark .submit { background:#103A63; box-shadow:0 12px 24px -14px rgba(0,0,0,.55); }
        .dark .help { color:#9DB0C7; }
        .dark .theme-fab { background:#1F2A42; border-color:#2C3A56; color:#7FB1E6; }
        .dark .session-alert { background:#161F33; box-shadow:0 28px 70px -24px rgba(0,0,0,.7); }
        .dark .session-alert h2 { color:#E6EBF3; }
        .dark .session-alert p { color:#9DB0C7; }
        .dark .session-alert-icon { background:#33280F; color:#F0C98A; }
    </style>
    <link rel="stylesheet" href="{{ asset('css/president-portal.css') }}">
</head>
<body class="portal-login">
    <button type="button" class="theme-fab" aria-label="Cambiar tema" title="Cambiar entre tema claro y oscuro"
        onclick="var d=document.documentElement.classList.toggle('dark');try{localStorage.setItem('mde-theme',d?'dark':'light')}catch(e){}">
        <svg class="i-moon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg class="i-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
    </button>

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
                    <div class="input-wrap"><i class="fas fa-lock" aria-hidden="true"></i><input id="password" name="password" type="password" autocomplete="current-password" required aria-describedby="password-error"><button class="toggle" type="button" id="toggle-password" aria-label="Mostrar contraseña"><i class="fas fa-eye-slash" aria-hidden="true"></i></button></div>
                    @error('password')<div class="error" id="password-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <label class="remember" for="remember"><input id="remember" name="remember" type="checkbox"> Recordarme</label>
                <button class="submit" id="submit" type="submit"><i class="fas fa-right-to-bracket" aria-hidden="true"></i> Ingresar al portal</button>
            </form>
            <p class="help">Acceso exclusivo para presidentas registradas y vigentes.</p>
        </section>
    </main>
    @if(session('session_expired') || request()->get('expired') == 1)
        <div class="session-alert-backdrop" id="session-expired-alert">
            <section class="session-alert" role="alertdialog" aria-modal="true" aria-labelledby="session-expired-title" aria-describedby="session-expired-description">
                <div class="session-alert-icon" aria-hidden="true"><i class="fas fa-clock"></i></div>
                <h2 id="session-expired-title">Sesión finalizada</h2>
                <p id="session-expired-description">Tu sesión venció por inactividad. Inicia sesión nuevamente para continuar de forma segura.</p>
                <button type="button" id="session-expired-confirm">Iniciar sesión</button>
            </section>
        </div>
    @endif
    <script>
        const toggle = document.getElementById('toggle-password');
        const password = document.getElementById('password');
        toggle.addEventListener('click', () => {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            toggle.querySelector('i').className = visible ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
        document.getElementById('president-login-form').addEventListener('submit', () => {
            const button = document.getElementById('submit');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Ingresando...';
        });

        const sessionExpiredButton = document.getElementById('session-expired-confirm');
        if (sessionExpiredButton) {
            sessionExpiredButton.focus();
            sessionExpiredButton.addEventListener('click', () => {
                document.getElementById('session-expired-alert')?.remove();
                document.getElementById('username')?.focus();

                if (window.history.replaceState) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            });
        }
    </script>
</body>
</html>
