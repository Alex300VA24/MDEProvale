<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBaseDocument extends Model
{
    use HasFactory;

    protected $table = 'kb_documents';

    protected $fillable = [
        'title',
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

    protected $hidden = [
        'file_data',
        'file_path',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeBaseDocumentChunk::class, 'kb_document_id');
    }
}
