<?php

namespace App\Providers;

use App\Http\Middleware\TrustProxies;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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
        // Los bindings de repositorios viven en RepositoryServiceProvider.
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['url']->forceRootUrl($this->app['request']->root());

        if (config('app.debug') && env('DB_QUERY_AUDIT', false)) {
            $logPath = storage_path('logs/query_audit.log');
            file_put_contents($logPath, "=== Query Audit started at " . now() . " ===\n", FILE_APPEND);

            DB::listen(function ($query) use ($logPath) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
                $caller = 'unknown';
                foreach ($trace as $frame) {
                    if (isset($frame['file']) && !str_contains($frame['file'], 'vendor')) {
                        $caller = $frame['file'] . ':' . ($frame['line'] ?? '?');
                        break;
                    }
                }

                $sql = vsprintf(str_replace('?', "'%s'", $query->sql), $query->bindings);
                $line = sprintf("[%s] [%.2f ms] %s | %s\n", now()->format('H:i:s'), $query->time, $sql, $caller);
                file_put_contents($logPath, $line, FILE_APPEND);
            });
        }

        View::composer('layouts.main', function ($view) {
            $unreadNotifications = 0;

            try {
                if (auth()->check()) {
                    $user = auth()->user();

                    if (!$user->relationLoaded('rol')) {
                        $user->load('rol:id,title,is_active');
                    }

                    $cacheKey = 'unread_notifications_user_' . $user->id;
                    $unreadNotifications = Cache::remember($cacheKey, 300, function () use ($user) {
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
