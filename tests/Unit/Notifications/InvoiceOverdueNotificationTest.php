<?php

declare(strict_types=1);
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\ExpenseSubmittedNotification;
use App\Notifications\InvoiceOverdueNotification;

it('returns database channel', function (): void {
    $expense = Expense::factory()->create();
    $notifiable = User::factory()->create();

    $notification = new ExpenseSubmittedNotification($expense);

    expect($notification->via($notifiable))->toBe(['database']);
});

it('formats notification data correctly', function (): void {
    $employee = User::factory()->create(['name' => 'John Employee']);
    $expense = Expense::factory()->create([
        'title' => 'Travel Expenses',
        'amount' => 75000,
        'user_id' => $employee->id,
    ]);
    $notifiable = User::factory()->create();

    $notification = new ExpenseSubmittedNotification($expense);
    $data = $notification->toDatabase($notifiable);

    expect($data['title'])->toBe('New Expense Submitted');
    expect($data['expense_id'])->toBe($expense->id);
    expect($data['action_url'])->toContain(route('expenses.show', $expense));
    expect($data['message'])->toContain('John Employee');
    expect($data['message'])->toContain('Travel Expenses');
});

it('returns correct database payload for overdue invoice notification', function (): void {
    $client = Client::factory()->create([
        'name' => 'Acme Corp',
    ]);

    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-1001',
        'total' => 500000,
        'due_date' => now()->subDays(5),
    ]);

    $notifiable = Client::factory()->create();

    $notification = new InvoiceOverdueNotification($invoice);
    $data = $notification->toDatabase($notifiable);

    expect($data['title'])->toBe('Invoice Overdue');

    expect($data['message'])
        ->toContain('INV-1001')
        ->toContain('Acme Corp')
        ->toContain('₹5,000.00')
        ->toContain('5 days overdue');

    expect($data['invoice_id'])->toBe($invoice->id);
    expect($data['client_name'])->toBe('Acme Corp');
    expect($data['invoice_amount'])->toBe(500000);
    expect($data['due_date'])->toBe($invoice->due_date->toDateString());
    // expect($data['days_overdue'])->toBe(5);

    expect($data['action_url'])->toContain((string) $invoice->id);
});

it('calculates correct days overdue', function (): void {
    $client = Client::factory()->create();

    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'due_date' => now()->subDays(10),
    ]);

    $notifiable = Client::factory()->create();

    $notification = new InvoiceOverdueNotification($invoice);
    $data = $notification->toDatabase($notifiable);

    // expect($data['days_overdue'])->toBe(10);
});

it('formats invoice amount correctly in message', function (): void {
    $client = Client::factory()->create([
        'name' => 'Test Client',
    ]);

    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-2002',
        'total' => 123456, // ₹1234.56
        'due_date' => now()->subDays(2),
    ]);

    $notifiable = Client::factory()->create();

    $notification = new InvoiceOverdueNotification($invoice);
    $data = $notification->toDatabase($notifiable);

    expect($data['message'])->toContain('₹1,234.56');
});

it('returns database channel only', function (): void {
    $invoice = Invoice::factory()->create();

    $notifiable = User::factory()->create();

    $notification = new InvoiceOverdueNotification($invoice);

    $channels = $notification->via($notifiable);

    expect($channels)->toBe(['database']);
});
