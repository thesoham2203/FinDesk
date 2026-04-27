<?php

declare(strict_types=1);

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class ClientLogout
{
    /**
     * Log the current client out of the application.
     */
    public function __invoke(): void
    {
        Auth::guard('client')->logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
