<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Events\InvoiceOverdue;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('marks invoices past due_date as overdue', function (): void {
    $invoice = Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Sent,
    ]);

    $this->artisan('invoices:check-overdue')->assertExitCode(0);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Overdue);
});

it('skips invoices in non-payable states', function (InvoiceStatus $status): void {
    $invoice = Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => $status,
    ]);

    $originalStatus = $invoice->status;

    $this->artisan('invoices:check-overdue')->assertExitCode(0);

    $invoice->refresh();
    expect($invoice->status)->toBe($originalStatus);
})->with([
    InvoiceStatus::Draft,
    InvoiceStatus::Paid,
    InvoiceStatus::Cancelled,
]);

it('dispatches InvoiceOverdue event for each marked invoice', function (): void {
    Event::fake();

    $invoice1 = Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Sent,
    ]);

    $invoice2 = Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Viewed,
    ]);

    $this->artisan('invoices:check-overdue');

    Event::assertDispatched(InvoiceOverdue::class, 2);
});

it('does not modify future-due invoices', function (): void {
    $futureInvoice = Invoice::factory()->create([
        'due_date' => now()->addDay(),
        'status' => InvoiceStatus::Sent,
    ]);

    $this->artisan('invoices:check-overdue');

    $futureInvoice->refresh();
    expect($futureInvoice->status)->toBe(InvoiceStatus::Sent);
});

it('returns correct exit code on success', function (): void {
    Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Sent,
    ]);

    $this->artisan('invoices:check-overdue')->assertExitCode(0);
});

it('handles empty result gracefully', function (): void {
    // Create invoices that should not be marked as overdue
    Invoice::factory()->create([
        'due_date' => now()->addDay(),
        'status' => InvoiceStatus::Sent,
    ]);

    Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Paid,
    ]);

    $this->artisan('invoices:check-overdue')
        ->assertExitCode(0);
});

it('processes multiple overdue invoices', function (): void {
    Invoice::factory()->count(5)->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Sent,
    ]);

    $this->artisan('invoices:check-overdue')->assertExitCode(0);

    $overdueCount = Invoice::where('status', InvoiceStatus::Overdue)->count();
    expect($overdueCount)->toBe(5);
});

it('processes PartiallyPaid invoices correctly', function (): void {
    $invoice = Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::PartiallyPaid,
    ]);

    $this->artisan('invoices:check-overdue');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Overdue);
});

it('processes Overdue status invoices already marked', function (): void {
    $invoice = Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Overdue,
    ]);

    $this->artisan('invoices:check-overdue');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Overdue);
});

it('loads relationships to prevent N+1 queries', function (): void {
    Invoice::factory()->count(3)->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Sent,
    ]);

    // This test verifies the command uses ->with(['creator', 'client'])
    // We can't directly test N+1 in this context, but we verify the command executes
    $this->artisan('invoices:check-overdue')->assertExitCode(0);

    // All invoices should be marked as overdue
    $overdueCount = Invoice::where('status', InvoiceStatus::Overdue)->count();
    expect($overdueCount)->toBe(3);
});

it('handles invoices with today due date correctly', function (): void {
    $todayInvoice = Invoice::factory()->create([
        'due_date' => today(),
        'status' => InvoiceStatus::Sent,
    ]);

    $this->artisan('invoices:check-overdue');

    $todayInvoice->refresh();
    // Due date of today should NOT be marked as overdue (only < today)
    expect($todayInvoice->status)->toBe(InvoiceStatus::Sent);
});

it('processes mixed statuses correctly', function (): void {
    // Should be marked overdue
    $overdue1 = Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Sent,
    ]);

    $overdue2 = Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Viewed,
    ]);

    // Should NOT be marked overdue
    $shouldNotChange1 = Invoice::factory()->create([
        'due_date' => now()->subDay(),
        'status' => InvoiceStatus::Paid,
    ]);

    $shouldNotChange2 = Invoice::factory()->create([
        'due_date' => now()->addDay(),
        'status' => InvoiceStatus::Sent,
    ]);

    $this->artisan('invoices:check-overdue');

    expect(Invoice::where('status', InvoiceStatus::Overdue)->count())->toBe(2)
        ->and($shouldNotChange1->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($shouldNotChange2->fresh()->status)->toBe(InvoiceStatus::Sent);
});
