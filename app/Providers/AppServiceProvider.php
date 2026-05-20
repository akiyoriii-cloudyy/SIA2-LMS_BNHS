<?php

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogLogout;
use App\Listeners\LogSuccessfulLogin;
use App\Models\SchoolNotification;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Failed::class, LogFailedLogin::class);
        Event::listen(Logout::class, LogLogout::class);

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by(strtolower($email).'|'.$request->ip()),
            ];
        });

        RateLimiter::for('api-login', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(20)->by($request->ip()),
                Limit::perMinute(10)->by(strtolower($email).'|'.$request->ip()),
            ];
        });

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();
            if (! $user || ! $user->hasPermission('lms.portal')) {
                $view->with('inAppUnreadCount', 0);

                return;
            }

            $count = SchoolNotification::query()
                ->where('user_id', $user->id)
                ->inApp()
                ->unread()
                ->count();

            $view->with('inAppUnreadCount', $count);
        });
    }
}
