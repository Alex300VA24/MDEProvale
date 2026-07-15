{{-- Modal Ver Persona — único, poblado por JS (openViewPersona) al hacer clic en una fila --}}
<div id="modal-ver-persona" class="modal-overlay">
    <div class="modal-panel sm:max-w-md">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user text-leaf"></i> Detalle de Persona
                </h3>
                <button onclick="closeModal('modal-ver-persona')" class="modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div><span class="text-[11px] font-bold text-earth uppercase">Nombre Completo</span>
                    <p class="font-semibold text-charcoal" id="ver-persona-nombre"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">DNI</span><p class="font-mono" id="ver-persona-dni"></p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Género</span><p id="ver-persona-genero"></p></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Edad</span><p class="font-bold text-leaf" id="ver-persona-edad"></p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Celular</span><p id="ver-persona-celular"></p></div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Dirección</span><p id="ver-persona-direccion"></p></div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Sector</span><p id="ver-persona-sector"></p></div>
            </div>
            <div class="px-6 pb-6">
                <button onclick="closeModal('modal-ver-persona')" class="btn-secondary w-full">Cerrar</button>
            </div>
        </div>
    </div>
</div>
