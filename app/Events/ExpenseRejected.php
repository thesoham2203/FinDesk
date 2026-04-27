<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ExpenseRejected
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Expense $expense,
        public User $rejector,
        public string $reason,
    ) {}
}
