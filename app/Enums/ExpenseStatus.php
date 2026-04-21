<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseStatus: string
{
    // #[Label('Draft')] protected Draft $draft;
    // #[Label('Submitted')] protected Submitted $submitted;
    // #[Label('Approved')] protected Approved $approved;
    // #[Label('Rejected')] protected Rejected $rejected;
    // #[Label('Reimbursed')] protected  Reimbursed $reimbursed;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Reimbursed = 'reimbursed';
    case PartiallyPaid = 'partially_paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Reimbursed => 'Reimbursed',
            self::PartiallyPaid => 'Partially Paid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'yellow',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Reimbursed => 'blue',
            self::PartiallyPaid => 'orange',
        };
    }

    /**
     * Returns the array of valid next statuses from the current state.
     *
     * Used by the state machine to enforce valid transitions between expense statuses.
     * Draft → Submitted → [Approved|Rejected] → Reimbursed (if approved).
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::Approved, self::Rejected],
            self::Approved => [self::Reimbursed, self::PartiallyPaid],
            self::PartiallyPaid => [self::Reimbursed],
            self::Reimbursed => [],
            self::Rejected => [self::Draft],
        };
    }
}
