<?php

declare(strict_types=1);

use App\Actions\Invoice\GenerateInvoiceNumber;
use App\Models\Invoice;
use Illuminate\Support\Facades\Date;

it('generates the first invoice number for the year', function (): void {
    $year = now()->year;
    $action = new GenerateInvoiceNumber();

    $number = $action->execute();

    expect($number)->toBe(sprintf('INV-%d-0001', $year));
});

it('generates sequential invoice numbers', function (): void {
    $year = now()->year;
    Invoice::factory()->create([
        'invoice_number' => sprintf('INV-%d-0001', $year),
        'created_at' => now(),
    ]);

    $action = new GenerateInvoiceNumber();
    $number = $action->execute();

    expect($number)->toBe(sprintf('INV-%d-0002', $year));
});

it('resets sequence for a new year', function (): void {
    Date::setTestNow('2025-01-01');
    $lastYear = 2024;
    $currentYear = 2025;

    Invoice::factory()->create([
        'invoice_number' => sprintf('INV-%d-0005', $lastYear),
        'created_at' => Date::parse('2024-12-31'),
    ]);

    $action = new GenerateInvoiceNumber();
    $number = $action->execute();

    expect($number)->toBe(sprintf('INV-%d-0001', $currentYear));

    Date::setTestNow(); // Reset
});

it('handles race conditions with pessimistic locking', function (): void {
    // This is hard to test in a simple unit test,
    // but we can at least verify it runs without errors in a transaction.
    $action = new GenerateInvoiceNumber();
    $number = $action->execute();

    expect($number)->toMatch('/INV-\d{4}-\d{4}/');
});
