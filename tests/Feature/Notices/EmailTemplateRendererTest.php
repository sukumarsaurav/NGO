<?php

declare(strict_types=1);

use App\Models\EmailTemplate;
use App\Services\EmailTemplates\EmailTemplateNotSeededException;
use App\Services\EmailTemplates\EmailTemplateRenderer;

it('substitutes whitelisted variables in subject and body', function () {
    EmailTemplate::factory()->create([
        'key' => 'test.greeting',
        'subject' => 'Hello {{ name }}',
        'body_html' => '<p>Hi {{ name }}, your balance is {{ amount }}.</p>',
        'available_variables' => ['name', 'amount'],
    ]);

    $rendered = app(EmailTemplateRenderer::class)->render('test.greeting', ['name' => 'Asha', 'amount' => '100']);

    expect($rendered['subject'])->toBe('Hello Asha')
        ->and($rendered['body'])->toBe('<p>Hi Asha, your balance is 100.</p>');
});

it('leaves a non-whitelisted token untouched instead of substituting it', function () {
    EmailTemplate::factory()->create([
        'key' => 'test.leak',
        'subject' => 'Subject',
        'body_html' => '<p>{{ secret }}</p>',
        'available_variables' => ['name'],
    ]);

    $rendered = app(EmailTemplateRenderer::class)->render('test.leak', ['name' => 'Asha', 'secret' => 'do-not-leak']);

    expect($rendered['body'])->toBe('<p>{{ secret }}</p>');
});

it('leaves a whitelisted token literal when no value was supplied', function () {
    EmailTemplate::factory()->create([
        'key' => 'test.missing',
        'subject' => 'Subject',
        'body_html' => '<p>{{ name }}</p>',
        'available_variables' => ['name'],
    ]);

    $rendered = app(EmailTemplateRenderer::class)->render('test.missing', []);

    expect($rendered['body'])->toBe('<p>{{ name }}</p>');
});

it('throws when the key was never seeded', function () {
    app(EmailTemplateRenderer::class)->render('nonexistent.key', []);
})->throws(EmailTemplateNotSeededException::class);

it('reports is_active from the seeded row', function () {
    EmailTemplate::factory()->create(['key' => 'test.inactive', 'is_active' => false]);

    expect(app(EmailTemplateRenderer::class)->isActive('test.inactive'))->toBeFalse();
});
