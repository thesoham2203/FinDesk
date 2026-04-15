<?php

declare(strict_types=1);

/**
 * UserRole Enum
 *
 * WHAT: Defines the four authorization roles in the FinDesk system.
 *
 * WHY: Authorization decisions throughout the app (gates, policies, access control) depend on
 *      a user's role. Using a string-backed enum provides type safety and makes role values
 *      available at compile time.
 *
 * IMPLEMENT: The label() and color() methods are complete. Authorization checks (Gates/Policies)
 *            are implemented in Day 3. No further changes needed to this enum.
 *
 * REFERENCE:
 * - Laravel Enums: https://laravel.com/docs/13.x/eloquent#castingusing-enums
 * - Authorization: https://laravel.com/docs/13.x/authorization
 */

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
