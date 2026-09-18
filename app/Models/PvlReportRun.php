<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PvlReportRun extends Model
{
    use HasFactory;

    public const BORRADOR = 'BORRADOR';
    public const ANALIZANDO = 'ANALIZANDO';
    public const REQUIERE_REVISION = 'REQUIERE_REVISION';
    public const LISTO_PARA_GENERAR = 'LISTO_PARA_GENERAR';
    public const GENERADO = 'GENERADO';
    public const ERROR = 'ERROR';

    protected $fillable = [
        'report_type',
        'month',
        'year',
        'status',
        'input_snapshot_json',
        'ai_output_json',
        'validated_data_json',
        'warnings_json',
        'sources_json',
        'model_used',
        'prompt_version',
        'source_fingerprint',
        'error_message',
        'created_by',
        'generated_at',
    ];

    protected $casts = [
        'input_snapshot_json' => 'array',
        'ai_output_json' => 'array',
        'validated_data_json' => 'array',
        'warnings_json' => 'array',
        'sources_json' => 'array',
        'month' => 'integer',
        'year' => 'integer',
        'generated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canGenerate(): bool
    {
        return in_array($this->status, [self::LISTO_PARA_GENERAR, self::GENERADO], true);
    }
}
