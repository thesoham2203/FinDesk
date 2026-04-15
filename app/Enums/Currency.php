<?php

declare(strict_types=1);

/**
 * Currency Enum
 *
 * WHAT: Defines supported currencies: INR, USD, EUR, GBP.
 *
 * WHY: FinDesk must support multi-currency. The organization has a default currency, but each
 *      expense and invoice can be recorded in its own currency. Using an enum ensures consistency
 *      and type safety when working with currency values.
 *
 * IMPLEMENT: The label(), color(), and symbol() methods are complete. No further implementation needed.
 *            This enum is cast on Organization, Expense, and Invoice models. The symbol() method
 *            is used in Attribute accessors for money formatting (e.g., "₹ 1,234.56").
 *
 * REFERENCE:
 * - Laravel Enums: https://laravel.com/docs/13.x/eloquent#castingusing-enums
 * - Eloquent Mutators (for formatting): https://laravel.com/docs/13.x/eloquent-mutators
 */

namespace App\Enums;

enum Currency: string
{
    case INR = 'INR';
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';

    public function label(): string
    {
        return match ($this) {
            self::INR => 'Indian Rupee',
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::GBP => 'British Pound',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INR => 'orange',
            self::USD => 'green',
            self::EUR => 'blue',
            self::GBP => 'red',
        };
    }

    /**
     * Returns the currency symbol (₹, $, €, £).
     *
     * Used for formatting money display in the UI.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::INR => '₹',
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
        };
    }
}
