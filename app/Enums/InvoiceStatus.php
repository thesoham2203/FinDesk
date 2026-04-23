<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'blue',
            self::Viewed => 'purple',
            self::PartiallyPaid => 'yellow',
            self::Paid => 'green',
            self::Overdue => 'red',
            self::Cancelled => 'black',
        };
    }

    /**
     * Returns the array of valid next statuses from the current state.
     *
     * Used by the state machine to enforce valid transitions between invoice statuses.
     * Some transitions are automatic (payment recorded updates status).
     * Overdue is set by a scheduled command.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent, self::Cancelled],
            self::Sent => [
                self::Viewed,
                self::PartiallyPaid,
                self::Paid,
                self::Overdue,
                self::Cancelled,
            ],
            self::Viewed => [
                self::PartiallyPaid,
                self::Paid,
                self::Overdue,
                self::Cancelled,
            ],
            self::PartiallyPaid => [self::Paid, self::Overdue, self::Cancelled],
            self::Paid => [self::Cancelled],
            self::Overdue => [self::PartiallyPaid, self::Paid, self::Cancelled],
            self::Cancelled => [],
        };
    }
}
