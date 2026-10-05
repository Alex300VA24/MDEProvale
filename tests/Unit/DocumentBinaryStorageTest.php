<?php

namespace Tests\Unit;

use App\Services\Rag\DocumentBinaryStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentBinaryStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_it_stores_reads_and_deletes_a_binary_by_hash(): void
    {
        $path = DocumentBinaryStorage::put('rag/kb', str_repeat('a', 64), 'contenido');

        $this->assertSame('rag/kb/'.str_repeat('a', 64).'.bin', $path);
        $this->assertSame('contenido', DocumentBinaryStorage::get($path, null));

        DocumentBinaryStorage::delete($path);

        Storage::disk('local')->assertMissing($path);
        $this->assertNull(DocumentBinaryStorage::get($path, null));
    }

    public function test_disk_binary_has_priority_over_legacy_database_fallback(): void
    {
        Storage::disk('local')->put('rag/pvl/doc.bin', 'nuevo');
        $legacy = base64_encode(gzdeflate('anterior'));

        $this->assertSame('nuevo', DocumentBinaryStorage::get('rag/pvl/doc.bin', $legacy));
    }

    public function test_it_reads_compressed_and_uncompressed_legacy_values(): void
    {
        $this->assertSame('comprimido', DocumentBinaryStorage::get(null, base64_encode(gzdeflate('comprimido'))));
        $this->assertSame('sin compresión', DocumentBinaryStorage::get(null, base64_encode('sin compresión')));
        $this->assertNull(DocumentBinaryStorage::get(null, 'base64 inválido'));
    }
}
