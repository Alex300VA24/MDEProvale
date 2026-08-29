<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Presidentas - PROVALE</title>
    <link rel="icon" href="{{ asset('img/logo-provale-sin-fondo.png') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/400.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/source-sans-3/700.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/600.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/lexend/700.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <style>
        :root { --navy:#0B3A66; --blue:#175A91; --teal:#115E59; --sky:#F1F5F9; --line:#D6E1EC; --muted:#506E8D; --success:#166534; --warning:#92400E; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--sky); color:var(--navy); font:16px/1.5 'Source Sans 3',sans-serif; }
        .skip-link { position:fixed; left:12px; top:-60px; z-index:100; padding:10px 14px; border-radius:8px; background:var(--navy); color:#fff; font-weight:700; }
        .skip-link:focus { top:12px; }
        h1,h2,h3 { font-family:'Lexend','Source Sans 3',sans-serif; }
        .topbar { position:sticky; top:0; z-index:10; background:rgba(255,255,255,.96); border-bottom:1px solid var(--line); backdrop-filter:blur(12px); }
        .topbar-inner,.container { width:min(1180px,calc(100% - 32px)); margin:auto; }
        .topbar-inner { min-height:72px; display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .brand { display:flex; align-items:center; gap:12px; }
        .brand img { width:44px; height:44px; object-fit:contain; }
        .brand strong { display:block; font:700 16px 'Lexend',sans-serif; }
        .brand span { color:var(--teal); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; }
        .logout { min-width:112px; min-height:42px; border:1px solid rgba(255,150,155,.55); background:rgba(210,60,66,.26); color:#FFD9DB; border-radius:8px; font-weight:700; cursor:pointer; touch-action:manipulation; }
        .logout:hover { background:rgba(210,60,66,.4); border-color:rgba(255,170,174,.75); }
        button:focus-visible,a:focus-visible,select:focus-visible { outline:3px solid rgba(30,87,153,.28); outline-offset:2px; }
        .container { padding:24px 0 48px; }

        /* Cabecera compacta: saludo + selector de período en una sola banda. */
        .pagehead { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:20px; }
        .eyebrow { margin:0 0 4px; color:var(--teal); font-size:12px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
        h1 { margin:0; font-size:clamp(22px,3vw,30px); line-height:1.2; }
        .subtitle { color:var(--muted); margin:6px 0 0; }
        .period-form { display:flex; gap:10px; align-items:end; padding:12px; border:1px solid var(--line); border-radius:10px; background:#fff; }
        label { display:block; color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
        select { min-height:42px; margin-top:4px; border:1px solid var(--line); border-radius:6px; background:#fff; color:var(--navy); padding:0 34px 0 12px; font-weight:700; }
        .filter-button { min-height:42px; padding:0 18px; border:0; border-radius:6px; background:var(--navy); color:#fff; font-weight:700; cursor:pointer; touch-action:manipulation; }

        .notice { padding:20px 22px; border:1px solid #FCD34D; border-radius:10px; background:#FFFBEB; color:#92400E; }

        /* Fila superior de datos clave: identidad del comité + próxima repartición. */
        .overview { display:grid; grid-template-columns:minmax(0,1.4fr) minmax(0,1fr); gap:16px; }
        .committee-card { padding:22px; border-radius:10px; background:var(--navy); color:#fff; display:grid; grid-template-columns:1fr auto; gap:20px; box-shadow:0 18px 40px -30px #102A49; }
        .committee-card .eyebrow { color:#A7F3D0; }
        .committee-card h2 { margin:3px 0 6px; font-size:21px; line-height:1.25; }
        .committee-meta { opacity:.82; margin:0; font-size:14px; }
        .committee-code { align-self:center; border:1px solid rgba(255,255,255,.32); border-radius:10px; padding:10px 14px; text-align:center; }
        .committee-code span { display:block; opacity:.7; font-size:11px; text-transform:uppercase; }
        .committee-code strong { font:700 18px 'Lexend',sans-serif; }

        .next-delivery { padding:20px 22px; border:1px solid var(--line); border-left:4px solid var(--navy); border-radius:10px; background:#fff; display:flex; flex-direction:column; gap:8px; }
        .next-delivery.is-ready { border-left-color:var(--success); }
        .next-delivery.is-pending { border-left-color:var(--warning); }
        .next-delivery .eyebrow { margin:0; color:var(--blue); }
        .next-delivery-date { font:700 24px 'Lexend',sans-serif; color:var(--navy); }
        .next-delivery-meta { display:grid; grid-template-columns:1fr 1fr; gap:10px 16px; margin:6px 0 0; }
        .next-delivery-meta dt { color:var(--muted); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
        .next-delivery-meta dd { margin:2px 0 0; font-weight:700; font-size:15px; }
        .status { display:inline-flex; align-items:center; gap:7px; border-radius:999px; padding:6px 11px; font-size:13px; font-weight:700; width:fit-content; }
        .status-ready { background:#ECFDF3; color:var(--success); }
        .status-pending { background:#FFF7ED; color:var(--warning); }

        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-top:18px; }
        .stat { background:#fff; border:1px solid var(--line); border-radius:10px; padding:16px; }
        .stat-icon { width:36px; height:36px; display:grid; place-items:center; border-radius:8px; background:#E8F1FB; color:var(--blue); margin-bottom:10px; }
        .stat span { display:block; color:var(--muted); font-size:13px; font-weight:600; }
        .stat strong { display:block; margin-top:2px; font:700 20px 'Lexend',sans-serif; }

        .section { margin-top:20px; background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden; }
        .section-head { padding:18px 22px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; gap:16px; }
        .section-head h2 { margin:0; font-size:18px; }
        .section-head p { margin:3px 0 0; color:var(--muted); font-size:14px; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; min-width:720px; border-collapse:collapse; }
        th { background:#F5F8FC; color:var(--muted); font-size:12px; letter-spacing:.04em; text-transform:uppercase; text-align:left; }
        th,td { padding:13px 20px; border-bottom:1px solid #E8F0F8; }
        tbody tr:last-child td { border-bottom:0; }
        .empty { padding:34px 22px; text-align:center; color:var(--muted); }
        .pagination { padding:14px 20px; border-top:1px solid var(--line); }
        .pagination nav > div:first-child { display:none; }

        @media(max-width:900px) {
            .overview { grid-template-columns:1fr; }
            .stats { grid-template-columns:repeat(2,1fr); }
        }
        @media(max-width:520px) {
            .topbar-inner,.container { width:min(100% - 20px,1180px); }
            .brand span { display:none; }
            .logout { min-width:44px; font-size:0; }
            .logout i { font-size:16px; }
            .pagehead { align-items:stretch; }
            .period-form { width:100%; display:grid; grid-template-columns:1fr 1fr; }
            .filter-button { grid-column:1/-1; }
            .committee-card { grid-template-columns:1fr; }
            .committee-code { justify-self:start; }
            .stats { grid-template-columns:1fr; }
            .next-delivery-meta { grid-template-columns:1fr; }
            .section-head { align-items:flex-start; flex-direction:column; }
        }
        @media(prefers-reduced-motion:reduce) { * { transition:none!important; scroll-behavior:auto!important; } }
    </style>
    <link rel="stylesheet" href="{{ asset('css/president-portal.css') }}">
</head>
<body class="portal-dashboard">
    <a class="skip-link" href="#main-content">Saltar al contenido</a>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand"><img src="{{ asset('img/logo-provale-sin-fondo.png') }}" width="44" height="44" alt="Logo PROVALE"><div><strong>Municipalidad Distrital de La Esperanza</strong><span>PROVALE · Portal de Presidentas</span></div></div>
            <form method="POST" action="{{ route('president.logout') }}">@csrf<button class="logout" type="submit"><i class="fas fa-right-from-bracket" aria-hidden="true"></i> Cerrar sesión</button></form>
        </div>
    </header>

    <main class="container" id="main-content">
        @if(session('success'))
            <div style="padding:14px 18px;margin-bottom:18px;border:1px solid #BBF7D0;border-radius:10px;background:#F0FDF4;color:var(--success);font-weight:600;" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i> {{ session('success') }}</div>
        @endif

        <div class="pagehead">
            <div>
                <p class="eyebrow">Consulta privada</p>
                <h1>Hola, {{ auth()->user()->names }}</h1>
                <p class="subtitle">Programación y Pecosas del comité asignado.</p>
            </div>
            <form class="period-form" method="GET" action="{{ route('president-portal.index') }}">
                <div><label for="month">Mes</label><select id="month" name="month">@foreach(range(1,12) as $value)<option value="{{ $value }}" @selected($month === $value)>{{ ucfirst(Carbon\Carbon::create()->month($value)->locale('es')->monthName) }}</option>@endforeach</select></div>
                <div><label for="year">Año</label><select id="year" name="year">@foreach(range(now()->year + 1, now()->year - 5) as $value)<option value="{{ $value }}" @selected($year === $value)>{{ $value }}</option>@endforeach</select></div>
                <button class="filter-button" type="submit"><i class="fas fa-filter" aria-hidden="true"></i> Consultar</button>
            </form>
        </div>

        @if(!$association)
            <div class="notice" role="alert"><strong>No existe comité vigente asignado.</strong><br>Solicita actualización de DNI, cargo o período de directiva al administrador.</div>
        @else
            <div class="overview">
                <section class="committee-card" aria-labelledby="committee-title">
                    <div>
                        <p class="eyebrow">Comité asignado</p>
                        <h2 id="committee-title">{{ $association->name }}</h2>
                        <p class="committee-meta"><i class="fas fa-location-dot" aria-hidden="true"></i> {{ $association->address ?: 'Dirección no registrada' }}{{ $association->placeSector?->sector?->title ? ' · '.$association->placeSector->sector->title : '' }}</p>
                    </div>
                    <div class="committee-code"><span>Código</span><strong>{{ $association->code }}</strong></div>
                </section>

                <section class="next-delivery {{ $scheduledPecosa ? 'is-ready' : 'is-pending' }}" aria-labelledby="next-delivery-title">
                    <p class="eyebrow" id="next-delivery-title">Próxima repartición · {{ ucfirst($periodStart->locale('es')->monthName) }} {{ $year }}</p>
                    <strong class="next-delivery-date">{{ $scheduledPecosa?->delivery_date?->format('d/m/Y') ?? 'Por confirmar' }}</strong>
                    <span class="status {{ $scheduledPecosa ? 'status-ready' : 'status-pending' }}"><i class="fas {{ $scheduledPecosa ? 'fa-circle-check' : 'fa-clock' }}" aria-hidden="true"></i>{{ $scheduledPecosa ? 'Programada' : 'Pendiente de programación' }}</span>
                    <dl class="next-delivery-meta">
                        <div><dt>N.º de Pecosa</dt><dd>{{ $scheduledPecosa?->pecosa_number ?? 'No emitida' }}</dd></div>
                        <div><dt>Estado</dt><dd>{{ $scheduledPecosa?->state?->title ?? 'Sin registro' }}</dd></div>
                    </dl>
                </section>
            </div>

            <div class="stats" aria-label="Resumen de repartición">
                <div class="stat"><div class="stat-icon"><i class="fas fa-users" aria-hidden="true"></i></div><span>Beneficiarios</span><strong>{{ $allocation['beneficiarios'] ?? '—' }}</strong></div>
                <div class="stat"><div class="stat-icon"><i class="fas fa-box" aria-hidden="true"></i></div><span>Leche asignada</span><strong>{{ isset($allocation) ? round($allocation['leche_litros']).' tarros' : '—' }}</strong></div>
                <div class="stat"><div class="stat-icon"><i class="fas fa-wheat-awn" aria-hidden="true"></i></div><span>Hojuelas asignadas</span><strong>{{ isset($allocation) ? round($allocation['hojuelas_kg']).' kg' : '—' }}</strong></div>
                <div class="stat"><div class="stat-icon"><i class="fas fa-file-lines" aria-hidden="true"></i></div><span>Pecosas registradas</span><strong>{{ $history?->total() ?? 0 }}</strong></div>
            </div>

            <section class="section" aria-labelledby="history-title">
                <div class="section-head"><div><h2 id="history-title">Historial de Pecosas</h2><p>Documentos del comité · {{ $history?->total() ?? 0 }} en total</p></div></div>
                @if($history && $history->count())
                    <div class="table-wrap"><table><thead><tr><th>Número</th><th>Fecha de entrega</th><th>Productos</th><th>Cantidad</th><th>Estado</th></tr></thead><tbody>
                    @foreach($history as $pecosa)<tr><td><strong>{{ $pecosa->pecosa_number }}</strong></td><td>{{ $pecosa->delivery_date?->format('d/m/Y') }}</td><td>{{ $pecosa->detailPecosas->count() }}</td><td>{{ number_format($pecosa->detailPecosas->sum('quantity')) }}</td><td><span class="status status-ready">{{ $pecosa->state?->title ?? 'Registrada' }}</span></td></tr>@endforeach
                    </tbody></table></div><div class="pagination">{{ $history->links() }}</div>
                @else<div class="empty"><i class="fas fa-folder-open" aria-hidden="true"></i><br>No hay Pecosas registradas para el comité.</div>@endif
            </section>
        @endif
    </main>
</body>
</html>
