{{-- Modal Editar Socio --}}
<div id="modal-editar-socio-{{ $partner->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-4xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-sun"></i> Editar Socio
                </h3>
                <button onclick="closeModal('modal-editar-socio-{{ $partner->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('socios-beneficiarios.socios.update', $partner) }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Persona *</label>
                    <select name="person_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($allPeople as $person)
                        <option value="{{ $person->id }}" {{ $partner->person_id == $person->id ? 'selected' : '' }}>{{ $person->names }} {{ $person->father_lastname }} ({{ $person->dni }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Club *</label>
                    <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($associations as $association)
                        <option value="{{ $association->id }}" {{ $partner->association_id == $association->id ? 'selected' : '' }}>{{ $association->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Inicio *</label>
                        <input type="date" name="date_begin" value="{{ $partner->date_begin }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Fin *</label>
                        <input type="date" name="date_end" value="{{ $partner->date_end }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Estado *</label>
                    <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ $partner->state_id == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Observaciones</label>
                    <textarea name="observations" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">{{ $partner->observations }}</textarea>
                </div>

                <div class="border-t-2 border-wheat pt-4 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-bold text-charcoal flex items-center gap-2">
                            <i class="fas fa-users text-leaf"></i> Beneficiarios
                        </h4>
                        <button type="button" onclick="addBeneficiaryEdit({{ $partner->id }})" class="btn-secondary text-xs">
                            <i class="fas fa-plus mr-1"></i> Agregar
                        </button>
                    </div>
                    <div id="beneficiaries-container-edit-{{ $partner->id }}" class="space-y-3">
                        @foreach($partner->beneficiaries as $index => $beneficiary)
                        @php $latestHistory = $beneficiary->histories->first(); @endphp
                        <div class="p-3 bg-gray-50 rounded-lg border border-wheat" id="beneficiary-row-edit-{{ $partner->id }}-{{ $index }}">
                            <div class="flex items-center justify-between border-b border-wheat pb-2 mb-3">
                                <span class="text-xs font-bold text-leaf uppercase">Beneficiario #{{ $index + 1 }}</span>
                                <button type="button" onclick="removeBeneficiaryEdit({{ $partner->id }}, {{ $index }})" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Persona *</label>
                                    <select name="beneficiaries[{{ $index }}][person_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" required>
                                        @foreach($allPeople as $person)
                                        <option value="{{ $person->id }}" {{ $beneficiary->person_id == $person->id ? 'selected' : '' }}>{{ $person->names }} {{ $person->father_lastname }} ({{ $person->dni }})</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="beneficiaries[{{ $index }}][id]" value="{{ $beneficiary->id }}">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Parentesco *</label>
                                    <select name="beneficiaries[{{ $index }}][relationship_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" required>
                                        @foreach($relationships as $relationship)
                                        <option value="{{ $relationship->id }}" {{ $beneficiary->relationship_id == $relationship->id ? 'selected' : '' }}>{{ $relationship->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p class="text-[10px] font-bold text-earth uppercase mb-2 border-t border-wheat pt-2">Datos Clínicos <span class="font-normal normal-case text-gray-400">(opcional)</span></p>
                            <div class="grid grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Peso (kg)</label>
                                    <input type="number" step="0.01" min="0" name="beneficiaries[{{ $index }}][weight]" value="{{ $latestHistory->weight ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" placeholder="65.50">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Talla (cm)</label>
                                    <input type="number" step="0.01" min="0" name="beneficiaries[{{ $index }}][height]" value="{{ $latestHistory->height ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" placeholder="160.00">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">HMG (g/dL)</label>
                                    <input type="number" step="0.01" min="0" name="beneficiaries[{{ $index }}][hmg]" value="{{ $latestHistory->hmg ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white" placeholder="12.50">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">F. Inicio Beneficio</label>
                                    <input type="date" name="beneficiaries[{{ $index }}][date_begin]" value="{{ $latestHistory->date_begin ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">F. Fin Beneficio</label>
                                    <input type="date" name="beneficiaries[{{ $index }}][date_end]" value="{{ $latestHistory->date_end ?? '' }}" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Tipo de Beneficio</label>
                                    <select name="beneficiaries[{{ $index }}][type_benefit_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                        <option value="">Seleccionar...</option>
                                        @foreach($typeBenefits as $tb)
                                        <option value="{{ $tb->id }}" {{ ($latestHistory->type_benefit_id ?? null) == $tb->id ? 'selected' : '' }}>{{ $tb->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Estado</label>
                                    <select name="beneficiaries[{{ $index }}][history_state_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                        <option value="">Seleccionar...</option>
                                        @foreach($states as $st)
                                        <option value="{{ $st->id }}" {{ ($latestHistory->state_id ?? null) == $st->id ? 'selected' : '' }}>{{ $st->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-earth uppercase mb-1">Motivo Descalificación</label>
                                    <select name="beneficiaries[{{ $index }}][reason_disqualification_id]" class="w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white">
                                        <option value="">Ninguno</option>
                                        @foreach($reasonDisqualifications as $rd)
                                        <option value="{{ $rd->id }}" {{ ($latestHistory->reason_disqualification_id ?? null) == $rd->id ? 'selected' : '' }}>{{ $rd->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Actualizar</button>
                    <button type="button" onclick="closeModal('modal-editar-socio-{{ $partner->id }}')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
