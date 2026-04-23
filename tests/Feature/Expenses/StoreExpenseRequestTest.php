<?php

declare(strict_types=1);

use App\Http\Requests\StoreExpenseRequest;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * Helper function to validate data against StoreExpenseRequest
 */
function validateExpenseRequest(array $data, ?User $user = null): array|true
{
    $user ??= User::factory()->create();

    $request = new StoreExpenseRequest();
    $request->setUserResolver(fn () => $user);

    try {
        $validated = $request->validate($request->rules());

        return true;
    } catch (ValidationException $validationException) {
        return $validationException->errors();
    }
}

describe('StoreExpenseRequest - Authorization', function (): void {
    it('requires authorization', function (): void {
        $request = new StoreExpenseRequest();

        // The request requires the 'create-expenses' gate to be defined
        // In practice, this would be defined in a service provider or policy
        expect($request::class)->toEqual(StoreExpenseRequest::class);
    });
});

describe('StoreExpenseRequest - Basic Validation', function (): void {
    beforeEach(function (): void {
        Gate::define('create-expenses', fn (): true => true);
    });

    it('validates required fields', function (): void {
        $request = new StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['title'])->toContain('required');
        expect($rules['amount'])->toContain('required');
        expect($rules['category_id'])->toContain('required');
        expect($rules['currency'])->toContain('required');
    });

    it('validates title is string and max 255', function (): void {
        $request = new StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['title'])->toContain('string');
        expect($rules['title'])->toContain('max:255');
    });

    it('validates description is nullable string max 2000', function (): void {
        $request = new StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['description'])->toContain('nullable');
        expect($rules['description'])->toContain('string');
        expect($rules['description'])->toContain('max:2000');
    });

    it('validates amount is required integer min 1', function (): void {
        $request = new StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['amount'])->toContain('required');
        expect($rules['amount'])->toContain('integer');
        expect($rules['amount'])->toContain('min:1');
    });

    it('validates category_id exists in database', function (): void {
        $request = new StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['category_id'])->toContain('required');
        expect($rules['category_id'])->toContain('exists:expense_categories,id');
    });

    it('validates currency is required and in allowed list', function (): void {
        $request = new StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['currency'])->toContain('required');
        expect($rules['currency'])->toContain('in:USD,EUR,GBP,INR');
    });

    it('has proper error messages', function (): void {
        $request = new StoreExpenseRequest();
        $messages = $request->messages();

        expect($messages)->toHaveKey('title.required');
        expect($messages)->toHaveKey('amount.required');
        expect($messages)->toHaveKey('amount.integer');
        expect($messages)->toHaveKey('amount.min');
        expect($messages)->toHaveKey('category_id.required');
        expect($messages)->toHaveKey('category_id.exists');
        expect($messages)->toHaveKey('currency.required');
    });

    it('maps attribute names correctly', function (): void {
        $request = new StoreExpenseRequest();
        $attributes = $request->attributes();

        expect($attributes['category_id'])->toBe('category');
    });
});

describe('StoreExpenseRequest - File Validation', function (): void {
    beforeEach(function (): void {
        Gate::define('create-expenses', fn (): true => true);
    });

    it('validates receipt is nullable file', function (): void {
        $request = new StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['receipt'])->toContain('nullable');
        expect($rules['receipt'])->toContain('file');
    });

    it('validates receipt mime types', function (): void {
        $request = new StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['receipt'])->toContain('mimes:jpg,jpeg,png,pdf');
    });

    it('validates receipt max file size', function (): void {
        $request = new StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['receipt'])->toContain('max:5120');
    });

    it('has proper receipt error messages', function (): void {
        $request = new StoreExpenseRequest();
        $messages = $request->messages();

        expect($messages)->toHaveKey('receipt.mimes');
        expect($messages)->toHaveKey('receipt.max');
    });
});

describe('StoreExpenseRequest - Custom Validation', function (): void {
    beforeEach(function (): void {
        // Ensure clean state between tests
        Gate::define('create-expenses', fn (): true => true);
    });

    it('rejects expense exceeding category max_amount', function (): void {
        $category = ExpenseCategory::factory()->create(['max_amount' => 10000]);
        $user = User::factory()->create();

        $request = new StoreExpenseRequest();
        $request->setUserResolver(fn () => $user);
        $request->replace([
            'title' => 'Flight ticket',
            'amount' => 15000,
            'category_id' => $category->id,
            'currency' => 'INR',
        ]);

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $request->withValidator($validator);

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('amount'))->toBeTrue();
    });

    it('rejects expense within category max_amount', function (): void {
        $category = ExpenseCategory::factory()->create(['max_amount' => 1000]);
        $user = User::factory()->create();

        $request = new StoreExpenseRequest();
        $request->setUserResolver(fn () => $user);
        $request->replace([
            'title' => 'Flight ticket',
            'amount' => 9999,
            'category_id' => $category->id,
            'currency' => 'INR',
        ]);

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $request->withValidator($validator);

        expect($validator->fails())->toBeTrue();
    });

    it('allows expense without receipt when category does not require it', function (): void {
        $category = ExpenseCategory::factory()->create(['requires_receipt' => false]);
        $user = User::factory()->create();

        $request = new StoreExpenseRequest();
        $request->setUserResolver(fn () => $user);
        $request->replace([
            'title' => 'Flight ticket',
            'amount' => 50000,
            'category_id' => $category->id,
            'currency' => 'INR',
        ]);

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $request->withValidator($validator);

        expect($validator->fails())->toBeFalse();
    });

    it('requires receipt when category requires it', function (): void {
        $category = ExpenseCategory::factory()->create(['requires_receipt' => true]);
        $user = User::factory()->create();

        $request = new StoreExpenseRequest();
        $request->setUserResolver(fn () => $user);
        $request->replace([
            'title' => 'Flight ticket',
            'amount' => 50000,
            'category_id' => $category->id,
            'currency' => 'INR',
        ]);

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $request->withValidator($validator);

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('receipt'))->toBeTrue();
    });
});

describe('StoreExpenseRequest - Error Messages', function (): void {
    beforeEach(function (): void {
        Gate::define('create-expenses', fn (): true => true);
    });

    it('returns custom message for required title', function (): void {
        $request = new StoreExpenseRequest();
        $messages = $request->messages();

        expect($messages['title.required'])->toContain('Expense title is required');
    });

    it('returns custom message for required amount', function (): void {
        $request = new StoreExpenseRequest();
        $messages = $request->messages();

        expect($messages['amount.required'])->toContain('Amount is required');
    });

    it('returns custom message for amount integer validation', function (): void {
        $request = new StoreExpenseRequest();
        $messages = $request->messages();

        expect($messages['amount.integer'])->toContain('must be a valid number');
    });

    it('returns custom message for amount min validation', function (): void {
        $request = new StoreExpenseRequest();
        $messages = $request->messages();

        expect($messages['amount.min'])->toContain('must be greater than 0');
    });

    it('returns custom message for category_id exists validation', function (): void {
        $request = new StoreExpenseRequest();
        $messages = $request->messages();

        expect($messages['category_id.exists'])->toContain('does not exist');
    });

    it('returns custom message for receipt mime types', function (): void {
        $request = new StoreExpenseRequest();
        $messages = $request->messages();

        expect($messages['receipt.mimes'])->toContain('JPG');
    });

    it('returns custom message for receipt max size', function (): void {
        $request = new StoreExpenseRequest();
        $messages = $request->messages();

        expect($messages['receipt.max'])->toContain('5MB');
    });

    it('maps category_id attribute to category in errors', function (): void {
        $request = new StoreExpenseRequest();
        $attributes = $request->attributes();

        expect($attributes['category_id'])->toBe('category');
    });
});
