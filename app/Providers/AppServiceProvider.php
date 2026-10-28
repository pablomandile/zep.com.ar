<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Límites para crear links desde la web y para la API (por token).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('links', fn (Request $request) => Limit::perMinute(
            (int) config('shortener.rate_limits.web_create_per_minute'),
        )->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(
            (int) config('shortener.rate_limits.api_per_minute'),
        )->by(match (true) {
            $request->bearerToken() !== null => 'token:'.hash('sha256', $request->bearerToken()),
            $request->user() !== null => 'user:'.$request->user()->id,
            default => 'ip:'.$request->ip(),
        }));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
