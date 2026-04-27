<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Register scheduled commands (Day 7: Invoice Scheduling)
Schedule::command('invoices:check-overdue')->dailyAt('09:00');
