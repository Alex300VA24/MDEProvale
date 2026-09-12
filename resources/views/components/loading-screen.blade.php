@props(['subtitle' => 'Cargando panel...'])

<div id="loading-screen" role="status" aria-live="polite" aria-label="Cargando">
    <div class="loader-container">
        <div class="loader-icon">
            <div class="loader-spin"></div>
            <div class="loader-ring"></div>
            <img
                src="{{ asset('img/muni2.png') }}"
                alt="PROVALE"
                decoding="sync"
                fetchpriority="high"
            >
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
