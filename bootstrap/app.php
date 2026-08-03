<?php

use App\Http\Middleware\VerifyRazorpayWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['verify.razorpay.webhook' => VerifyRazorpayWebhook::class]);

        // The gateway sends no CSRF token — HMAC signature verification
        // (VerifyRazorpayWebhook) is this route's actual auth boundary.
        $middleware->validateCsrfTokens(except: ['webhooks/razorpay']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Sensitive fields never get flashed back to a re-rendered form after
        // a validation failure. Password fields are Laravel's own default;
        // the rest are ours per docs/05-CONVENTIONS.md — "never log PAN, card
        // data, or webhook payloads containing PII."
        //
        // This governs session flashing only. Payment/webhook code (M05,
        // built in Sprint 5+) must redact these same fields explicitly before
        // any ->log() or Log:: call — this list does not protect that path.
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'pan',
            'card_number',
            'cvv',
            'otp',
        ]);

        // No-op when SENTRY_LARAVEL_DSN is unset — the SDK checks the DSN
        // before initializing, so this is safe to register unconditionally
        // in every environment.
        Integration::handles($exceptions);
    })->create();
