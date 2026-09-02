<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureViews();
        $this->configureRateLimiting();
        $this->clearLoginThrottleOnSuccess();
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(self::loginThrottleKey($request));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->string('credential.id');
            $identifier = $credentialId->isNotEmpty() ? $credentialId->value() : $request->session()->getId();

            return Limit::perMinute(10)->by($identifier.'|'.$request->ip());
        });
    }

    /**
     * A named limiter (`config('fortify.limiters.login')`) replaces
     * Fortify's own `EnsureLoginIsNotThrottled` pipeline step entirely —
     * including the part of it that clears the limiter on a successful
     * login. `throttle:login` on its own counts every request, successful
     * or not, so without this a user who simply logs in more than five
     * times in a minute gets locked out exactly as if those had been five
     * wrong passwords. `md5($limiterName.$limit->key)` mirrors exactly how
     * the named-limiter middleware itself derives the cache key
     * (`Illuminate\Routing\Middleware\ThrottleRequests::handleRequestUsingNamedLimiter`)
     * — there is no public API to ask it for that key instead.
     */
    private function clearLoginThrottleOnSuccess(): void
    {
        Event::listen(Login::class, function (): void {
            RateLimiter::clear(md5('login'.self::loginThrottleKey(request())));
        });
    }

    private static function loginThrottleKey(Request $request): string
    {
        $username = $request->string(Fortify::username())->lower()->value();

        return Str::transliterate($username.'|'.$request->ip());
    }
}
