<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>try{if(localStorage.getItem('mde-theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}</script>
    <title>Cambiar contraseña - Portal de Presidentas</title>
    <link rel="icon" href="{{ asset('img/logo-provale-sin-fondo.png') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/400.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/700.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/700.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <style>
        :root { --navy:#0B3A66; --blue:#175A91; --teal:#115E59; --sky:#F1F5F9; --line:#D6E1EC; --muted:#506E8D; --success:#166534; --danger:#B4232D; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:#F3F6F9; color:var(--navy); font:16px/1.5 'Source Sans 3',sans-serif; }
        .skip-link { position:fixed; left:12px; top:-60px; z-index:100; padding:10px 14px; border-radius:10px; background:var(--navy); color:#fff; font-weight:700; }
        .skip-link:focus { top:12px; }
        .topbar { position:sticky; top:0; z-index:10; background:rgba(255,255,255,.96); border-bottom:1px solid var(--line); backdrop-filter:blur(12px); }
        .topbar-inner,.shell { width:min(1180px,calc(100% - 32px)); margin:auto; }
        .topbar-inner { min-height:76px; display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .brand { display:flex; align-items:center; gap:12px; }
        .brand img { width:48px; height:48px; object-fit:contain; }
        .brand strong { display:block; font:700 17px 'Lexend',sans-serif; }
        .brand span { color:var(--teal); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; }
        .logout { min-width:112px; min-height:44px; border:1px solid rgba(255,150,155,.55); background:rgba(210,60,66,.26); color:#FFD9DB; border-radius:8px; font-weight:700; cursor:pointer; }
        .logout:hover { background:rgba(210,60,66,.4); border-color:rgba(255,170,174,.75); }
        button:focus-visible,a:focus-visible,input:focus-visible { outline:3px solid rgba(30,87,153,.28); outline-offset:2px; }
        .shell { min-height:100vh; display:grid; place-items:center; padding:30px 0 50px; }
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
        .submit { width:100%; min-height:48px; margin-top:22px; border:0; border-radius:6px; background:#0B3A66; color:#fff; font-size:15px; font-weight:700; cursor:pointer; box-shadow:0 12px 24px -14px rgba(11,58,102,.8); }
        .submit:hover { filter:brightness(1.05); }
        @media(max-width:480px) { .shell { padding:20px 0 40px; } .topbar-inner,.shell { width:min(100% - 20px,1180px); } .brand span { display:none; } .logout { min-width:48px; font-size:0; } .logout i { font-size:16px; } .card { padding:26px 20px; border-radius:20px; } }
        @media(prefers-reduced-motion:reduce) { * { transition:none!important; } }

        .theme-fab { width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--line); border-radius:9999px; background:#fff; color:var(--blue); font-size:16px; cursor:pointer; }

        /* ===== Modo oscuro (opt-in) ===== */
        .dark body { background:#0E1526; color:#E6EBF3; }
        .dark .topbar { background:rgba(14,21,38,.92); border-bottom-color:#2C3A56; }
        .dark .card { background:rgba(22,31,51,.97); border-color:rgba(44,58,86,.9); box-shadow:0 28px 70px -38px rgba(0,0,0,.6); }
        .dark h1 { color:#E6EBF3; }
        .dark .intro, .dark label, .dark .hint { color:#9DB0C7; }
        .dark input { background:#0E1526; border-color:#2C3A56; color:#E6EBF3; }
        .dark input:focus { background:#131C30; border-color:#5AA9E0; box-shadow:0 0 0 4px rgba(90,169,224,.16); }
        .dark .toggle:hover { background:#1F2A42; color:#7FB1E6; }
        .dark .submit { background:#103A63; box-shadow:0 12px 24px -14px rgba(0,0,0,.55); }
        .dark .theme-fab { background:#1F2A42; border-color:#2C3A56; color:#7FB1E6; }
        .dark .alert-success { background:#13311F; border-color:#2E6B45; color:#86D89A; }
        .dark .alert-danger { background:#331A1D; border-color:#6B3238; color:#F1A9AF; }
    </style>
    <link rel="stylesheet" href="{{ asset('css/president-portal.css') }}">
</head>
<body class="portal-password">
    <a class="skip-link" href="#main-content">Saltar al contenido</a>

    <div style="position:fixed;top:16px;right:16px;z-index:70;">
        <button type="button" class="theme-fab" aria-label="Cambiar tema" title="Cambiar entre tema claro y oscuro"
            onclick="var d=document.documentElement.classList.toggle('dark');try{localStorage.setItem('mde-theme',d?'dark':'light')}catch(e){}">
            <svg class="i-moon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            <svg class="i-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        </button>
    </div>

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
                    <div class="input-wrap"><i class="fas fa-lock" aria-hidden="true"></i><input id="current_password" name="current_password" type="password" autocomplete="current-password" required aria-describedby="current-password-error"><button class="toggle" type="button" data-toggle-for="current_password" aria-label="Mostrar contraseña"><i class="fas fa-eye-slash" aria-hidden="true"></i></button></div>
                    @error('current_password')<div class="error" id="current-password-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password">Nueva contraseña</label>
                    <div class="input-wrap"><i class="fas fa-key" aria-hidden="true"></i><input id="password" name="password" type="password" autocomplete="new-password" required aria-describedby="password-error"><button class="toggle" type="button" data-toggle-for="password" aria-label="Mostrar contraseña"><i class="fas fa-eye-slash" aria-hidden="true"></i></button></div>
                    <p class="hint">Mínimo 8 caracteres y diferente a tu DNI.</p>
                    @error('password')<div class="error" id="password-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmar nueva contraseña</label>
                    <div class="input-wrap"><i class="fas fa-key" aria-hidden="true"></i><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button class="toggle" type="button" data-toggle-for="password_confirmation" aria-label="Mostrar contraseña"><i class="fas fa-eye-slash" aria-hidden="true"></i></button></div>
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
                button.querySelector('i').className = visible ? 'fas fa-eye-slash' : 'fas fa-eye';
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
