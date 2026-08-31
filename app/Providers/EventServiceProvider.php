<?php

namespace App\Providers;

use App\Events\ResponsiblePeriodEnded;
use App\Listeners\LogResponsiblePeriodEnded;
use App\Models\Pecosa;
use App\Observers\PecosaObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ResponsiblePeriodEnded::class => [
            LogResponsiblePeriodEnded::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Pecosa::observe(PecosaObserver::class);
    }
}
