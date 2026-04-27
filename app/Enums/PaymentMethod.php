<?php

declare(strict_types=1);

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
