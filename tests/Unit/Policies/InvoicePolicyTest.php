<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use App\Policies\InvoicePolicy;

describe('InvoicePolicy', function (): void {
    describe('viewAny', function (): void {
        it('allows users to view any invoices', function (): void {
            $user = User::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->viewAny($user))->toBeTrue();
        });

        it('allows admin to view any invoices', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $policy = new InvoicePolicy();

            expect($policy->viewAny($admin))->toBeTrue();
        });

        it('allows accountant to view any invoices', function (): void {
            $accountant = User::factory()->create(['role' => UserRole::Accountant]);
            $policy = new InvoicePolicy();

            expect($policy->viewAny($accountant))->toBeTrue();
        });
    });

    describe('view', function (): void {
        it('allows users to view a specific invoice', function (): void {
            $user = User::factory()->create();
            $invoice = Invoice::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->view($user, $invoice))->toBeTrue();
        });

        it('allows admin to view any specific invoice', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $invoice = Invoice::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->view($admin, $invoice))->toBeTrue();
        });
    });

    describe('create', function (): void {
        it('allows users to create invoices', function (): void {
            $user = User::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->create($user))->toBeTrue();
        });

        it('allows admin to create invoices', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $policy = new InvoicePolicy();

            expect($policy->create($admin))->toBeTrue();
        });

        it('allows accountant to create invoices', function (): void {
            $accountant = User::factory()->create(['role' => UserRole::Accountant]);
            $policy = new InvoicePolicy();

            expect($policy->create($accountant))->toBeTrue();
        });
    });

    describe('update', function (): void {
        it('allows users to update invoices', function (): void {
            $user = User::factory()->create();
            $invoice = Invoice::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->update($user, $invoice))->toBeTrue();
        });
    });

    describe('delete', function (): void {
        it('allows users to delete invoices', function (): void {
            $user = User::factory()->create();
            $invoice = Invoice::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->delete($user, $invoice))->toBeTrue();
        });
    });

    describe('send', function (): void {
        it('allows users to send invoices', function (): void {
            $user = User::factory()->create();
            $invoice = Invoice::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->send($user, $invoice))->toBeTrue();
        });
    });

    describe('cancel', function (): void {
        it('allows users to cancel invoices', function (): void {
            $user = User::factory()->create();
            $invoice = Invoice::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->cancel($user, $invoice))->toBeTrue();
        });
    });
});
