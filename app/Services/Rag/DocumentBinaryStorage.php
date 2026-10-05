<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\Storage;

/**
 * Binarios RAG en disco (storage/app/rag/*) con fallback a file_data legacy.
 *
 * Nuevos uploads van a disco; file_data queda nullable para compatibilidad.
 */
class DocumentBinaryStorage
{
    public static function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('local');
    }

    public static function put(string $directory, string $hash, string $binary): string
    {
        $path = trim($directory, '/').'/'.$hash.'.bin';
        static::disk()->put($path, $binary);

        return $path;
    }

    public static function get(?string $path, ?string $legacyFileData): ?string
    {
        if (is_string($path) && $path !== '' && static::disk()->exists($path)) {
            return static::disk()->get($path);
        }

        if (! is_string($legacyFileData) || $legacyFileData === '') {
            return null;
        }

        $decoded = base64_decode($legacyFileData, true);
        if ($decoded === false) {
            return null;
        }

        $inflated = @gzinflate($decoded);

        return $inflated === false ? $decoded : $inflated;
    }

    public static function delete(?string $path): void
    {
        if (is_string($path) && $path !== '' && static::disk()->exists($path)) {
            static::disk()->delete($path);
        }
    }
}
