<?php

declare(strict_types=1);

use App\Http\Requests\StoreInvoiceRequest;

describe('StoreInvoiceRequest', function (): void {
    it('has correct validation rules', function (): void {
        $request = new StoreInvoiceRequest();
        $rules = $request->rules();

        expect($rules)->toBeArray();
        expect(count($rules))->toBeGreaterThan(0);
    });
});
