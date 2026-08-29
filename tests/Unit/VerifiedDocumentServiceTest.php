<?php

namespace Tests\Unit;

use App\Services\VerifiedDocumentService;
use Tests\TestCase;

class VerifiedDocumentServiceTest extends TestCase
{
    public function test_qr_generator_returns_embeddable_png_data_uri(): void
    {
        $uri = app(VerifiedDocumentService::class)->qrDataUri('https://example.test/verificar-documento/' . str_repeat('a', 64));

        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        $this->assertGreaterThan(500, strlen($uri));
    }
}
