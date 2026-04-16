<?php

declare(strict_types=1);

use App\Policies\UserPolicy;

describe('UserPolicy', function (): void {
    it('has viewAny method', function (): void {
        $policy = new UserPolicy();
        expect(method_exists($policy, 'viewAny'))->toBeTrue();
    });

    it('has view method', function (): void {
        $policy = new UserPolicy();
        expect(method_exists($policy, 'view'))->toBeTrue();
    });

    it('has create method', function (): void {
        $policy = new UserPolicy();
        expect(method_exists($policy, 'create'))->toBeTrue();
    });

    it('has update method', function (): void {
        $policy = new UserPolicy();
        expect(method_exists($policy, 'update'))->toBeTrue();
    });

    it('has delete method', function (): void {
        $policy = new UserPolicy();
        expect(method_exists($policy, 'delete'))->toBeTrue();
    });
});
