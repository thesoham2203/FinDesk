<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;

final class InvoicePolicy
{
    /**
     * Determine if the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        // Admin, Accountant, and Manager can view all
        return in_array($user->role->value, [UserRole::Admin->value, UserRole::Accountant->value, UserRole::Manager->value], true);
    }

    /**
     * Determine if the user can view a specific invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Admin, Accountant, and Manager can view all
        if (in_array($user->role->value, [UserRole::Admin->value, UserRole::Accountant->value, UserRole::Manager->value], true)) {
            return true;
        }

        // Creator can view their own
        return $invoice->created_by === $user->id;
    }

    /**
     * Determine if the user can create invoices.
     */
    public function create(User $user): bool
    {
        // Only Admin, Manager, and Accountant can create invoices
        return in_array($user->role->value, [UserRole::Admin->value, UserRole::Manager->value, UserRole::Accountant->value], true);
    }

    /**
     * Determine if the user can update an invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Only allow when invoice status is Draft
        if ($invoice->status !== InvoiceStatus::Draft) {
            return false;
        }

        // Only Admin, Accountant, or the creator
        return in_array($user->role->value, [UserRole::Admin->value, UserRole::Accountant->value], true)
            || $invoice->created_by === $user->id;
    }

    /**
     * Determine if the user can delete an invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Only allow when invoice status is Draft
        if ($invoice->status !== InvoiceStatus::Draft) {
            return false;
        }

        // Only Admin, Accountant, or the creator
        return in_array($user->role->value, [UserRole::Admin->value, UserRole::Accountant->value], true)
            || $invoice->created_by === $user->id;
    }

    /**
     * Determine if the user can send an invoice.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        // Only when invoice status is Draft
        if ($invoice->status !== InvoiceStatus::Draft) {
            return false;
        }

        // Only Admin, Manager, or Accountant (or the creator)
        return in_array($user->role->value, [UserRole::Admin->value, UserRole::Manager->value, UserRole::Accountant->value], true)
            || $invoice->created_by === $user->id;
    }

    /**
     * Determine if the user can cancel an invoice.
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        // Only when status is Draft or Sent
        if (! in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Sent], true)) {
            return false;
        }

        // Only Admin, Manager, or Accountant (or the creator)
        return in_array($user->role->value, [UserRole::Admin->value, UserRole::Manager->value, UserRole::Accountant->value], true)
            || $invoice->created_by === $user->id;
    }

    /**
     * Determine if the user can record a payment on an invoice.
     */
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        // Only Admin and Accountant can record payments
        if (! in_array($user->role->value, [UserRole::Admin->value, UserRole::Accountant->value], true)) {
            return false;
        }

        // Can only record payment on payable statuses
        return in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Viewed, InvoiceStatus::PartiallyPaid, InvoiceStatus::Overdue], true);
    }
}
