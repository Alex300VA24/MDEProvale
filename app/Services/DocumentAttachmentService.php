<?php

namespace App\Services;

use App\Models\DocumentAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentAttachmentService
{
    public function store(Model $attachable, string $documentType, UploadedFile $file): DocumentAttachment
    {
        $raw = file_get_contents($file->getRealPath());
        $disk = 'local';
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = sprintf(
            'attachments/%s/%s/%s.%s',
            Str::slug(class_basename($attachable)),
            $attachable->getKey(),
            (string) Str::uuid(),
            $extension
        );
        Storage::disk($disk)->put($path, $raw);

        $payload = [
            'document_type' => $documentType,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'storage_disk' => $disk,
            'file_path' => $path,
            'checksum' => hash('sha256', $raw),
            'uploaded_by' => auth()->id(),
            'file_data' => null,
        ];

        if (in_array($documentType, [DocumentAttachment::TYPE_EXPEDIENTE_UNICO, DocumentAttachment::TYPE_CONADIS], true)) {
            $existing = $attachable->documents()->where('document_type', $documentType)->first();
            if ($existing) {
                $oldDisk = $existing->storage_disk ?: 'local';
                $oldPath = $existing->file_path;
                $existing->update($payload);
                if ($oldPath && $oldPath !== $path) {
                    Storage::disk($oldDisk)->delete($oldPath);
                }

                return $existing->refresh();
            }

            return $attachable->documents()->create($payload);
        }

        return $attachable->documents()->create($payload);
    }

    public function getDecryptedContent(DocumentAttachment $attachment): string
    {
        if ($attachment->file_path) {
            return Storage::disk($attachment->storage_disk ?: 'local')->get($attachment->file_path);
        }

        $decoded = base64_decode($attachment->file_data);
        $uncompressed = @gzinflate($decoded);

        if ($uncompressed === false) {
            return $decoded;
        }

        return $uncompressed;
    }

    public function delete(DocumentAttachment $attachment): void
    {
        if ($attachment->file_path) {
            Storage::disk($attachment->storage_disk ?: 'local')->delete($attachment->file_path);
        }
        $attachment->delete();
    }
}
