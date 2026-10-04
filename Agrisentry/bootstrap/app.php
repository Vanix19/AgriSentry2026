<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies();
        $middleware->statefulApi();
        $middleware->redirectUsersTo('/species');
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception) {
            $request = request();
            if ($exception instanceof \Illuminate\Database\QueryException ||
                ($request->is('login', 'login/*', 'password/*', 'api/password/*', 'api/mobile/login*') &&
                 !$exception instanceof \Illuminate\Validation\ValidationException &&
                 !$exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)) {
                \App\Support\DeploymentErrors::log($request->is('login') && $request->isMethod('post')
                    ? 'Login backend failure' : 'Backend request failed.', $exception, [
                    'path' => $request->path(), 'method' => $request->method(),
                ]);
                // Avoid Laravel's default exception dump exposing SQL bindings.
                return false;
            }
        });
        $exceptions->render(function (\Illuminate\Database\QueryException $exception, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'The server is temporarily unavailable. Please wait a moment and try again.'], 503);
            }
        });
    })->create();
