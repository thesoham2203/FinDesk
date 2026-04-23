<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use App\Policies\InvoicePolicy;

describe('InvoicePolicy', function (): void {
    describe('viewAny', function (): void {
        it('allows accountant to view any invoices', function (): void {
            $accountant = User::factory()->create(['role' => UserRole::Accountant]);
            $policy = new InvoicePolicy();

            expect($policy->viewAny($accountant))->toBeTrue();
        });

        it('allows admin to view any invoices', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $policy = new InvoicePolicy();

            expect($policy->viewAny($admin))->toBeTrue();
        });

        it('denies employees from viewing any invoices', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $policy = new InvoicePolicy();

            expect($policy->viewAny($employee))->toBeFalse();
        });
    });

    describe('view', function (): void {
        it('allows admin to view any specific invoice', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $invoice = Invoice::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->view($admin, $invoice))->toBeTrue();
        });

        it('allows creator to view their own invoice', function (): void {
            $user = User::factory()->create(['role' => UserRole::Employee]);
            $invoice = Invoice::factory()->create(['created_by' => $user->id]);
            $policy = new InvoicePolicy();

            expect($policy->view($user, $invoice))->toBeTrue();
        });

        it('denies other users from viewing specific invoice', function (): void {
            $user = User::factory()->create(['role' => UserRole::Employee]);
            $invoice = Invoice::factory()->create();
            $policy = new InvoicePolicy();

            expect($policy->view($user, $invoice))->toBeFalse();
        });
    });

    describe('create', function (): void {
        it('allows accountant to create invoices', function (): void {
            $accountant = User::factory()->create(['role' => UserRole::Accountant]);
            $policy = new InvoicePolicy();

            expect($policy->create($accountant))->toBeTrue();
        });

        it('allows admin to create invoices', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $policy = new InvoicePolicy();

            expect($policy->create($admin))->toBeTrue();
        });

        it('denies employees from creating invoices', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $policy = new InvoicePolicy();

            expect($policy->create($employee))->toBeFalse();
        });
    });

    describe('update', function (): void {
        it('allows accountant to update draft invoices', function (): void {
            $user = User::factory()->create(['role' => UserRole::Accountant]);
            $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);
            $policy = new InvoicePolicy();

            expect($policy->update($user, $invoice))->toBeTrue();
        });

        it('allows creator to update their own draft invoice', function (): void {
            $user = User::factory()->create(['role' => UserRole::Employee]);
            $invoice = Invoice::factory()->create([
                'status' => InvoiceStatus::Draft,
                'created_by' => $user->id,
            ]);
            $policy = new InvoicePolicy();

            expect($policy->update($user, $invoice))->toBeTrue();
        });

        it('denies updating non-draft invoices', function (): void {
            $user = User::factory()->create(['role' => UserRole::Admin]);
            $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
            $policy = new InvoicePolicy();

            expect($policy->update($user, $invoice))->toBeFalse();
        });
    });

    describe('delete', function (): void {
        it('allows admin to delete draft invoices', function (): void {
            $user = User::factory()->create(['role' => UserRole::Admin]);
            $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);
            $policy = new InvoicePolicy();

            expect($policy->delete($user, $invoice))->toBeTrue();
        });

        it('denies deleting non-draft invoices', function (): void {
            $user = User::factory()->create(['role' => UserRole::Admin]);
            $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);
            $policy = new InvoicePolicy();

            expect($policy->delete($user, $invoice))->toBeFalse();
        });
    });

    describe('send', function (): void {
        it('allows accountant to send draft invoices', function (): void {
            $user = User::factory()->create(['role' => UserRole::Accountant]);
            $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);
            $policy = new InvoicePolicy();

            expect($policy->send($user, $invoice))->toBeTrue();
        });

        it('denies sending non-draft invoices', function (): void {
            $user = User::factory()->create(['role' => UserRole::Admin]);
            $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
            $policy = new InvoicePolicy();

            expect($policy->send($user, $invoice))->toBeFalse();
        });
    });

    describe('cancel', function (): void {
        it('allows manager to cancel sent invoices', function (): void {
            $user = User::factory()->create(['role' => UserRole::Manager]);
            $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
            $policy = new InvoicePolicy();

            expect($policy->cancel($user, $invoice))->toBeTrue();
        });

        it('denies cancelling paid invoices', function (): void {
            $user = User::factory()->create(['role' => UserRole::Admin]);
            $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);
            $policy = new InvoicePolicy();

            expect($policy->cancel($user, $invoice))->toBeFalse();
        });
    });

    describe('recordPayment', function (): void {
        it('allows admin and accountant to record payments on payable invoices', function (): void {
            $policy = new InvoicePolicy();

            expect($policy->recordPayment(
                User::factory()->create(['role' => UserRole::Admin]),
                Invoice::factory()->create(['status' => InvoiceStatus::Sent]),
            ))->toBeTrue();

            expect($policy->recordPayment(
                User::factory()->create(['role' => UserRole::Accountant]),
                Invoice::factory()->create(['status' => InvoiceStatus::Overdue]),
            ))->toBeTrue();
        });

        it('denies non-privileged roles from recording payments', function (): void {
            $policy = new InvoicePolicy();

            expect($policy->recordPayment(
                User::factory()->create(['role' => UserRole::Manager]),
                Invoice::factory()->create(['status' => InvoiceStatus::Sent]),
            ))->toBeFalse();
        });

        it('denies payment recording for ineligible invoice statuses', function (): void {
            $policy = new InvoicePolicy();

            expect($policy->recordPayment(
                User::factory()->create(['role' => UserRole::Admin]),
                Invoice::factory()->create(['status' => InvoiceStatus::Draft]),
            ))->toBeFalse();
        });
    });
});
