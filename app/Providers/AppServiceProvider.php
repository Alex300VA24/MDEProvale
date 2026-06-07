<?php

namespace App\Providers;

use App\Http\Middleware\TrustProxies;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
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
            
            try {
                if (auth()->check()) {
                    $user = auth()->user();
                    
                    // Eager-load 'rol' to prevent a lazy-load query
                    // every time the layout accesses Auth::user()->rol->title
                    if (!$user->relationLoaded('rol')) {
                        $user->load('rol:id,title');
                    }

                    $cacheKey = 'unread_notifications_user_' . $user->id;
                    $unreadNotifications = Cache::remember($cacheKey, 30, function () use ($user) {
                        return Notification::unreadCountForUser($user);
                    });
                }
            } catch (\Exception $e) {
                \Log::error('Error al contar notificaciones: ' . $e->getMessage());
                $unreadNotifications = 0;
            }
            
            $unreadNotificationsLabel = $unreadNotifications > 9 ? '9+' : $unreadNotifications;
            $view->with('unreadNotifications', $unreadNotifications);
            $view->with('unreadNotificationsLabel', $unreadNotificationsLabel);
        });
    }
}
