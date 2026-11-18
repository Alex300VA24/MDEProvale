<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'dedupe_key',
        'title',
        'description',
        'status',
        'user_id',
        'requested_by',
        'requested_at',
        'processed_at',
        'processed_by',
        'is_seen',
        'seen_at',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'seen_at' => 'datetime',
        'is_seen' => 'boolean',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processedByUser()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public static function pendingCount()
    {
        return self::where('status', 'pending')->count();
    }

    public static function unreadCount()
    {
        return self::where('is_seen', false)->count();
    }

    // Tipos de aviso del sistema (sin dueño individual): visibles para todos los
    // usuarios autenticados, a diferencia de las solicitudes personales
    // (ej. recuperación de contraseña) que solo ve el admin o quien las pidió.
    public const TYPE_REGULARIZATION = 'regularizacion';

    public const SYSTEM_WIDE_TYPES = ['normativa_pvl', 'alerta_beneficiarios', 'alerta_resolucion', self::TYPE_REGULARIZATION];

    // Avisos que exigen una acción: siguen contando en la campanita, aunque ya
    // se hayan visto, hasta que se solucionen.
    public const STATUS_OPEN = 'por_solucionar';
    public const STATUS_SOLVED = 'solucionado';

    // Buzón (novedades y conocimiento IA) vs campana (alertas operativas).
    public const CHANNEL_BELL = 'bell';
    public const CHANNEL_INBOX = 'inbox';
    public const INBOX_TYPES = ['normativa_pvl', 'alerta_beneficiarios'];

    // Avisos que se siguen contando (vistos o no) hasta que se marquen como
    // subsanados y la verificación confirme que el problema ya no existe.
    public const ACTIONABLE_TYPES = ['normativa_pvl', 'alerta_beneficiarios', 'alerta_resolucion', self::TYPE_REGULARIZATION];

    /** Limita la consulta al canal: buzón = tipos de novedades; campana = el resto. */
    public static function forChannel($query, ?string $channel)
    {
        return match ($channel) {
            self::CHANNEL_INBOX => $query->whereIn('type', self::INBOX_TYPES),
            self::CHANNEL_BELL => $query->whereNotIn('type', self::INBOX_TYPES),
            default => $query,
        };
    }

    public static function unreadCountForUser(?User $user, ?string $channel = null): int
    {
        if (!$user || !$user->id) {
            return 0;
        }

        $query = self::forChannel(self::where(fn ($q) => $q
            // Accionables: cuentan mientras estén abiertos, aunque ya se hayan visto.
            ->where(fn ($open) => $open
                ->whereIn('type', self::ACTIONABLE_TYPES)
                ->where('status', self::STATUS_OPEN))
            // Resto (p. ej. solicitudes de contraseña): cuentan hasta verse.
            ->orWhere(fn ($other) => $other
                ->whereNotIn('type', self::ACTIONABLE_TYPES)
                ->where(fn ($w) => $w->where('is_seen', false)->orWhere('status', self::STATUS_OPEN)))), $channel);

        if ((int) $user->rol_id === 1) {
            return $query->count();
        }

        return $query
            ->where(fn ($q) => $q
                ->where('requested_by', $user->id)
                ->orWhereIn('type', self::SYSTEM_WIDE_TYPES))
            ->count();
    }

    public static function createPasswordResetRequest(User $user)
    {
        $admin = User::where('rol_id', 1)->first();
        
        return self::create([
            'type' => 'password_reset',
            'title' => 'Solicitud de recuperación de contraseña',
            'description' => 'El usuario ' . $user->email . ' (' . $user->names . ' ' . $user->father_surname . ') solicita restablecer su contraseña.',
            'status' => 'pending',
            'user_id' => $admin ? $admin->id : $user->id,
            'requested_by' => $user->id,
            'requested_at' => now(),
            'metadata' => [
                'email' => $user->email,
                'username' => $user->username,
                'names' => $user->names,
                'father_surname' => $user->father_surname,
                'mother_surname' => $user->mother_surname,
            ],
        ]);
    }
}
