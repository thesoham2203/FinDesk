<?php

declare(strict_types=1);

use App\View\Components\ClientAppLayout;

test('client app layout can be rendered', function (): void {
    $component = new ClientAppLayout();

    $view = $component->render();

    expect($view->name())->toBe('layouts.client-app');
});
