{{-- Modal Ver Socio --}}
<div id="modal-ver-socio-{{ $partner->id }}" class="modal-overlay">
    <div class="modal-panel sm:max-w-4xl">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user text-leaf"></i> Detalle del Socio
                </h3>
                <button onclick="closeModal('modal-ver-socio-{{ $partner->id }}')" class="modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4 sm:p-6 space-y-3 text-sm">
                <div><span class="text-[11px] font-bold text-earth uppercase">Nombre</span>
                    <p class="font-semibold text-charcoal">{{ $partner->people ? $partner->people->names . ' ' . $partner->people->father_lastname . ' ' . $partner->people->mother_lastname : 'Sin nombre' }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">DNI</span><p>{{ $partner->people->dni ?? '-' }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Estado</span>
                        <p><span class="px-2 py-1 text-[10px] font-bold rounded-full {{ $partner->state && $partner->state->title == 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $partner->state->title ?? 'N/A' }}</span></p>
                    </div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Club</span><p>{{ $partner->association->name ?? '-' }}</p></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Inicio</span><p>{{ $partner->date_begin ? \Carbon\Carbon::parse($partner->date_begin)->format('d/m/Y') : '-' }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Fin</span><p>{{ $partner->date_end ? \Carbon\Carbon::parse($partner->date_end)->format('d/m/Y') : '-' }}</p></div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Beneficiarios</span><p class="font-bold text-leaf text-lg">{{ $partner->beneficiaries->count() }}</p></div>
                @if($partner->beneficiaries->count() > 0)
                <div class="mt-3">
                    <span class="text-[11px] font-bold text-earth uppercase">Lista de Beneficiarios</span>
                    <div class="mt-2 space-y-2">
                        @foreach($partner->beneficiaries as $beneficiary)
                        @php $latestHistory = $beneficiary->histories->first(); @endphp
                        <div class="p-3 bg-gray-50 rounded-lg border border-wheat text-sm">
                            <div class="flex items-center justify-between border-b border-wheat pb-2 mb-2">
                                <span class="text-xs font-bold text-leaf uppercase">Beneficiario</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                                <div>
                                    <span class="text-[10px] font-bold text-earth uppercase">Nombre</span>
                                    <p class="font-semibold">{{ $beneficiary->person->names ?? '' }} {{ $beneficiary->person->father_lastname ?? '' }} {{ $beneficiary->person->mother_lastname ?? '' }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-earth uppercase">DNI</span>
                                    <p>{{ $beneficiary->person->dni ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                                <div>
                                    <span class="text-[10px] font-bold text-earth uppercase">Parentesco</span>
                                    <p>{{ $beneficiary->relationship->title ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="border-t border-wheat pt-2 mt-2">
                                <span class="text-[10px] font-bold text-earth uppercase">Datos Clínicos</span>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-1">
                                    <div><span class="text-[9px] text-earth uppercase">Peso</span><p class="text-xs">{{ $latestHistory->weight ?? '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">Talla</span><p class="text-xs">{{ $latestHistory->height ?? '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">HMG</span><p class="text-xs">{{ $latestHistory->hmg ?? '-' }}</p></div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-1">
                                    <div><span class="text-[9px] text-earth uppercase">F. Inicio</span><p class="text-xs">{{ $latestHistory && $latestHistory->date_begin ? \Carbon\Carbon::parse($latestHistory->date_begin)->format('d/m/Y') : '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">F. Fin</span><p class="text-xs">{{ $latestHistory && $latestHistory->date_end ? \Carbon\Carbon::parse($latestHistory->date_end)->format('d/m/Y') : '-' }}</p></div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-1">
                                    <div><span class="text-[9px] text-earth uppercase">Tipo Beneficio</span><p class="text-xs">{{ $latestHistory->typeBenefit->title ?? '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">Estado</span><p class="text-xs">{{ $latestHistory->state->title ?? '-' }}</p></div>
                                    <div><span class="text-[9px] text-earth uppercase">Motivo Descalif.</span><p class="text-xs">{{ $latestHistory->reasonDisqualification->title ?? 'Ninguno' }}</p></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @if($partner->observations)
                <div><span class="text-[11px] font-bold text-earth uppercase">Observaciones</span><p class="text-earth">{{ $partner->observations }}</p></div>
                @endif
            </div>
            <div class="px-6 pb-6">
                <button onclick="closeModal('modal-ver-socio-{{ $partner->id }}')" class="btn-secondary w-full text-xs sm:text-sm">Cerrar</button>
            </div>
        </div>
    </div>
</div>
