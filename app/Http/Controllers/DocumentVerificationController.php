<?php

namespace App\Http\Controllers;

use App\Models\VerifiedDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentVerificationController extends Controller
{
    public function show(string $token)
    {
        $document = $this->findByToken($token);

        return response()
            ->view('documents.verify', compact('document'))
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Cache-Control', 'no-store, private');
    }

    public function pdf(string $token): BinaryFileResponse
    {
        $document = $this->findByToken($token);
        abort_unless($document->storage_path && Storage::disk('local')->exists($document->storage_path), 404);

        $filename = $document->identifier . '.pdf';
        $disposition = request()->boolean('descargar') ? 'attachment' : 'inline';

        return response()->file(Storage::disk('local')->path($document->storage_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function findByToken(string $token): VerifiedDocument
    {
        abort_unless((bool) preg_match('/\A[a-f0-9]{64}\z/', $token), 404);

        return VerifiedDocument::where('token', $token)->firstOrFail();
    }
}
