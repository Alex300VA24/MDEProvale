<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Association::class => \App\Policies\AssociationPolicy::class,
        \App\Models\Partner::class => \App\Policies\PartnerPolicy::class,
        \App\Models\Pecosa::class => \App\Policies\PecosaPolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
        \App\Models\Racion::class => \App\Policies\RacionPolicy::class,
        \App\Models\Resolution::class => \App\Policies\ResolutionPolicy::class,
        \App\Models\Transaction::class => \App\Policies\TransactionPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
