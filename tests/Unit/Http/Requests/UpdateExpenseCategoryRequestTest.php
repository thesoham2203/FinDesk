<?php

declare(strict_types=1);

use App\Http\Requests\UpdateExpenseCategoryRequest;
use Illuminate\Support\Facades\Gate;

describe('UpdateExpenseCategoryRequest', function (): void {
    it('has correct validation rules', function (): void {
        $request = new UpdateExpenseCategoryRequest();
        $rules = $request->rules();

        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('description');
        expect($rules)->toHaveKey('max_amount');
        expect($rules)->toHaveKey('requires_receipt');
    });

    it('authorizes the request', function (): void {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('manage-categories');

        $request = new UpdateExpenseCategoryRequest();
        expect($request->authorize())->toBeTrue();
    });

    it('has correct custom messages', function (): void {
        $request = new UpdateExpenseCategoryRequest();
        $messages = $request->messages();

        expect($messages)->toHaveKey('name.required');
        expect($messages['name.required'])->toBe('Category name is required.');
        expect($messages)->toHaveKey('name.unique');
        expect($messages['name.unique'])->toBe('A different category with this name already exists.');
        expect($messages)->toHaveKey('max_amount.min');
        expect($messages['max_amount.min'])->toBe('Maximum amount must be greater than 0.');
        expect($messages)->toHaveKey('requires_receipt.required');
        expect($messages['requires_receipt.required'])->toBe('Please specify if receipts are required.');
    });

    it('has correct custom attributes', function (): void {
        $request = new UpdateExpenseCategoryRequest();
        $attributes = $request->attributes();

        expect($attributes)->toHaveKey('max_amount');
        expect($attributes['max_amount'])->toBe('maximum amount');
        expect($attributes)->toHaveKey('requires_receipt');
        expect($attributes['requires_receipt'])->toBe('requires receipt');
    });
});
