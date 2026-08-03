<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Blade;

/**
 * Compiles an admin-editable `document_templates.body_html` string (Blade
 * syntax, e.g. `{{ $member->name }}`) against real data. Isolated to this one
 * class so `Blade::render()` — a facade — never leaks into an Action; see
 * docs/05-CONVENTIONS.md's "no facades in Actions/Services" rule.
 */
final class TemplateEngine
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $bodyHtml, array $data): string
    {
        return Blade::render($bodyHtml, $data);
    }
}
