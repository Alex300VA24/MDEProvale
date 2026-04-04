<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Notification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.main', function ($view) {
            $unreadNotifications = 0;
            if (auth()->check()) {
                $user = auth()->user();
                if ($user->isAdmin()) {
                    $unreadNotifications = Notification::unreadCount();
                } else {
                    $unreadNotifications = Notification::where('requested_by', $user->id)
                        ->where('is_seen', false)
                        ->count();
                }
            }
            $unreadNotificationsLabel = $unreadNotifications > 9 ? '9+' : $unreadNotifications;
            $view->with('unreadNotifications', $unreadNotifications);
            $view->with('unreadNotificationsLabel', $unreadNotificationsLabel);
        });
    }
}
