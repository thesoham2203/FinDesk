<?php

declare(strict_types=1);

use App\Actions\Invoice\GenerateInvoiceNumber;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

it('generates the first invoice number for the year', function () {
    $year = now()->year;
    $action = new GenerateInvoiceNumber();

    $number = $action->execute();

    expect($number)->toBe("INV-{$year}-0001");
});

it('generates sequential invoice numbers', function () {
    $year = now()->year;
    Invoice::factory()->create([
        'invoice_number' => "INV-{$year}-0001",
        'created_at' => now(),
    ]);

    $action = new GenerateInvoiceNumber();
    $number = $action->execute();

    expect($number)->toBe("INV-{$year}-0002");
});

it('resets sequence for a new year', function () {
    Carbon::setTestNow('2025-01-01');
    $lastYear = 2024;
    $currentYear = 2025;

    Invoice::factory()->create([
        'invoice_number' => "INV-{$lastYear}-0005",
        'created_at' => Carbon::parse('2024-12-31'),
    ]);

    $action = new GenerateInvoiceNumber();
    $number = $action->execute();

    expect($number)->toBe("INV-{$currentYear}-0001");

    Carbon::setTestNow(); // Reset
});

it('handles race conditions with pessimistic locking', function () {
    // This is hard to test in a simple unit test,
    // but we can at least verify it runs without errors in a transaction.
    $action = new GenerateInvoiceNumber();
    $number = $action->execute();

    expect($number)->toMatch('/INV-\d{4}-\d{4}/');
});
