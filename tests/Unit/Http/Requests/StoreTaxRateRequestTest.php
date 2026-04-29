<?php

declare(strict_types=1);

use App\Http\Requests\StoreTaxRateRequest;
use Illuminate\Support\Facades\Gate;

describe('StoreTaxRateRequest', function (): void {
    it('has correct validation rules', function (): void {
        $request = new StoreTaxRateRequest();
        $rules = $request->rules();

        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('percentage');
        expect($rules)->toHaveKey('is_default');
        expect($rules)->toHaveKey('is_active');
    });

    it('authorizes the request', function (): void {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('manage-tax-rates');

        $request = new StoreTaxRateRequest();
        expect($request->authorize())->toBeTrue();
    });

    it('has correct custom messages', function (): void {
        $request = new StoreTaxRateRequest();
        $messages = $request->messages();

        expect($messages)->toHaveKey('name.required');
        expect($messages['name.required'])->toBe('Tax rate name is required.');
        expect($messages)->toHaveKey('percentage.required');
        expect($messages['percentage.required'])->toBe('Percentage is required.');
        expect($messages)->toHaveKey('percentage.numeric');
        expect($messages['percentage.numeric'])->toBe('Percentage must be a valid number.');
        expect($messages)->toHaveKey('percentage.min');
        expect($messages['percentage.min'])->toBe('Percentage must be at least 0.');
        expect($messages)->toHaveKey('percentage.max');
        expect($messages['percentage.max'])->toBe('Percentage cannot exceed 100.');
        expect($messages)->toHaveKey('is_default.required');
        expect($messages['is_default.required'])->toBe('Please specify if this is the default rate.');
        expect($messages)->toHaveKey('is_active.required');
        expect($messages['is_active.required'])->toBe('Please specify if this rate is active.');
    });

    it('has correct custom attributes', function (): void {
        $request = new StoreTaxRateRequest();
        $attributes = $request->attributes();

        expect($attributes)->toHaveKey('is_default');
        expect($attributes['is_default'])->toBe('default status');
        expect($attributes)->toHaveKey('is_active');
        expect($attributes['is_active'])->toBe('active status');
    });
});
