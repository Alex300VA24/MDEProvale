{{-- Modal Crear Socio --}}
<div id="modal-crear-socio" class="modal-overlay">
    <div class="modal-panel sm:max-w-4xl">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user-plus text-leaf"></i> Nuevo Socio
                </h3>
                <button onclick="closeModal('modal-crear-socio')" class="modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('socios-beneficiarios.socios.store') }}" method="POST" class="p-4 sm:p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Persona *</label>
                    <select name="person_id" id="select-person-crear-socio" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Buscar persona por nombre o DNI...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Club *</label>
                    <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar club...</option>
                        @foreach($associations as $association)
                        <option value="{{ $association->id }}">{{ $association->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Inicio *</label>
                        <input type="date" name="date_begin" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Fin *</label>
                        <input type="date" name="date_end" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Estado *</label>
                    <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar...</option>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}">{{ $state->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Observaciones</label>
                    <textarea name="observations" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"></textarea>
                </div>

                <div class="border-t-2 border-wheat pt-4 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-bold text-charcoal flex items-center gap-2">
                            <i class="fas fa-users text-leaf"></i> Beneficiarios
                        </h4>
                        <button type="button" onclick="addBeneficiaryCreate()" class="btn-secondary text-xs">
                            <i class="fas fa-plus mr-1"></i> Agregar
                        </button>
                    </div>
                    <div id="beneficiaries-container-create" class="space-y-3">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1 text-xs sm:text-sm"><i class="fas fa-save mr-2"></i> Guardar</button>
                    <button type="button" onclick="closeModal('modal-crear-socio')" class="btn-secondary flex-1 text-xs sm:text-sm">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
