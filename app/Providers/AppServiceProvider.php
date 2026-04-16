<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ExpenseApproved;
use App\Events\ExpenseReimbursed;
use App\Events\ExpenseRejected;
use App\Events\ExpenseSubmitted;
use App\Listeners\LogExpenseActivity;
use App\Listeners\NotifyExpenseReviewed;
use App\Listeners\NotifyExpenseSubmitted;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\User;
use App\Observers\ExpenseObserver;
use App\Observers\PaymentObserver;
use Illuminate\Support\Facades\Event;
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
        // Register Authorization Gates
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

        // Register Event-Listener Mappings (Day 5: Approval Workflow)
        Event::listen(ExpenseSubmitted::class, LogExpenseActivity::class);
        Event::listen(ExpenseApproved::class, LogExpenseActivity::class);
        Event::listen(ExpenseRejected::class, LogExpenseActivity::class);
        Event::listen(ExpenseReimbursed::class, LogExpenseActivity::class);
        Event::listen(ExpenseSubmitted::class, NotifyExpenseSubmitted::class);
        Event::listen(ExpenseApproved::class, NotifyExpenseReviewed::class);
        Event::listen(ExpenseRejected::class, NotifyExpenseReviewed::class);

        // Register Model Observers (Day 5: Approval Workflow)
        Expense::observe(ExpenseObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
