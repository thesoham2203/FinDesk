<?php

declare(strict_types=1);

it('has welcome page', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Laravel');
});
