<?php

declare(strict_types=1);

use App\Http\Requests\UpdateTaxRateRequest;

describe('UpdateTaxRateRequest', function (): void {
    it('has correct validation rules', function (): void {
        $request = new UpdateTaxRateRequest();
        $rules = $request->rules();

        expect($rules)->toBeArray();
        expect(count($rules))->toBeGreaterThan(0);
    });
});
