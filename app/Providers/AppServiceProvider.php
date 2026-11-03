<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
     * Bootstrap any application services. Limits count clients by their address, which the trusted proxies tell
     * (config/trustedproxy.php).
     */
    public function boot(): void
    {
        // GET /up also asks the database and the cache store (Redis in Docker), so a health check sees them down.
        Event::listen(DiagnosingHealth::class, function (): void {
            DB::select('select 1');
            Cache::get('health-check');
        });
        RateLimiter::for('mobility-catalog', fn (Request $request): Limit => Limit::perMinute(60)->by('catalog:'.$this->rateLimitIdentity($request)));
        RateLimiter::for('mobility-search', function (Request $request): array {
            $limits = [Limit::perMinute(30)->by('search:'.$this->rateLimitIdentity($request))];
            if ($request->user() === null) {
                // A guest can start a new session for every search, so their address caps them all.
                $limits[] = Limit::perMinute(120)->by('search-ip:'.$request->ip());
            }

            return $limits;
        });
        RateLimiter::for('mobility-password', fn (Request $request): Limit => Limit::perMinute(5)->by('password:'.$this->rateLimitIdentity($request)));
        RateLimiter::for('mobility-auth', function (Request $request): array {
            $email = $request->input('email');
            $identity = hash('sha256', is_string($email) ? mb_strtolower(trim($email)) : '');

            // An account's limit holds whatever address the attempts come from, so changing it guesses no faster.
            return [
                Limit::perMinute(5)->by('auth-account:'.$identity),
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
