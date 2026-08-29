<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Verificación de documento - PROVALE</title>
    <link rel="icon" href="{{ asset('img/logo-provale-sin-fondo.png') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/400.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/700.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/700.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <style>
        :root { --navy:#1A2E4A; --blue:#1E5799; --teal:#0E8A7A; --sky:#EEF4FC; --line:#D4E4F7; --muted:#5A7FA8; --success:#15803D; --danger:#B91C1C; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; background:var(--sky); color:var(--navy); font-family:'Source Sans 3',sans-serif; }
        .skip-link { position:fixed; left:12px; top:-60px; z-index:100; padding:10px 14px; border-radius:10px; background:var(--navy); color:#fff; font-weight:700; }
        .skip-link:focus { top:12px; }
        h1,h2 { font-family:'Lexend','Source Sans 3',sans-serif; }
        .topbar { background:#fff; border-bottom:1px solid var(--line); }
        .topbar-inner,.container { width:min(1120px,calc(100% - 32px)); margin:auto; }
        .topbar-inner { min-height:76px; display:flex; align-items:center; gap:14px; }
        .brand-logo { width:48px; height:48px; object-fit:contain; }
        .brand strong { display:block; font:700 17px 'Lexend',sans-serif; }
        .brand span { color:var(--teal); font-size:12px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
        .container { padding:32px 0 48px; }
        .status-card { background:#fff; border:1px solid var(--line); border-radius:24px; padding:28px; box-shadow:0 18px 45px -30px rgba(15,46,82,.4); display:grid; grid-template-columns:auto 1fr; gap:20px; }
        .status-icon { width:64px; height:64px; border-radius:20px; display:grid; place-items:center; font-size:28px; }
        .valid .status-icon { color:var(--success); background:#ECFDF3; }
        .invalid .status-icon { color:var(--danger); background:#FEF2F2; }
        .eyebrow { margin:0 0 4px; color:var(--teal); text-transform:uppercase; letter-spacing:.1em; font-size:12px; font-weight:700; }
        h1 { margin:0; font-size:clamp(24px,4vw,34px); }
        .status-text { margin:8px 0 0; color:#476887; font-size:17px; }
        .grid { display:grid; grid-template-columns:minmax(0,360px) minmax(0,1fr); gap:24px; margin-top:24px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:20px; padding:24px; }
        h2 { margin:0 0 18px; font-size:18px; }
        dl { margin:0; }
        .meta-row { padding:12px 0; border-bottom:1px solid #E8F0F8; }
        .meta-row:last-child { border-bottom:0; }
        dt { color:var(--muted); font-size:12px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        dd { margin:4px 0 0; font-weight:700; overflow-wrap:anywhere; }
        .badge { display:inline-flex; align-items:center; gap:7px; padding:7px 11px; border-radius:999px; font-size:13px; }
        .badge-valid { color:var(--success); background:#ECFDF3; }
        .badge-invalid { color:var(--danger); background:#FEF2F2; }
        .actions { display:grid; gap:10px; margin-top:20px; }
        .button { min-height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; gap:9px; padding:10px 16px; text-decoration:none; font-weight:700; transition:filter .2s,box-shadow .2s; touch-action:manipulation; }
        .button-primary { background:linear-gradient(135deg,var(--blue),#2E6DB4); color:#fff; box-shadow:0 12px 24px -14px rgba(30,87,153,.65); }
        .button-secondary { background:#F4F8FC; border:1px solid var(--line); color:var(--blue); }
        .button:hover { filter:brightness(.97); }
        .button:focus-visible { outline:3px solid rgba(30,87,153,.28); outline-offset:2px; }
        .viewer { padding:0; overflow:hidden; min-height:660px; }
        .viewer-head { min-height:64px; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; border-bottom:1px solid var(--line); }
        .viewer h2 { margin:0; }
        iframe { width:100%; height:594px; border:0; background:#F8FAFC; }
        .security-note { margin:20px 0 0; color:var(--muted); font-size:14px; text-align:center; }
        @media (max-width:800px) { .grid { grid-template-columns:1fr; } .viewer { min-height:540px; } iframe { height:480px; } }
        @media (max-width:520px) { .status-card { grid-template-columns:1fr; padding:22px; } .container { padding-top:20px; } .card { padding:20px; } .viewer { padding:0; } .viewer-head { align-items:flex-start; flex-direction:column; } }
        @media (prefers-reduced-motion:reduce) { * { scroll-behavior:auto!important; transition:none!important; } }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Saltar al contenido</a>
    <header class="topbar">
        <div class="topbar-inner">
            <img class="brand-logo" src="{{ asset('img/logo-provale-sin-fondo.png') }}" width="48" height="48" alt="Logo PROVALE">
            <div class="brand"><strong>PROVALE</strong><span>Verificación documental</span></div>
        </div>
    </header>

    <main class="container" id="main-content">
        <section class="status-card {{ $document->isValid() ? 'valid' : 'invalid' }}" aria-labelledby="verification-title">
            <div class="status-icon" aria-hidden="true"><i class="fas {{ $document->isValid() ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i></div>
            <div>
                <p class="eyebrow">Resultado de verificación</p>
                <h1 id="verification-title">{{ $document->isValid() ? 'Documento auténtico y vigente' : 'Documento revocado' }}</h1>
                <p class="status-text">Registro emitido por Municipalidad Distrital de La Esperanza mediante sistema PROVALE.</p>
            </div>
        </section>

        <div class="grid">
            <section class="card" aria-labelledby="metadata-title">
                <h2 id="metadata-title">Datos del documento</h2>
                <dl>
                    <div class="meta-row"><dt>Tipo de padrón</dt><dd>{{ $document->type_label }}</dd></div>
                    <div class="meta-row"><dt>Fecha de emisión</dt><dd>{{ $document->issued_at->format('d/m/Y H:i') }}</dd></div>
                    <div class="meta-row"><dt>Código identificador</dt><dd>{{ $document->identifier }}</dd></div>
                    <div class="meta-row"><dt>Estado</dt><dd><span class="badge {{ $document->isValid() ? 'badge-valid' : 'badge-invalid' }}"><i class="fas fa-circle" aria-hidden="true"></i>{{ ucfirst($document->status) }}</span></dd></div>
                    @if(!empty($document->metadata['periodo']))
                        <div class="meta-row"><dt>Período</dt><dd>{{ $document->metadata['periodo'] }}</dd></div>
                    @endif
                    <div class="meta-row"><dt>Huella SHA-256</dt><dd>{{ $document->sha256 }}</dd></div>
                </dl>
                <div class="actions">
                    <a class="button button-primary" href="{{ route('documents.pdf', $document->token) }}" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square" aria-hidden="true"></i>Abrir PDF</a>
                    <a class="button button-secondary" href="{{ route('documents.pdf', ['token' => $document->token, 'descargar' => 1]) }}"><i class="fas fa-download" aria-hidden="true"></i>Descargar original</a>
                </div>
            </section>

            <section class="card viewer" aria-labelledby="viewer-title">
                <div class="viewer-head"><h2 id="viewer-title">Vista previa del original</h2><span class="badge {{ $document->isValid() ? 'badge-valid' : 'badge-invalid' }}"><i class="fas fa-shield-halved" aria-hidden="true"></i>Original registrado</span></div>
                <iframe loading="lazy" src="{{ route('documents.pdf', $document->token) }}" title="Vista previa del documento PDF original"></iframe>
            </section>
        </div>
        <p class="security-note"><i class="fas fa-lock" aria-hidden="true"></i> URL única con token aleatorio de 256 bits. Documento almacenado con huella SHA-256.</p>
    </main>
</body>
</html>
