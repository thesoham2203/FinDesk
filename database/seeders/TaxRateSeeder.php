<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

final class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::factory()->create([
            'name' => 'Standard VAT',
            'percentage' => 20.00,
        ]);

        TaxRate::factory()->create([
            'name' => 'Reduced VAT',
            'percentage' => 5.00,
        ]);

        TaxRate::factory()->create([
            'name' => 'Zero VAT',
            'percentage' => 0.00,
        ]);

        TaxRate::factory()->create([
            'name' => 'GST',
            'percentage' => 18.50,
            'is_default' => true,
        ]);

        TaxRate::factory()->create([
            'name' => 'PST',
            'percentage' => 7.00,
        ]);
    }
}
