<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('password', function (Request $request) {
            $value = $request->input('username');
            $name = is_string($value) ? strtolower(substr(trim($value), 0, 40)) : '';
            return [
                Limit::perMinute(20)->by('ip:'.$request->ip()),
                Limit::perMinute(6)->by('name:'.hash('sha256', $name)),
            ];
        });
        RateLimiter::for('invitation', fn (Request $request) => Limit::perMinute(8)->by($request->ip()));
        RateLimiter::for('verification', fn (Request $request) => [
            Limit::perMinute(20)->by('ip:'.$request->ip()),
            Limit::perMinute(5)->by('member:'.($request->session()->get('entry.user') ?? $request->ip())),
        ]);
    }
}
