<?php

namespace App\Providers;

use App\Models\SupplyClient;
use App\Models\SupplyIssueRequest;
use App\Models\SupplyRequest;
use App\Models\User;
use App\Policies\SupplyPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        SupplyClient::class => SupplyPolicy::class,
        SupplyRequest::class => SupplyPolicy::class,
        SupplyIssueRequest::class => SupplyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if (!$user instanceof User) {
                return null;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            return null;
        });
    }
}
