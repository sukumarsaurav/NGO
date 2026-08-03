<?php

declare(strict_types=1);

namespace App\Services\EmailTemplates;

use App\Models\EmailTemplate;
use Psr\Log\LoggerInterface;

/**
 * Renders subject/body for every automated email from its `email_templates`
 * row — see docs/modules/M09-notices-communication.md. Substitution is a
 * whitelist token replace, never Blade compilation: admin-authored copy must
 * never be able to execute code. A `{{ token }}` outside the template's own
 * `available_variables` list is left untouched and logged, never fatal.
 */
final class EmailTemplateRenderer
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param  array<string, scalar|null>  $variables
     * @return array{subject: string, body: string}
     */
    public function render(string $key, array $variables): array
    {
        $template = $this->find($key);

        return [
            'subject' => $this->substitute($template->subject, $variables, $template->available_variables),
            'body' => $this->substitute($template->body_html, $variables, $template->available_variables),
        ];
    }

    public function isActive(string $key): bool
    {
        return $this->find($key)->is_active;
    }

    private function find(string $key): EmailTemplate
    {
        $template = EmailTemplate::query()->where('key', $key)->first();

        if (! $template) {
            throw new EmailTemplateNotSeededException($key);
        }

        return $template;
    }

    /**
     * @param  array<string, scalar|null>  $variables
     * @param  array<int, string>  $whitelist
     */
    private function substitute(string $text, array $variables, array $whitelist): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $matches) use ($variables, $whitelist): string {
            $name = $matches[1];

            if (! in_array($name, $whitelist, true)) {
                $this->logger->warning("Email template variable '{{{$name}}}' is not in its available_variables whitelist.");

                return $matches[0];
            }

            return array_key_exists($name, $variables) && $variables[$name] !== null
                ? (string) $variables[$name]
                : $matches[0];
        }, $text);
    }
}
