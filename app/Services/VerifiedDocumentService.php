<?php

namespace App\Services;

use App\Models\VerifiedDocument;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VerifiedDocumentService
{
    public function __construct(private PDFService $pdfService)
    {
    }

    public function issue(
        string $type,
        string $identifier,
        array $metadata,
        string $view,
        array $viewData,
        string $filename,
        ?int $createdBy,
        string $paper = 'a4',
        string $orientation = 'landscape'
    ): array {
        $document = VerifiedDocument::create([
            'token' => bin2hex(random_bytes(32)),
            'type' => $type,
            'identifier' => $identifier,
            'status' => VerifiedDocument::STATUS_VALID,
            'issued_at' => now(),
            'metadata' => $metadata,
            'created_by' => $createdBy,
        ]);
        $path = null;

        try {
            $verificationUrl = route('documents.verify', ['token' => $document->token]);
            $pdf = $this->pdfService->generate($view, array_merge($viewData, [
                'verificationDocument' => $document,
                'verificationUrl' => $verificationUrl,
                'qrDataUri' => $this->qrDataUri($verificationUrl),
            ]), $paper, $orientation);
            $contents = $pdf->output();
            $safeFilename = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '.pdf';
            $path = 'documentos-verificados/' . $document->token . '/' . $safeFilename;

            if (!Storage::disk('local')->put($path, $contents)) {
                throw new RuntimeException('No se pudo almacenar el PDF verificable.');
            }
            $document->update([
                'storage_path' => $path,
                'sha256' => hash('sha256', $contents),
            ]);

            return [$document->fresh(), $contents, $safeFilename];
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            $document->delete();
            throw $exception;
        }
    }

    public function qrDataUri(string $url): string
    {
        $qrCode = QrCode::create($url)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->setSize(240)
            ->setMargin(12);

        return (new PngWriter())->write($qrCode)->getDataUri();
    }
}
