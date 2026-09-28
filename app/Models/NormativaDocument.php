<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NormativaDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'tipo_id',
        'tipo_documento',
        'periodo',
        'numero',
        'titulo',
        'asunto',
        'concepto',
        'fecha_documento',
        'pdf_url',
        'index_status',
        'index_error',
        'relevancia_pvl',
        'relevancia_resumen',
        'relevancia_motivo',
        'notified_at',
    ];

    protected $casts = [
        'fecha_documento' => 'date:Y-m-d',
        'relevancia_pvl' => 'boolean',
        'notified_at' => 'datetime',
    ];

    public function chunks()
    {
        return $this->hasMany(NormativaDocumentChunk::class);
    }
}
