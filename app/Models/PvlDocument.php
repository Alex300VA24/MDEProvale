<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PvlDocument extends Model
{
    use HasFactory;

    public const TYPES = [
        'factura',
        'comprobante',
        'orden_compra',
        'financiamiento',
        'donacion',
        'proveedor',
        'distribucion',
        'certificado_calidad',
        'certificado_microbiologico',
        'ficha_tecnica',
        'lote',
        'beneficiarios',
        'constancia_envio',
        'otro',
    ];

    protected $fillable = [
        'document_type',
        'period',
        'product_id',
        'provider_reference',
        'file_name',
        'mime_type',
        'file_size',
        'file_hash',
        'file_path',
        'file_data',
        'index_status',
        'index_error',
        'created_by',
    ];

    protected $hidden = ['file_data', 'file_path'];

    protected $casts = [
        'file_data' => 'encrypted',
        'file_size' => 'integer',
    ];

    public function chunks()
    {
        return $this->hasMany(PvlDocumentChunk::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
