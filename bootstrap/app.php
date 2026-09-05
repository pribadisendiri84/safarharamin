<?php

use App\Http\Middleware\RecordPageView;
use App\Support\VisitorTracker;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            VisitorTracker::COOKIE_ID,
            VisitorTracker::COOKIE_SRC,
        ]);
        $middleware->web(append: [
            RecordPageView::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('admin', 'admin/*') && ! $request->expectsJson()) {
                return redirect()
                    ->route('admin.dashboard')
                    ->withErrors(['Kamu tidak punya akses ke halaman atau aksi ini.']);
            }
        });

        $exceptions->renderable(function (PostTooLargeException $e, Request $request) {
            if (! $request->expectsJson()) {
                return response()->view('errors.413', [], 413);
            }
        });
    })->create();
