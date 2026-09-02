<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Exceptions\Rendering\InertiaExceptionRenderer;
use App\Exceptions\Rendering\ProblemJsonExceptionRenderer;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway terminates TLS at its edge and forwards plain HTTP to the
        // container, so without this every absolute URL Laravel generates
        // (asset URLs, redirects) uses the wrong scheme — the browser then
        // blocks the http:// asset requests as mixed content on the https://
        // page. The container is never reached except through that edge, so
        // trusting all proxies is safe here.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $wantsJson = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($wantsJson);

        $exceptions->render(
            fn (DomainException $e, Request $request): JsonResponse|RedirectResponse => $wantsJson($request)
                ? app(ProblemJsonExceptionRenderer::class)->render($e, $request)
                : app(InertiaExceptionRenderer::class)->render($e, $request),
        );
    })->create();
