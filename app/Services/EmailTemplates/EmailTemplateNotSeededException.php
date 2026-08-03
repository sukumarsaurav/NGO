<?php

declare(strict_types=1);

namespace App\Services\EmailTemplates;

use RuntimeException;

/**
 * Every automated email routes through a seeded `email_templates` row —
 * see docs/modules/M09-notices-communication.md. A missing key means the
 * calling Mailable is using a key EmailTemplateSeeder doesn't know about yet.
 */
final class EmailTemplateNotSeededException extends RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Email template key '{$key}' has not been seeded. Add it to EmailTemplateSeeder first.");
    }
}
