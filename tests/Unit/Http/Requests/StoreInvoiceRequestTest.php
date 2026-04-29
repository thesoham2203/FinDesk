<?php

declare(strict_types=1);

use App\Http\Requests\StoreInvoiceRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

describe('StoreInvoiceRequest', function (): void {
    it('has correct validation rules', function (): void {
        $request = new StoreInvoiceRequest();
        $rules = $request->rules();

        expect($rules)->toHaveKey('client_id');
        expect($rules)->toHaveKey('issue_date');
        expect($rules)->toHaveKey('due_date');
        expect($rules)->toHaveKey('line_items');
    });

    it('authorizes the request', function (): void {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('manage-invoices');

        $request = new StoreInvoiceRequest();
        expect($request->authorize())->toBeTrue();
    });

    it('has correct custom messages', function (): void {
        $request = new StoreInvoiceRequest();
        $messages = $request->messages();

        expect($messages)->toHaveKey('client_id.required');
        expect($messages['client_id.required'])->toBe('Client is required.');
    });

    it('validates line items correctly in withValidator', function () {
        $request = new StoreInvoiceRequest();

        // Mocking input
        $request->merge([
            'line_items' => [
                ['quantity' => 0, 'unit_price' => 0],
            ],
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        expect($validator->errors()->has('line_items'))->toBeTrue();
        expect($validator->errors()->first('line_items'))->toBe('At least one line item must have a quantity and unit price greater than zero.');
    });

    it('passes validation when at least one line item is valid', function () {
        $request = new StoreInvoiceRequest();

        $request->merge([
            'line_items' => [
                ['quantity' => 1, 'unit_price' => 100, 'description' => 'Test'],
            ],
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        // We only care about the errors added by withValidator here
        expect($validator->errors()->has('line_items'))->toBeFalse();
    });
});
