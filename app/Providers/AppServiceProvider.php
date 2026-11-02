<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('mobility-catalog', fn (Request $request): Limit => Limit::perMinute(60)->by('catalog:'.$this->rateLimitIdentity($request)));
        RateLimiter::for('mobility-search', fn (Request $request): Limit => Limit::perMinute(30)->by('search:'.$this->rateLimitIdentity($request)));
        RateLimiter::for('mobility-password', fn (Request $request): Limit => Limit::perMinute(5)->by('password:'.$this->rateLimitIdentity($request)));
        RateLimiter::for('mobility-auth', function (Request $request): array {
            $email = $request->input('email');
            $identity = hash('sha256', is_string($email) ? mb_strtolower(trim($email)) : '');

            return [
                Limit::perMinute(5)->by('auth-account:'.$request->ip().':'.$identity),
                Limit::perMinute(100)->by('auth-ip:'.$request->ip()),
            ];
        });
    }

    private function rateLimitIdentity(Request $request): string
    {
        $identity = $request->user() ? 'user:'.$request->user()->id : 'session:'.$request->session()->getId();

        return $request->ip().':'.hash('sha256', $identity);
    }
}
