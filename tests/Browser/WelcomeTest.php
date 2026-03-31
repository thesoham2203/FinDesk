<?php

declare(strict_types=1);

// Browser tests require additional setup. See tests/Feature/WelcomeTest.php for the feature test version.
it('welcome page is tested in feature tests', function (): void {
    expect(true)->toBeTrue();
})->skip('Browser tests require manual setup');

