<?php

declare(strict_types=1);

use App\Http\Requests\StoreTaxRateRequest;

describe('StoreTaxRateRequest', function (): void {
    it('has correct validation rules', function (): void {
        $request = new StoreTaxRateRequest();
        $rules = $request->rules();

        expect($rules)->toBeArray();
        expect(count($rules))->toBeGreaterThan(0);
    });
});
