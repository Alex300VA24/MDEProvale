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
        :root { --navy:#1A2E4A; --blue:#1E5799; --teal:#0E8A7A; --sky:#EEF4FC; --line:#D4E4F7; --muted:#5A7FA8; --success:#15803D; --warning:#B45309; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--sky); color:var(--navy); font:16px/1.5 'Source Sans 3',sans-serif; }
        .skip-link { position:fixed; left:12px; top:-60px; z-index:100; padding:10px 14px; border-radius:10px; background:var(--navy); color:#fff; font-weight:700; }
        .skip-link:focus { top:12px; }
        h1,h2,h3 { font-family:'Lexend','Source Sans 3',sans-serif; }
        .topbar { position:sticky; top:0; z-index:10; background:rgba(255,255,255,.96); border-bottom:1px solid var(--line); backdrop-filter:blur(12px); }
        .topbar-inner,.container { width:min(1180px,calc(100% - 32px)); margin:auto; }
        .topbar-inner { min-height:76px; display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .brand { display:flex; align-items:center; gap:12px; }
        .brand img { width:48px; height:48px; object-fit:contain; }
        .brand strong { display:block; font:700 17px 'Lexend',sans-serif; }
        .brand span { color:var(--teal); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; }
        .logout { min-width:112px; min-height:44px; border:1px solid var(--line); background:#fff; color:var(--blue); border-radius:13px; font-weight:700; cursor:pointer; touch-action:manipulation; }
        .logout:hover { background:#F4F8FC; }
        button:focus-visible,a:focus-visible,select:focus-visible { outline:3px solid rgba(30,87,153,.28); outline-offset:2px; }
        .container { padding:30px 0 50px; }
        .hero { display:flex; justify-content:space-between; align-items:end; gap:24px; margin-bottom:24px; }
        .eyebrow { margin:0 0 4px; color:var(--teal); font-size:12px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
        h1 { margin:0; font-size:clamp(25px,4vw,36px); line-height:1.2; }
        .subtitle { color:var(--muted); margin:8px 0 0; }
        .period-form { display:flex; gap:10px; align-items:end; padding:12px; border:1px solid var(--line); border-radius:16px; background:#fff; }
        label { display:block; color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
        select { min-height:44px; margin-top:4px; border:1px solid var(--line); border-radius:11px; background:#fff; color:var(--navy); padding:0 34px 0 12px; font-weight:700; }
        .filter-button { min-height:44px; padding:0 18px; border:0; border-radius:11px; background:var(--blue); color:#fff; font-weight:700; cursor:pointer; touch-action:manipulation; }
        .notice { padding:22px; border:1px solid #FCD34D; border-radius:18px; background:#FFFBEB; color:#92400E; }
        .committee-card { padding:22px; border-radius:22px; background:linear-gradient(135deg,#173B67,#1E5799); color:#fff; display:grid; grid-template-columns:1fr auto; gap:20px; box-shadow:0 20px 45px -30px #102A49; }
        .committee-card h2 { margin:3px 0 5px; font-size:22px; }
        .committee-meta { opacity:.82; margin:0; }
        .committee-code { align-self:center; border:1px solid rgba(255,255,255,.32); border-radius:14px; padding:10px 14px; text-align:center; }
        .committee-code span { display:block; opacity:.7; font-size:11px; text-transform:uppercase; }
        .committee-code strong { font:700 18px 'Lexend',sans-serif; }
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-top:18px; }
        .stat { background:#fff; border:1px solid var(--line); border-radius:18px; padding:18px; min-height:126px; }
        .stat-icon { width:38px; height:38px; display:grid; place-items:center; border-radius:12px; background:#E8F1FB; color:var(--blue); margin-bottom:11px; }
        .stat span { display:block; color:var(--muted); font-size:13px; font-weight:600; }
        .stat strong { display:block; margin-top:2px; font:700 21px 'Lexend',sans-serif; }
        .section { margin-top:24px; background:#fff; border:1px solid var(--line); border-radius:22px; overflow:hidden; }
        .section-head { padding:20px 22px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; gap:16px; }
        .section-head h2 { margin:0; font-size:19px; }
        .section-head p { margin:3px 0 0; color:var(--muted); font-size:14px; }
        .status { display:inline-flex; align-items:center; gap:7px; border-radius:999px; padding:7px 11px; font-size:13px; font-weight:700; }
        .status-ready { background:#ECFDF3; color:var(--success); }
        .status-pending { background:#FFF7ED; color:var(--warning); }
        .schedule { display:grid; grid-template-columns:repeat(3,1fr); gap:1px; background:var(--line); }
        .schedule-item { background:#fff; padding:20px 22px; }
        .schedule-item span { display:block; color:var(--muted); font-size:13px; }
        .schedule-item strong { display:block; margin-top:4px; font-size:18px; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; min-width:720px; border-collapse:collapse; }
        th { background:#F5F8FC; color:var(--muted); font-size:12px; letter-spacing:.04em; text-transform:uppercase; text-align:left; }
        th,td { padding:14px 20px; border-bottom:1px solid #E8F0F8; }
        tbody tr:last-child td { border-bottom:0; }
        .empty { padding:36px 22px; text-align:center; color:var(--muted); }
        .pagination { padding:16px 20px; border-top:1px solid var(--line); }
        .pagination nav > div:first-child { display:none; }
        @media(max-width:850px) { .hero { align-items:stretch; flex-direction:column; } .stats { grid-template-columns:repeat(2,1fr); } .schedule { grid-template-columns:1fr; } }
        @media(max-width:520px) { .topbar-inner,.container { width:min(100% - 20px,1180px); } .brand span { display:none; } .logout { min-width:48px; font-size:0; } .logout i { font-size:16px; } .period-form { width:100%; display:grid; grid-template-columns:1fr 1fr; } .filter-button { grid-column:1/-1; } .committee-card { grid-template-columns:1fr; } .committee-code { justify-self:start; } .stats { grid-template-columns:1fr; } .section-head { align-items:flex-start; flex-direction:column; } }
        @media(prefers-reduced-motion:reduce) { * { transition:none!important; scroll-behavior:auto!important; } }
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

    <main class="container" id="main-content">
        @if(session('success'))
            <div style="padding:16px 20px;margin-bottom:20px;border:1px solid #BBF7D0;border-radius:16px;background:#F0FDF4;color:var(--success);font-weight:600;" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i> {{ session('success') }}</div>
        @endif
        <div class="hero">
            <div><p class="eyebrow">Consulta privada</p><h1>Hola, {{ auth()->user()->names }}</h1><p class="subtitle">Revisa programación y Pecosas de comité asignado.</p></div>
            <form class="period-form" method="GET" action="{{ route('president-portal.index') }}">
                <div><label for="month">Mes</label><select id="month" name="month">@foreach(range(1,12) as $value)<option value="{{ $value }}" @selected($month === $value)>{{ ucfirst(Carbon\Carbon::create()->month($value)->locale('es')->monthName) }}</option>@endforeach</select></div>
                <div><label for="year">Año</label><select id="year" name="year">@foreach(range(now()->year + 1, now()->year - 5) as $value)<option value="{{ $value }}" @selected($year === $value)>{{ $value }}</option>@endforeach</select></div>
                <button class="filter-button" type="submit"><i class="fas fa-filter" aria-hidden="true"></i> Consultar</button>
            </form>
        </div>

        @if(!$association)
            <div class="notice" role="alert"><strong>No existe comité vigente asignado.</strong><br>Solicita actualización de DNI, cargo o período de directiva al administrador.</div>
        @else
            <section class="committee-card" aria-labelledby="committee-title">
                <div><p class="eyebrow" style="color:#A7F3D0">Comité asignado</p><h2 id="committee-title">{{ $association->name }}</h2><p class="committee-meta"><i class="fas fa-location-dot" aria-hidden="true"></i> {{ $association->address ?: 'Dirección no registrada' }}{{ $association->placeSector?->sector?->title ? ' · '.$association->placeSector->sector->title : '' }}</p></div>
                <div class="committee-code"><span>Código</span><strong>{{ $association->code }}</strong></div>
            </section>

            <div class="stats" aria-label="Resumen de repartición">
                <div class="stat"><div class="stat-icon"><i class="fas fa-users" aria-hidden="true"></i></div><span>Beneficiarios</span><strong>{{ $allocation['beneficiarios'] ?? '—' }}</strong></div>
                <div class="stat"><div class="stat-icon"><i class="fas fa-box" aria-hidden="true"></i></div><span>Leche asignada</span><strong>{{ isset($allocation) ? round($allocation['leche_litros']).' tarros' : '—' }}</strong></div>
                <div class="stat"><div class="stat-icon"><i class="fas fa-wheat-awn" aria-hidden="true"></i></div><span>Hojuelas asignadas</span><strong>{{ isset($allocation) ? round($allocation['hojuelas_kg']).' kg' : '—' }}</strong></div>
                <div class="stat"><div class="stat-icon"><i class="fas fa-file-lines" aria-hidden="true"></i></div><span>Pecosas registradas</span><strong>{{ $history?->total() ?? 0 }}</strong></div>
            </div>

            <section class="section" aria-labelledby="schedule-title">
                <div class="section-head"><div><h2 id="schedule-title">Cronograma de repartición</h2><p>{{ ucfirst($periodStart->locale('es')->monthName) }} de {{ $year }}</p></div><span class="status {{ $scheduledPecosa ? 'status-ready' : 'status-pending' }}"><i class="fas {{ $scheduledPecosa ? 'fa-circle-check' : 'fa-clock' }}" aria-hidden="true"></i>{{ $scheduledPecosa ? 'Programada' : 'Pendiente de programación' }}</span></div>
                <div class="schedule">
                    <div class="schedule-item"><span>Fecha programada</span><strong>{{ $scheduledPecosa?->delivery_date?->format('d/m/Y') ?? 'Por confirmar' }}</strong></div>
                    <div class="schedule-item"><span>Número de Pecosa</span><strong>{{ $scheduledPecosa?->pecosa_number ?? 'No emitida' }}</strong></div>
                    <div class="schedule-item"><span>Estado</span><strong>{{ $scheduledPecosa?->state?->title ?? 'Sin registro' }}</strong></div>
                </div>
            </section>

            <section class="section" aria-labelledby="history-title">
                <div class="section-head"><div><h2 id="history-title">Historial de Pecosas</h2><p>Documentos correspondientes a gestión de comité.</p></div></div>
                @if($history && $history->count())
                    <div class="table-wrap"><table><thead><tr><th>Número</th><th>Fecha de entrega</th><th>Productos</th><th>Cantidad</th><th>Estado</th></tr></thead><tbody>
                    @foreach($history as $pecosa)<tr><td><strong>{{ $pecosa->pecosa_number }}</strong></td><td>{{ $pecosa->delivery_date?->format('d/m/Y') }}</td><td>{{ $pecosa->detailPecosas->count() }}</td><td>{{ number_format($pecosa->detailPecosas->sum('quantity')) }}</td><td><span class="status status-ready">{{ $pecosa->state?->title ?? 'Registrada' }}</span></td></tr>@endforeach
                    </tbody></table></div><div class="pagination">{{ $history->links() }}</div>
                @else<div class="empty"><i class="fas fa-folder-open" aria-hidden="true"></i><br>No hay Pecosas registradas para comité.</div>@endif
            </section>
        @endif
    </main>
</body>
</html>
