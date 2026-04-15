<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

final class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        ExpenseCategory::query()->create([
            'name' => 'Travel',
            'description' => 'Travel expenses including flights, hotels, and transportation',
            'max_amount' => 1000000, // ₹10,000 in paise
            'requires_receipt' => true,
        ]);

        ExpenseCategory::query()->create([
            'name' => 'Meals',
            'description' => 'Meal and food expenses',
            'max_amount' => 50000, // ₹500 in paise
            'requires_receipt' => false,
        ]);

        ExpenseCategory::query()->create([
            'name' => 'Software',
            'description' => 'Software licenses and subscriptions',
            'max_amount' => 500000, // ₹5,000 in paise
            'requires_receipt' => true,
        ]);

        ExpenseCategory::query()->create([
            'name' => 'Office Supplies',
            'description' => 'Office supplies and materials',
            'max_amount' => null,
            'requires_receipt' => false,
        ]);
    }
}
