<?php

declare(strict_types=1);

/**
 * CheckOverdueInvoices Artisan Command
 *
 * WHAT: Daily scheduled command that checks for invoices past their due_date
 *       and marks them as Overdue. Notifies accountants and admins.
 *
 * WHY: Invoice status must automatically reflect overdue state for business reporting.
 *      This command runs daily (via scheduler) to keep invoice statuses in sync with dates.
 *      Notifications alert the accounting team to follow up.
 *
 * IMPLEMENT: Query invoices with due_date < today in payable states (Sent, Viewed, PartiallyPaid),
 *            transition each to Overdue, dispatch InvoiceOverdue event (triggers notifications),
 *            log activity for audit trail.
 *
 * REFERENCE:
 * - Artisan Commands: https://laravel.com/docs/13.x/artisan#writing-commands
 * - Task Scheduling: https://laravel.com/docs/13.x/scheduling
 * - State Machine: App\Enums\InvoiceStatus::transitionTo()
 */

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceOverdue;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

final class CheckOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue invoices and update their status, notify accounting team';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->startOfDay();
        $count = 0;

        // Query for invoices that are past due and still in payable states
        $overdue = Invoice::query()
            ->where('due_date', '<', $today)
            ->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Viewed->value,
                InvoiceStatus::PartiallyPaid->value,
            ])
            ->with(['creator', 'client'])
            ->get();

        // Process each overdue invoice
        foreach ($overdue as $invoice) {
            // Transition to Overdue status (triggers activity logging via observer)
            $invoice->transitionTo(InvoiceStatus::Overdue);
            $invoice->save();

            // Dispatch event to trigger notifications
            InvoiceOverdue::dispatch($invoice);

            $this->info("Marked invoice {$invoice->invoice_number} as overdue");
            $count++;
        }

        // Notify all accountants and admins
        $this->notifyAccountingTeam($overdue);

        $this->info("Processed {$count} overdue invoices");

        return self::SUCCESS;
    }

    /**
     * Send notifications to accounting team about overdue invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Invoice>  $overdue
     */
    private function notifyAccountingTeam(Collection $overdue): void
    {
        if ($overdue->isEmpty()) {
            return;
        }

        // Get all accountants and admins
        $accountingTeam = User::query()
            ->whereIn('role', ['admin', 'accountant'])
            ->get();

        // Send notification to each team member
        foreach ($accountingTeam as $user) {
            // Send one notification per overdue invoice
            foreach ($overdue as $invoice) {
                $user->notify(new InvoiceOverdueNotification($invoice));
            }
        }

        $this->info("Notifications sent to {$accountingTeam->count()} team members");
    }
}
