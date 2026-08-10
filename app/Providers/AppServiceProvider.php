<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureGates();

        Vite::prefetch(concurrency: 3);
    }

    /**
     * Strict models in development so N+1 queries and typo'd attributes fail
     * loudly instead of reaching the demo (§40). Disabled in production, where
     * a missing eager-load should degrade rather than 500.
     */
    private function configureModels(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
        Model::unguard(false);
    }

    /**
     * Administrators bypass every permission check.
     *
     * Returning null (not false) for everyone else is important: it lets the
     * normal policy chain run instead of short-circuiting to "denied".
     */
    private function configureGates(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(UserRole::Administrator->value) ? true : null;
        });
    }
}
