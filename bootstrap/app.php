<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // Webhook gateway dipanggil server-ke-server tanpa session/CSRF token.
        // Keasliannya diverifikasi lewat signature (Midtrans) atau callback
        // token (Xendit) di dalam service masing-masing.
        $middleware->validateCsrfTokens(except: [
            'payment/webhook/*',
        ]);

        // Alias tambahan untuk guard admin.
        // "auth:admin" dan "guest:admin" sudah otomatis dikenali Laravel
        // karena berbasis guard bawaan, jadi tidak perlu alias khusus.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
