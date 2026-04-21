<?php

declare(strict_types=1);

/**
 * InvoicePolicy
 *
 * WHAT: Determines who can view, create, update, delete, send, cancel, or record payments on invoices.
 *
 * WHY: Invoices are created by accountants/managers and managed through a workflow (Draft → Sent → Paid).
 *      Different roles have different permissions at each stage. Policies enforce these rules.
 *
 * IMPLEMENT: Each method checks user role and invoice status. Draft invoices can be edited/deleted.
 *            Sent/Paid invoices are read-only or payment-only. Implement authorization per method docstring.
 *
 * REFERENCE:
 * - Laravel Policies: https://laravel.com/docs/13.x/authorization#creating-policies
 * - Invoice Workflow: See InvoiceStatus enum for available statuses
 */

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
        // Most roles can view invoice lists (Managers, Accountants, Admin)
        // Return true for most roles
        return true;
    }

    /**
     * Determine if the user can view a specific invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Admin can view all
        if ($user->role->value === UserRole::Admin->value) {
            return true;
        }
        // Accountant and Manager can view all (they create/manage them)
        if ($user->role->value === UserRole::Accountant->value || $user->role->value === UserRole::Manager->value) {
            return true;
        }
        // Employees typically cannot view invoices (or only their own client invoices)
        if ($user->role->value === UserRole::Employee->value) {
            return false;
        }

        // Return true/false based on role
        return false;
    }

    /**
     * Determine if the user can create invoices.
     */
    public function create(User $user): bool
    {
        // Only Admin, Manager, and Accountant can create invoices
        if ($user->role->value === UserRole::Admin->value || $user->role->value === UserRole::Manager->value || $user->role->value === UserRole::Accountant->value) {
            return true;
        }
        // Employees cannot create invoices
        if ($user->role->value === UserRole::Employee->value) {
            return false;
        }

        // Return true only for those roles
        return false;
    }

    /**
     * Determine if the user can update an invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Only when invoice status is Draft
        if ($invoice->status !== InvoiceStatus::Draft) {
            return true;
        }
        // Only Admin and Accountant (or the creator)
        if ($user->role->value === UserRole::Admin->value || $user->role->value === UserRole::Accountant->value) {
            return true;
        }
        // Return false if status is not Draft
        if ($invoice->status !== InvoiceStatus::Draft) {
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can delete an invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Only when invoice status is Draft
        if ($invoice->status !== InvoiceStatus::Draft) {
            return true;
        }
        // Only Admin or Accountant
        if ($user->role->value === UserRole::Admin->value || $user->role->value === UserRole::Accountant->value) {
            return true;
        }
        // Return false if status is not Draft
        if ($invoice->status !== InvoiceStatus::Draft) {
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can send an invoice.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        // Only when invoice status is Draft
        if ($invoice->status !== InvoiceStatus::Draft) {
            return true;
        }
        // Only Admin, Manager, or Accountant
        if ($user->role->value === UserRole::Admin->value || $user->role->value === UserRole::Manager->value || $user->role->value === UserRole::Accountant->value) {
            return true;
        }
        // Return false if status is not Draft
        if ($invoice->status !== InvoiceStatus::Draft) {
            return false;
        }

        return false;

    }

    /**
     * Determine if the user can cancel an invoice.
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        // Only when status is Draft or Sent
        if ($invoice->status !== InvoiceStatus::Draft && $invoice->status !== InvoiceStatus::Sent) {
            return true;
        }
        // Only Admin or Accountant
        if ($user->role->value === UserRole::Admin->value || $user->role->value === UserRole::Accountant->value) {
            return true;
        }
        // Return false if status is Paid or Cancelled already
        if ($invoice->status === InvoiceStatus::Paid || $invoice->status === InvoiceStatus::Cancelled) {
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can record a payment on an invoice.
     */
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return ($user->role->value === UserRole::Admin->value || $user->role->value === UserRole::Accountant->value)
            && ($invoice->status === InvoiceStatus::Sent
                || $invoice->status === InvoiceStatus::Viewed
                || $invoice->status === InvoiceStatus::PartiallyPaid
                || $invoice->status === InvoiceStatus::Overdue);
    }
}
