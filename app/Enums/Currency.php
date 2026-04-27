<?php

declare(strict_types=1);

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
