<?php

use App\Http\Middleware\AdminMiddleware;
use App\Support\ErrorSanitizer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
            'webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof ValidationException) {
                return null;
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                $status = 500;
                if ($e instanceof HttpExceptionInterface) {
                    $status = $e->getStatusCode();
                } elseif (property_exists($e, 'status') && is_int($e->status)) {
                    $status = $e->status;
                }

                return response()->json([
                    'status' => 'error',
                    'message' => ErrorSanitizer::sanitize($e->getMessage()),
                ], $status);
            }
        });
    })->create();
