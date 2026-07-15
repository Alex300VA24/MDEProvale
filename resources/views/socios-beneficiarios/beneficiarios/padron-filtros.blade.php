@extends('layouts.main')

@section('title', 'Padrón de Beneficiarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Padrón de Beneficiarios del PVL
        </h3>
        <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <p class="text-earth mb-6">Seleccione los filtros para generar el padrón de beneficiarios:</p>

        <form id="form-padron-beneficiarios" action="{{ route('socios-beneficiarios.beneficiarios.padron') }}" method="GET" class="space-y-6" data-no-loading>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Comité --}}
                <div>
                    <label class="block text-sm font-bold text-charcoal mb-2">
                        <i class="fas fa-building mr-1"></i> Comité / Club de Madres
                    </label>
                    <select name="association_id" id="select-comite-padron" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccione un comité</option>
                        @foreach($associations as $association)
                        <option value="{{ $association->id }}" {{ request('association_id') == $association->id ? 'selected' : '' }}>
                            {{ $association->code }} - {{ $association->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Mes --}}
                <div>
                    <label class="block text-sm font-bold text-charcoal mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i> Mes
                    </label>
                    <select name="month" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @php
                        $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                        @endphp
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $mes == $i ? 'selected' : '' }}>
                            {{ $mesesNombres[$i] }}
                            </option>
                            @endfor
                    </select>
                </div>

                {{-- Año --}}
                <div>
                    <label class="block text-sm font-bold text-charcoal mb-2">
                        <i class="fas fa-calendar mr-1"></i> Año
                    </label>
                    <select name="year" class="w-full px-3 py-2 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @for($y = date('Y') - 10; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>
                            {{ $y }}
                            </option>
                            @endfor
                    </select>
                </div>
            </div>

            <div class="flex justify-center">
                <button type="submit" id="btn-generar-padron" class="btn-primary px-8 py-3 text-lg flex items-center gap-3">
                    <i class="fas fa-file-pdf"></i> Generar Padrón de Beneficiarios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#select-comite-padron').select2({ width: '100%', placeholder: 'Seleccione un comité', allowClear: false });
    }
});

document.getElementById('form-padron-beneficiarios').addEventListener('submit', function (e) {
    e.preventDefault();

    const form = e.target;
    const btn = document.getElementById('btn-generar-padron');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

    // La pestaña se abre ya (dentro del gesto de clic) para que el navegador no la
    // bloquee como popup; se navega a la URL real del PDF cuando el fetch resuelve.
    const previewTab = window.open('', '_blank');
    if (!previewTab) {
        Swal.fire({
            icon: 'warning',
            title: 'Ventanas emergentes bloqueadas',
            text: 'Habilita las ventanas emergentes para este sitio y vuelve a intentarlo.',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#1E5799',
        });
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        return;
    }

    const params = new URLSearchParams(new FormData(form));

    fetch(form.action + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
        .then(async (response) => {
            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'No se pudo generar el padrón para el periodo seleccionado.');
            }
            return response.blob();
        })
        .then((blob) => {
            previewTab.location = URL.createObjectURL(blob);
        })
        .catch((err) => {
            previewTab.close();
            Swal.fire({
                icon: 'info',
                title: 'Sin información disponible',
                text: err.message,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#1E5799',
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
});
</script>
@endsection