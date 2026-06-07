{{-- Modal Ver Persona --}}
<div id="modal-ver-persona-{{ $person->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-md mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user text-leaf"></i> Detalle de Persona
                </h3>
                <button onclick="closeModal('modal-ver-persona-{{ $person->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div><span class="text-[11px] font-bold text-earth uppercase">Nombre Completo</span>
                    <p class="font-semibold text-charcoal">{{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">DNI</span><p class="font-mono">{{ $person->dni }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Género</span><p>{{ $person->gender == 'F' ? 'Femenino' : 'Masculino' }}</p></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Edad</span><p class="font-bold text-leaf">{{ $person->age_formatted }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Celular</span><p>{{ $person->phone_number ?? 'Sin número' }}</p></div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Dirección</span><p>{{ $person->address ?? 'Sin dirección' }}</p></div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Sector</span>
                    <p>{{ $person->placeSector->place->title ?? 'N/A' }} - {{ $person->placeSector->sector->title ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="px-6 pb-6">
                <button onclick="closeModal('modal-ver-persona-{{ $person->id }}')" class="btn-secondary w-full">Cerrar</button>
            </div>
        </div>
    </div>
</div>
