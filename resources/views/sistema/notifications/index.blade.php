@if($solicitudes->count() > 0)
    <div class="space-y-4">
        @foreach($solicitudes as $solicitud)
        <div class="p-4 rounded-xl border-2 border-mist {{ $solicitud->is_seen ? 'bg-gray-50' : 'bg-base' }}">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        @if($solicitud->type === 'password_reset')
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded">Contraseña</span>
                        @else
                        <span class="px-2 py-1 bg-blue-light text-blue text-xs font-bold rounded">{{ $solicitud->type }}</span>
                        @endif
                        @if($solicitud->status === 'approved')
                        <span class="px-2 py-1 bg-teal-light text-teal text-xs font-bold rounded"><i class="fas fa-check mr-1"></i> Aprobado</span>
                        @elseif($solicitud->status === 'rejected')
                        <span class="px-2 py-1 bg-coral-light text-coral text-xs font-bold rounded"><i class="fas fa-times mr-1"></i> Rechazado</span>
                        @endif
                    </div>
                    <p class="font-bold text-navy">{{ $solicitud->title }}</p>
                    <p class="text-sm text-slate mt-1">{{ $solicitud->description }}</p>
                    <p class="text-xs text-slate mt-2">
                        Usuario: {{ $solicitud->user->names ?? 'N/A' }} | Fecha: {{ $solicitud->requested_at->format('d/m/Y H:i') }}
                        @if($solicitud->processed_at)
                        | Procesado: {{ $solicitud->processedByUser->names ?? 'Admin' }} ({{ $solicitud->processed_at->format('d/m/Y H:i') }})
                        @endif
                    </p>
                </div>
                <div class="flex gap-2 ml-4">
                    @if(Auth::user()->rol_id == 1 && $solicitud->status === 'pending')
                    <form action="{{ route('sistema.notifications.approve', $solicitud->id) }}" method="POST" class="inline" onsubmit="document.getElementById('loading-screen').classList.add('active');">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-blue text-white text-sm font-bold rounded-lg hover:opacity-90">
                            <i class="fas fa-check mr-1"></i> Aprobar
                        </button>
                    </form>
                    <form action="{{ route('sistema.notifications.reject', $solicitud->id) }}" method="POST" class="inline" onsubmit="document.getElementById('loading-screen').classList.add('active');">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-red-500 text-white text-sm font-bold rounded-lg hover:opacity-90">
                            <i class="fas fa-times mr-1"></i> Rechazar
                        </button>
                    </form>
                    @elseif($solicitud->status === 'approved')
                    <button class="px-3 py-2 bg-green-100 text-green-700 text-sm font-bold rounded-lg cursor-default" disabled>
                        <i class="fas fa-check-circle mr-1"></i> Aprobado
                    </button>
                    @elseif($solicitud->status === 'rejected')
                    <button class="px-3 py-2 bg-red-100 text-red-700 text-sm font-bold rounded-lg cursor-default" disabled>
                        <i class="fas fa-times-circle mr-1"></i> Rechazado
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="text-center py-8 text-slate">
        <i class="fas fa-inbox text-4xl mb-3"></i>
        <p>No hay notificaciones</p>
    </div>
@endif