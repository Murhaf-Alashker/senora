<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
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
        $exist = Setting::where('id','>',0)->exists();
        if(!$exist) {
            Setting::create([
                'instagram' => 'instagram.com',
                'facebook' => 'facebook.com',
                'whatsapp' => 'whatsapp.com',
                'contact_us_email' => fake()->safeEmail(),
                'wholesale_at' => 100,
            ]);
        }

        RateLimiter::for('once-per-10-seconds', function () {
            return Limit::perSecond(1,5)->by(request()->ip())->response(function () {
                return response()->json([
                    'message' => 'الرجاء الانتظار 5 ثواني قبل إعادة المحاولة'
                ], 429);
            });
        });
    }
}
