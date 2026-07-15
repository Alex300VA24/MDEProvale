{{--
    Pantalla de carga compartida. Antes existían 3 versiones distintas
    (main.blade.php, auth/login.blade.php, layouts/guest.blade.php) que
    divergían en detalles. Esta es la única fuente; los estilos viven en
    resources/css/app.css bajo #loading-screen / .loader-*.

    Uso:
    - En el shell autenticado (main.blade.php) se activa con la clase Alpine
      :class="{ 'active': loading }".
    - En páginas sueltas (login) se activa/desactiva agregando/quitando
      la clase "active" manualmente por JS.
--}}
@props(['subtitle' => 'Cargando sistema...'])
<div id="loading-screen" {{ $attributes }}>
    <div class="loader-container">
        <div class="loader-icon">
            <div class="loader-spin"></div>
            <div class="loader-ring"></div>
            <img src="{{ asset('img/muni2.png') }}" alt="PROVALE">
        </div>
        <div class="loader-text">
            <div class="loader-title">PROVALE</div>
            <div class="loader-subtitle">{{ $subtitle }}</div>
        </div>
        <div class="loader-progress">
            <div class="loader-progress-bar"></div>
        </div>
    </div>
</div>
