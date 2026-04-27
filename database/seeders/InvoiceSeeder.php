<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Invoice;
use Illuminate\Database\Seeder;

final class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        Invoice::factory()->count(10)->create();
    }
}
