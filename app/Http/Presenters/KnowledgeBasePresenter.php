<?php

namespace App\Http\Presenters;

use App\Models\KnowledgeBaseDocument;
use App\Models\NormativaDocument;

/**
 * Da forma JSON a los documentos de la base de conocimiento (incluye las URLs de las rutas).
 */
class KnowledgeBasePresenter
{
    public function document(KnowledgeBaseDocument $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'file_name' => $document->file_name,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'index_status' => $document->index_status,
            'index_error' => $document->index_error,
            'created_at' => optional($document->created_at)->format('d/m/Y H:i'),
            'download_url' => route('api.base-conocimiento.documents.download', $document),
            'preview_url' => route('api.base-conocimiento.documents.download', $document),
            'sort_date' => optional($document->created_at)->toISOString(),
            'origin' => 'upload',
        ];
    }

    public function municipalDocument(NormativaDocument $document): array
    {
        return [
            'id' => 'normativa-'.$document->id,
            'title' => $document->titulo,
            'file_name' => trim($document->tipo_documento.' '.($document->numero ? 'N° '.$document->numero : '')),
            'mime_type' => $document->mime_type ?: 'application/pdf',
            'file_size' => $document->file_size,
            'index_status' => $document->index_status,
            'index_error' => $document->index_error,
            'created_at' => optional($document->fecha_documento)->format('d/m/Y'),
            'sort_date' => optional($document->fecha_documento)->format('Y-m-d'),
            'preview_url' => $document->file_path
                ? route('api.base-conocimiento.normativa.preview', $document)
                : null,
            'download_url' => $document->file_path
                ? route('api.base-conocimiento.normativa.download', $document)
                : null,
            'official_url' => $document->pdf_url,
            'stored' => $document->file_path !== null,
            'origin' => 'municipal',
            'summary' => $document->relevancia_resumen,
        ];
    }
}
