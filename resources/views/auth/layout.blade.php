<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>@yield('title') - PROVALE</title>
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/400.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/700.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/700.css') }}">
    <style>
        :root {
            --navy: #0B3A66;
            --navy-deep: #072A4D;
            --blue: #175A91;
            --blue-soft: #E1EDF7;
            --canvas: #EEF4FC;
            --surface: #FFFFFF;
            --text: #1A2E4A;
            --muted: #506E8D;
            --border: #D6E1EC;
            --danger: #B4232D;
            --danger-soft: #FEE2E2;
            --focus: #FBBF24;
        }

        * { box-sizing: border-box; }
        ::selection { background: var(--blue-soft); color: var(--navy-deep); }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            color: var(--text);
            background: var(--canvas);
            font-family: 'Source Sans 3', ui-sans-serif, system-ui, sans-serif;
        }

        .auth-shell {
            width: min(100%, 440px);
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: var(--surface);
            box-shadow: 0 18px 50px rgba(7, 42, 77, .14);
        }

        .auth-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 24px;
            color: white;
            background: var(--navy);
        }

        .auth-mark {
            width: 52px;
            height: 52px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: white;
        }

        .auth-mark img { width: 42px; height: 42px; object-fit: contain; }
        .auth-brand { margin: 0; font: 700 1.15rem/1.2 'Lexend', sans-serif; }
        .auth-program { margin: 4px 0 0; color: #C9DDF0; font-size: .875rem; }
        .auth-content { padding: 28px 24px 24px; }

        h1 {
            margin: 0 0 10px;
            color: var(--navy-deep);
            font: 700 1.45rem/1.25 'Lexend', sans-serif;
            letter-spacing: -.02em;
        }

        .auth-intro { max-width: 65ch; margin: 0 0 24px; color: var(--muted); line-height: 1.55; }
        .field { margin-bottom: 18px; }
        label { display: block; margin-bottom: 7px; color: var(--navy-deep); font-size: .78rem; font-weight: 700; }

        input {
            width: 100%;
            min-height: 46px;
            padding: 10px 12px;
            border: 2px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            background: var(--surface);
            font: 600 .95rem/1.35 'Source Sans 3', sans-serif;
            caret-color: var(--blue);
        }

        input:focus { outline: 3px solid rgba(251, 191, 36, .4); border-color: var(--blue); }
        input::placeholder { color: var(--muted); }

        .button {
            width: 100%;
            min-height: 46px;
            border: 0;
            border-radius: 6px;
            color: white;
            background: var(--navy);
            font: 700 .95rem/1.2 'Source Sans 3', sans-serif;
            cursor: pointer;
            transition: background-color .18s ease-out, transform .18s ease-out, box-shadow .18s ease-out;
        }

        .button:hover { background: var(--blue); box-shadow: 0 7px 18px rgba(11, 58, 102, .2); transform: translateY(-1px); }
        .button:focus-visible, .link-button:focus-visible, a:focus-visible { outline: 3px solid var(--focus); outline-offset: 3px; }
        .button:disabled { cursor: not-allowed; opacity: .55; transform: none; box-shadow: none; }

        .link-button {
            padding: 0;
            border: 0;
            color: var(--blue);
            background: transparent;
            font: 700 .9rem/1.4 'Source Sans 3', sans-serif;
            text-decoration: underline;
            text-underline-offset: 3px;
            cursor: pointer;
        }

        .actions { display: grid; gap: 14px; }
        .secondary-action { text-align: center; }
        .error-list { margin: 0 0 20px; padding: 12px 14px 12px 32px; border-radius: 12px; color: var(--danger); background: var(--danger-soft); }
        .status { margin: 0 0 20px; padding: 12px 14px; border-radius: 12px; color: #115E59; background: #CCFBF1; }
        .auth-footer { margin: 0; padding: 0 24px 24px; color: var(--muted); font-size: .78rem; text-align: center; }

        @media (max-width: 520px) {
            body { place-items: start center; padding: 16px; }
            .auth-header, .auth-content { padding-left: 20px; padding-right: 20px; }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <header class="auth-header">
            <span class="auth-mark" aria-hidden="true">
                <img src="{{ asset('img/muni2.png') }}" alt="">
            </span>
            <div>
                <p class="auth-brand">PROVALE</p>
                <p class="auth-program">Programa del Vaso de Leche</p>
            </div>
        </header>

        <section class="auth-content">
            @if (session('status'))
                <p class="status" role="status">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <ul class="error-list" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            @yield('content')
        </section>

        <p class="auth-footer">Acceso seguro para personal autorizado de la Municipalidad Distrital de La Esperanza.</p>
    </main>
</body>
</html>
