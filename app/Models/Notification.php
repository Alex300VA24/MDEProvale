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
    public const SYSTEM_WIDE_TYPES = ['normativa_pvl', 'alerta_beneficiarios', 'alerta_resolucion'];

    // Avisos que exigen una acción: siguen contando en la campanita, aunque ya
    // se hayan visto, hasta que se solucionen.
    public const STATUS_OPEN = 'por_solucionar';
    public const STATUS_SOLVED = 'solucionado';

    public static function unreadCountForUser(?User $user): int
    {
        if (!$user || !$user->id) {
            return 0;
        }

        $query = self::where(fn ($q) => $q
            ->where('is_seen', false)
            ->orWhere('status', self::STATUS_OPEN));

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
