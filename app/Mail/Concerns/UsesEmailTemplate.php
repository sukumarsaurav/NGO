<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use App\Services\EmailTemplates\EmailTemplateRenderer;

/**
 * Every transactional Mailable pulls its subject/body from `email_templates`
 * instead of a hand-written Blade view — see
 * docs/modules/M09-notices-communication.md. `envelope()` and `content()`
 * both need the rendered copy; memoized per key so a single send only hits
 * the renderer once.
 */
trait UsesEmailTemplate
{
    /** @var array{subject: string, body: string}|null */
    private ?array $renderedTemplate = null;

    private ?string $renderedTemplateKey = null;

    /**
     * @param  array<string, scalar|null>  $variables
     * @return array{subject: string, body: string}
     */
    protected function renderTemplate(string $key, array $variables): array
    {
        if ($this->renderedTemplateKey !== $key) {
            $this->renderedTemplate = app(EmailTemplateRenderer::class)->render($key, $variables);
            $this->renderedTemplateKey = $key;
        }

        return $this->renderedTemplate;
    }
}
