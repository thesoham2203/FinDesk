<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

final class GenerateInvoiceNumber
{
    public function execute(): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($year): string {
            // Lock the last invoice of the current year for exclusive access
            $lastInvoice = Invoice::query()
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            // Parse the next sequence number
            if ($lastInvoice === null) {
                // First invoice of the year
                $nextNumber = 1;
            } else {
                // Extract sequence number from existing invoice_number (e.g., '0042' from 'INV-2026-0042')
                $parts = explode('-', (string) $lastInvoice->invoice_number);
                $currentSequence = isset($parts[2]) ? (int) $parts[2] : 0;
                $nextNumber = $currentSequence + 1;
            }

            // Format as INV-YYYY-NNNN (zero-padded to 4 digits)
            return sprintf('INV-%d-%04d', $year, $nextNumber);
        });
    }
}
