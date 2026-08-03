<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Models\DocumentTemplate;
use Barryvdh\DomPDF\PDF as DompdfWrapper;

/**
 * Wraps barryvdh/laravel-dompdf. Renders a DocumentTemplate's HTML/CSS with
 * injected member data and QR markup to PDF bytes.
 *
 * Registers Noto Sans Devanagari on every render — dompdf silently renders
 * unembedded Devanagari as empty boxes (see docs/03-ROADMAP.md's Sprint 4
 * note), so this is not optional. The font ships as a single static weight;
 * dompdf's registerFont() is called for all four style slots against that
 * one file — a deliberate simplification (no bold Devanagari), acceptable
 * because the acceptance bar is "renders correctly", not "renders bold".
 */
final class PdfRenderer
{
    private const MM_TO_PT = 2.83464567;

    public function __construct(
        private readonly DompdfWrapper $pdf,
    ) {}

    public function render(DocumentTemplate $template, string $bodyHtml): string
    {
        return $this->renderHtml($bodyHtml, $template->css ?? '', $template->page_size, $template->orientation);
    }

    /**
     * Lower-level entry point for PDFs that aren't backed by an admin-editable
     * DocumentTemplate row — receipts (Sprint 6) render from a static Blade
     * view instead. Both paths go through the same font registration and base
     * CSS so Hindi text and page-size handling behave identically everywhere.
     */
    public function renderHtml(string $bodyHtml, string $css, string $pageSize, string $orientation): string
    {
        $this->registerHindiFont();

        $html = '<html><head><meta charset="utf-8"><style>'
            .$this->baseCss()
            .$css
            .'</style></head><body>'
            .$bodyHtml
            .'</body></html>';

        $pdf = $this->pdf->loadHTML($html);
        $pdf->setPaper($this->paperSize($pageSize), $orientation);

        return $pdf->output();
    }

    /**
     * @return array{0:int,1:int,2:float,3:float}|string
     */
    private function paperSize(string $pageSize): array|string
    {
        if (strtoupper($pageSize) === 'CR80') {
            // 85.6 x 54 mm, the ISO/IEC 7810 ID-1 card size.
            $widthPt = 85.6 * self::MM_TO_PT;
            $heightPt = 54 * self::MM_TO_PT;

            return [0, 0, $widthPt, $heightPt];
        }

        return $pageSize;
    }

    private function registerHindiFont(): void
    {
        $file = storage_path('fonts/NotoSansDevanagari-Regular.ttf');

        if (! is_file($file)) {
            return;
        }

        $metrics = $this->pdf->getDomPDF()->getFontMetrics();

        foreach (['normal', 'bold', 'italic', 'bold_italic'] as $variant) {
            [$weight, $style] = str_contains($variant, 'bold')
                ? ['bold', str_contains($variant, 'italic') ? 'italic' : 'normal']
                : ['normal', $variant === 'italic' ? 'italic' : 'normal'];

            $metrics->registerFont(
                ['family' => 'Noto Sans Devanagari', 'weight' => $weight, 'style' => $style],
                $file
            );
        }
    }

    private function baseCss(): string
    {
        return <<<'CSS'
            @page { margin: 0; }
            body {
                font-family: 'Noto Sans Devanagari', 'DejaVu Sans', sans-serif;
                margin: 0;
                padding: 0;
            }
            CSS;
    }
}
