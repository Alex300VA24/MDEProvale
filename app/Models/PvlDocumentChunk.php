<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PvlDocumentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'pvl_document_id',
        'page_number',
        'chunk_index',
        'content',
        'embedding',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
        'page_number' => 'integer',
        'chunk_index' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(PvlDocument::class, 'pvl_document_id');
    }
}
