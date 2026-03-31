<?php

declare(strict_types=1);

use App\View\Components\GuestLayout;

test('guest layout can be rendered', function (): void {
    $component = new GuestLayout();

    $view = $component->render();

    expect($view->name())->toBe('layouts.guest');
});
