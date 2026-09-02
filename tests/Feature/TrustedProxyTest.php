<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Regression test for the Railway deployment: TLS is terminated at Railway's
 * edge, which forwards plain HTTP to the container. Without trusting that
 * proxy, `$request->isSecure()` — and every absolute URL Laravel generates
 * from it, including the asset URLs the login page embeds — comes back
 * `http://` on a page served over `https://`, which the browser then blocks
 * as mixed content.
 */
test('a request forwarded as https by a trusted proxy is treated as secure', function (): void {
    Route::get('/__trusted-proxy-check', fn () => request()->isSecure() ? 'secure' : 'insecure');

    $this->get('/__trusted-proxy-check', ['X-Forwarded-Proto' => 'https'])
        ->assertOk()
        ->assertSee('secure');
});

test('a request with no forwarded-proto header is treated as plain http', function (): void {
    Route::get('/__trusted-proxy-check', fn () => request()->isSecure() ? 'secure' : 'insecure');

    $this->get('/__trusted-proxy-check')
        ->assertOk()
        ->assertSee('insecure');
});
