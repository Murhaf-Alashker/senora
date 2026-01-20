<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
        RateLimiter::for('once-per-10-seconds', function () {
            return Limit::perSecond(1,5)->by(request()->ip())->response(function () {
                return response()->json([
                    'message' => 'الرجاء الانتظار 5 ثواني قبل إعادة المحاولة'
                ], 429);
            });
        });
    }
}
