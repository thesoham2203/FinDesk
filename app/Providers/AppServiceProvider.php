<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use function in_array;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //

    }

    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user) => $user->role === 'admin');
        Gate::define('manage-users', fn (User $user) => $user->role === 'admin');
        Gate::define('manage-departments', fn (User $user) => $user->role === 'admin');
        Gate::define('manage-categories', fn (User $user) => $user->role === 'admin');
        Gate::define('manage-tax-rates', fn (User $user) => $user->role === 'admin');
        Gate::define('approve-expenses', fn (User $user) => $user->role === 'manager');
        Gate::define('manage-invoices', fn (User $user) => in_array($user->role, ['admin', 'manager', 'accountant'], true));
        Gate::define('record-payments', fn (User $user) => in_array($user->role, ['admin', 'accountant'], true));
        Gate::define('view-reports', fn (User $user) => in_array($user->role, ['admin', 'manager'], true));
        Gate::define('create-expenses', fn (User $user) => in_array($user->role, ['employee'], true));
    }
}
