<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerifiedDocument extends Model
{
    use HasFactory;

    public const TYPE_PECOSA_REGISTER = 'padron_pecosas';
    public const TYPE_PECOSA_RECEIPT = 'comprobante_pecosa';
    public const TYPE_DISTRIBUTION_REGISTER = 'padron_reparticion';
    public const STATUS_VALID = 'vigente';
    public const STATUS_REVOKED = 'revocado';

    protected $fillable = [
        'token',
        'type',
        'identifier',
        'status',
        'issued_at',
        'revoked_at',
        'metadata',
        'storage_path',
        'sha256',
        'created_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PECOSA_REGISTER => 'Padrón de Pecosas',
            self::TYPE_PECOSA_RECEIPT => 'Comprobante de Pecosa',
            self::TYPE_DISTRIBUTION_REGISTER => 'Padrón de Repartición',
            default => 'Documento institucional',
        };
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID && $this->revoked_at === null;
    }
}
