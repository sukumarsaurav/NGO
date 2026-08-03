<?php

declare(strict_types=1);

namespace App\Services\Qr;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Wraps simplesoftwareio/simple-qrcode. Emits inline SVG markup, not PNG —
 * the PNG backend needs ext-imagick, which this environment doesn't have,
 * and dompdf renders inline `<svg>` reliably without it.
 */
final class QrCodeGenerator
{
    /**
     * @return string raw `<svg>...</svg>` markup, safe to echo unescaped into a Blade template
     */
    public function svg(string $payload, int $sizePixels = 200): string
    {
        $markup = (string) QrCode::format('svg')
            ->size($sizePixels)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($payload);

        // Strip the leading XML prolog line — an inline <svg> inside an
        // HTML document can't carry one.
        return (string) preg_replace('/^<\?xml.*?\?'.'>\s*/', '', $markup);
    }
}
