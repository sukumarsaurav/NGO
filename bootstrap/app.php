<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
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
    })->create();
