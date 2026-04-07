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
            self::INR => '₹',
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
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
}
