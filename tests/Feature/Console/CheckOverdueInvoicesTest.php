<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Events\InvoiceOverdue;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceOverdueNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('marks overdue invoices and notifies accounting team', function (): void {
    Notification::fake();
    Event::fake();

    $invoice = Invoice::factory()->sent()->create(['due_date' => now()->subDays(2)]);

    // Create accounting team members
    $admins = User::factory()->count(2)->create(['role' => 'admin']);
    $accountants = User::factory()->count(1)->create(['role' => 'accountant']);

    $this->artisan('invoices:check-overdue')->assertExitCode(0);

    $invoice->refresh();
    expect($invoice->status->value)->toBe(InvoiceStatus::Overdue->value);

    Event::assertDispatched(InvoiceOverdue::class, function ($e) use ($invoice) {
        return $e->invoice->id === $invoice->id;
    });

    foreach ($admins->concat($accountants) as $user) {
        Notification::assertSentTo($user, InvoiceOverdueNotification::class);
    }
});
