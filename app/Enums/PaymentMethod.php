<?php

declare(strict_types=1);

/**
 * PaymentMethod Enum
 *
 * WHAT: Defines the supported payment methods: Bank Transfer, Credit Card, Cash, Cheque, UPI, Other.
 *
 * WHY: FinDesk needs to track HOW a payment was made for record-keeping and reconciliation.
 *      Using an enum ensures consistency and allows reference/tracking numbers to be stored contextually.
 *
 * IMPLEMENT: The label() and color() methods are complete. No additional methods needed.
 *            This enum is cast on the Payment model and used in Livewire payment forms (Day 6).
 *
 * REFERENCE:
 * - Laravel Enums: https://laravel.com/docs/13.x/eloquent#castingusing-enums
 * - Payment model: App\Models\Payment
 */

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';
    case Cash = 'cash';
    case Cheque = 'cheque';
    case UPI = 'upi';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank Transfer',
            self::CreditCard => 'Credit Card',
            self::Cash => 'Cash',
            self::Cheque => 'Cheque',
            self::UPI => 'UPI',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BankTransfer => 'blue',
            self::CreditCard => 'purple',
            self::Cash => 'green',
            self::Cheque => 'gray',
            self::UPI => 'orange',
            self::Other => 'gray',
        };
    }
}
