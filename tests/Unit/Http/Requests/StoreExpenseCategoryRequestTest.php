<?php

declare(strict_types=1);

use App\Http\Requests\StoreExpenseCategoryRequest;
use Illuminate\Support\Facades\Gate;

describe('StoreExpenseCategoryRequest', function (): void {
    it('has correct validation rules', function (): void {
        $request = new StoreExpenseCategoryRequest();
        $rules = $request->rules();

        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('description');
        expect($rules)->toHaveKey('max_amount');
        expect($rules)->toHaveKey('requires_receipt');
    });

    it('validates name is required', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['name'])->toContain('required');
    });

    it('validates name is string', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['name'])->toContain('string');
    });

    it('validates name is unique', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['name'])->toContain('unique:expense_categories');
    });

    it('validates name max length is 255', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['name'])->toContain('max:255');
    });

    it('validates description is nullable', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['description'])->toContain('nullable');
    });

    it('validates description is string', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['description'])->toContain('string');
    });

    it('validates description max length is 1000', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['description'])->toContain('max:1000');
    });

    it('validates max_amount is nullable', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['max_amount'])->toContain('nullable');
    });

    it('validates max_amount is integer', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['max_amount'])->toContain('integer');
    });

    it('validates max_amount is minimum 1', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['max_amount'])->toContain('min:1');
    });

    it('validates requires_receipt is required', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['requires_receipt'])->toContain('required');
    });

    it('validates requires_receipt is boolean', function (): void {
        $rules = new StoreExpenseCategoryRequest()->rules();

        expect($rules['requires_receipt'])->toContain('boolean');
    });

    it('authorizes the request', function (): void {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('manage-categories');

        $request = new StoreExpenseCategoryRequest();
        expect($request->authorize())->toBeTrue();
    });

    it('has correct custom messages', function (): void {
        $request = new StoreExpenseCategoryRequest();
        $messages = $request->messages();

        expect($messages)->toHaveKey('name.required');
        expect($messages['name.required'])->toBe('Category name is required.');
        expect($messages)->toHaveKey('name.unique');
        expect($messages['name.unique'])->toBe('A category with this name already exists.');
        expect($messages)->toHaveKey('max_amount.min');
        expect($messages['max_amount.min'])->toBe('Maximum amount must be greater than 0.');
        expect($messages)->toHaveKey('requires_receipt.required');
        expect($messages['requires_receipt.required'])->toBe('Please specify if receipts are required.');
    });

    it('has correct custom attributes', function (): void {
        $request = new StoreExpenseCategoryRequest();
        $attributes = $request->attributes();

        expect($attributes)->toHaveKey('max_amount');
        expect($attributes['max_amount'])->toBe('maximum amount');
        expect($attributes)->toHaveKey('requires_receipt');
        expect($attributes['requires_receipt'])->toBe('requires receipt');
    });
});
