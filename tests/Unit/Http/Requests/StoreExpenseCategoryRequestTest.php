<?php

declare(strict_types=1);

use App\Http\Requests\StoreExpenseCategoryRequest;

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
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['name'])->toContain('required');
    });

    it('validates name is string', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['name'])->toContain('string');
    });

    it('validates name is unique', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['name'])->toContain('unique:expense_categories');
    });

    it('validates name max length is 255', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['name'])->toContain('max:255');
    });

    it('validates description is nullable', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['description'])->toContain('nullable');
    });

    it('validates description is string', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['description'])->toContain('string');
    });

    it('validates description max length is 1000', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['description'])->toContain('max:1000');
    });

    it('validates max_amount is nullable', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['max_amount'])->toContain('nullable');
    });

    it('validates max_amount is integer', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['max_amount'])->toContain('integer');
    });

    it('validates max_amount is minimum 1', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['max_amount'])->toContain('min:1');
    });

    it('validates requires_receipt is required', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['requires_receipt'])->toContain('required');
    });

    it('validates requires_receipt is boolean', function (): void {
        $rules = (new StoreExpenseCategoryRequest())->rules();

        expect($rules['requires_receipt'])->toContain('boolean');
    });
});
