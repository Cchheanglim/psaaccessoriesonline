<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
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
        // Behind Render's TLS proxy the app would otherwise generate http://
        // links and set cookies without the secure flag.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configurePasswordRules();
        $this->configureRateLimiting();
    }

    /**
     * Minimum password strength for registration and admin-created accounts.
     */
    protected function configurePasswordRules(): void
    {
        Password::defaults(function () {
            return $this->app->isProduction()
                ? Password::min(10)->letters()->numbers()->uncompromised()
                : Password::min(8)->letters()->numbers();
        });
    }

    /**
     * Named rate limiters.
     *
     * Login is keyed on submitted email *and* IP so that one attacker cannot
     * lock every account out by guessing against a single address, and a
     * botnet cannot spread guesses for one account across many addresses.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(5)->by(mb_strtolower($email).'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('uploads', fn (Request $request) => Limit::perHour(20)->by($request->user()?->id ?: $request->ip()));
    }
}
