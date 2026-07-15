{{-- Modal Crear Persona --}}
<div id="modal-crear-persona" class="modal-overlay">
    <div class="modal-panel sm:max-w-2xl">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user-plus text-leaf"></i> Nueva Persona
                </h3>
                <button onclick="closeModal('modal-crear-persona')" class="modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('socios-beneficiarios.personas.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Nombres *</label>
                        <input type="text" name="names" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">DNI *</label>
                        <input type="text" name="dni" maxlength="8" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Apellido Paterno *</label>
                        <input type="text" name="father_lastname" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Apellido Materno *</label>
                        <input type="text" name="mother_lastname" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Nacimiento *</label>
                        <input type="date" name="birthdate" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Género *</label>
                        <select name="gender" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccionar...</option>
                            <option value="F">Femenino</option>
                            <option value="M">Masculino</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Dirección *</label>
                    <input type="text" name="address" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Celular</label>
                        <input type="text" name="phone_number" maxlength="9" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Sector *</label>
                        <select name="place_sector_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccionar...</option>
                            @foreach($placeSectors as $ps)
                            <option value="{{ $ps->id }}">{{ $ps->place->title ?? '' }} - {{ $ps->sector->title ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                    <button type="button" onclick="closeModal('modal-crear-persona')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
