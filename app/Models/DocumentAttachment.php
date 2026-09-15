<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentAttachment extends Model
{
    use HasFactory;

    public const TYPE_FICHA_FISICA = 'ficha_fisica';
    public const TYPE_DNI_SOCIA = 'dni_socia';
    public const TYPE_CARNET_GESTACION = 'carnet_gestacion';
    public const TYPE_DNI_BENEFICIARIO = 'dni_beneficiario';
    public const TYPE_PARTIDA_NACIMIENTO = 'partida_nacimiento';
    public const TYPE_CONSTANCIA_MEDICA = 'constancia_medica';

    public const TYPES = [
        self::TYPE_FICHA_FISICA,
        self::TYPE_DNI_SOCIA,
        self::TYPE_CARNET_GESTACION,
        self::TYPE_DNI_BENEFICIARIO,
        self::TYPE_PARTIDA_NACIMIENTO,
        self::TYPE_CONSTANCIA_MEDICA,
    ];

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'document_type',
        'file_name',
        'mime_type',
        'file_size',
        'file_data',
    ];

    protected $casts = [
        'file_data' => 'encrypted',
        'file_size' => 'integer',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
