<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Employee = 'employee';
    case Accountant = 'accountant';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
            self::Accountant => 'Accountant',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'red',
            self::Manager => 'blue',
            self::Employee => 'green',
            self::Accountant => 'purple',
        };
    }
}
