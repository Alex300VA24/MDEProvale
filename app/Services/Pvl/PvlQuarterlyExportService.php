<?php

namespace App\Services\Pvl;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Table as TableStyle;

class PvlQuarterlyExportService
{
    private const FONT = 'Times New Roman';

    public function __construct(private PvlQuarterlyDocumentService $document)
    {
    }

    /** @param array<string,string> $charts clave => ruta de PNG temporal */
    public function pdf(array $blocks, array $charts)
    {
        return Pdf::loadView('reportes.pvl.trimestral', [
            'letterhead' => $this->document->letterhead(),
            'logo' => $this->logoPath(),
            'blocks' => $blocks,
            'charts' => array_map(fn (string $path) => 'data:image/png;base64,'.base64_encode((string) file_get_contents($path)), $charts),
        ])->setPaper('a4');
    }

    /** Devuelve la ruta de un .docx temporal. */
    public function docx(array $blocks, array $charts): string
    {
        $word = new PhpWord();
        $word->setDefaultFontName(self::FONT);
        $word->setDefaultFontSize(12);
        $word->setDefaultParagraphStyle(['spaceAfter' => 120, 'spacing' => 276]);

        $section = $word->addSection([
            'pageSizeW' => 11906, 'pageSizeH' => 16838,
            'marginTop' => 850, 'marginBottom' => 1134, 'marginLeft' => 1418, 'marginRight' => 1134,
        ]);

        $this->letterhead($section);

        foreach ($blocks as $block) {
            match ($block['type']) {
                'title' => $section->addText($block['text'], ['bold' => true, 'underline' => 'single'], ['spaceAfter' => 240]),
                'meta' => $this->meta($section, $block['rows']),
                'rule' => $section->addText(str_repeat('-', 118), ['size' => 7], ['spaceAfter' => 160]),
                'h' => $section->addText($block['text'], ['bold' => true], ['spaceBefore' => 200, 'keepNext' => true]),
                'p' => $section->addText($block['text'], [], ['alignment' => Jc::BOTH]),
                'ol' => $this->list($section, $block['items']),
                'table' => $this->table($section, $block),
                'image' => $this->image($section, $block, $charts),
                'sign' => $this->sign($section, $block),
                'note' => $section->addText($block['text'], ['size' => 10], ['spaceBefore' => 240]),
                default => null,
            };
        }

        $path = tempnam(sys_get_temp_dir(), 'pvlq').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    private function logoPath(): string
    {
        return public_path('img/muni2-bn.png');
    }

    private function letterhead(Section $section): void
    {
        $l = $this->document->letterhead();
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $row = $table->addRow();
        $logo = $row->addCell(1300);
        if (is_file($this->logoPath())) {
            $logo->addImage($this->logoPath(), ['width' => 52, 'height' => 50]);
        }
        $text = $row->addCell(8000);
        $center = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $text->addText($l['municipalidad'], ['bold' => true, 'size' => 15], $center);
        $text->addText($l['creacion'], ['size' => 8], $center);
        $text->addText($l['telefono'], ['size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);
        $text->addText($l['area'], ['size' => 11], $center);
        $text->addText($l['programa'], ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
    }

    private function meta(Section $section, array $rows): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        foreach ($rows as [$label, $lines]) {
            $row = $table->addRow();
            $row->addCell(1700)->addText($label, [], ['spaceAfter' => 120]);
            $row->addCell(400)->addText(':', [], ['spaceAfter' => 120]);
            $cell = $row->addCell(7200);
            foreach ($lines as $i => [$text, $bold]) {
                $cell->addText($text, ['bold' => $bold], ['spaceAfter' => $i === count($lines) - 1 ? 120 : 0]);
            }
        }
    }

    private function list(Section $section, array $items): void
    {
        foreach ($items as $i => $item) {
            $section->addText(($i + 1).'. '.$item, [], ['alignment' => Jc::BOTH, 'indentation' => ['left' => 360, 'hanging' => 360]]);
        }
    }

    private function table(Section $section, array $block): void
    {
        if (! empty($block['caption'])) {
            $section->addText($block['caption'], ['bold' => true, 'size' => 10], ['spaceBefore' => 120, 'spaceAfter' => 60, 'keepNext' => true]);
        }
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50, 'layout' => TableStyle::LAYOUT_AUTO, 'width' => 5000, 'unit' => 'pct']);
        $cell = fn ($row, string $text, string $align, bool $bold) => $row->addCell()->addText($text, ['bold' => $bold, 'size' => 9], ['alignment' => $align === 'r' ? Jc::END : Jc::START, 'spaceAfter' => 0]);

        $head = $table->addRow();
        foreach ($block['head'] as $i => $text) {
            $cell($head, $text, $block['align'][$i], true);
        }
        foreach ($block['rows'] as $values) {
            $row = $table->addRow();
            foreach ($values as $i => $text) {
                $cell($row, (string) $text, $block['align'][$i], false);
            }
        }
        if (! empty($block['total'])) {
            $row = $table->addRow();
            foreach ($block['total'] as $i => $text) {
                $cell($row, (string) $text, $block['align'][$i], true);
            }
        }
        $section->addText('', [], ['spaceAfter' => 60]);
    }

    private function image(Section $section, array $block, array $charts): void
    {
        if (! isset($charts[$block['key']])) {
            return;
        }
        $section->addImage($charts[$block['key']], ['width' => 400, 'alignment' => Jc::CENTER]);
        $section->addText($block['caption'], ['size' => 9, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);
    }

    private function sign(Section $section, array $block): void
    {
        $section->addText('', [], ['spaceAfter' => 600]);
        $section->addText('______________________________', [], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText($block['name'], ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText($block['role'], [], ['alignment' => Jc::CENTER]);
    }
}
