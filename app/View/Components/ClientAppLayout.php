<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

final class ClientAppLayout extends Component
{
    public function render(): View
    {
        return view('layouts.client-app');
    }
}
