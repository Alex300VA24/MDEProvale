<?php

namespace App\Services;

use App\Models\DocumentAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class DocumentAttachmentService
{
    public function store(Model $attachable, string $documentType, UploadedFile $file): DocumentAttachment
    {
        $raw = file_get_contents($file->getRealPath());
        $compressed = base64_encode(gzdeflate($raw, 9));

        $payload = [
            'document_type' => $documentType,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'file_data' => $compressed,
        ];

        if (in_array($documentType, [DocumentAttachment::TYPE_EXPEDIENTE_UNICO, DocumentAttachment::TYPE_CONADIS], true)) {
            return $attachable->documents()->updateOrCreate(['document_type' => $documentType], $payload);
        }

        return $attachable->documents()->create($payload);
    }

    public function getDecryptedContent(DocumentAttachment $attachment): string
    {
        $decoded = base64_decode($attachment->file_data);
        $uncompressed = @gzinflate($decoded);

        if ($uncompressed === false) {
            return $decoded;
        }

        return $uncompressed;
    }

    public function delete(DocumentAttachment $attachment): void
    {
        $attachment->delete();
    }
}
