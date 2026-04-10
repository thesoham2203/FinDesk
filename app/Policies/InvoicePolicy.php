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

use App\Models\Invoice;
use App\Models\User;

final class InvoicePolicy
{
    /**
     * Determine if the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        // TODO: Most roles can view invoice lists (Managers, Accountants, Admin)
        // TODO: Return true for most roles
        return true;
    }

    /**
     * Determine if the user can view a specific invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // TODO: Admin can view all
        // TODO: Accountant and Manager can view all (they create/manage them)
        // TODO: Employees typically cannot view invoices (or only their own client invoices)
        // TODO: Return true/false based on role
        return true;
    }

    /**
     * Determine if the user can create invoices.
     */
    public function create(User $user): bool
    {
        // TODO: Only Admin, Manager, and Accountant can create invoices
        // TODO: Employees cannot create invoices
        // TODO: Return true only for those roles
        return true;
    }

    /**
     * Determine if the user can update an invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // TODO: Only when invoice status is Draft
        // TODO: Only Admin and Accountant (or the creator)
        // TODO: Return false if status is not Draft
        return true;
    }

    /**
     * Determine if the user can delete an invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // TODO: Only when invoice status is Draft
        // TODO: Only Admin or Accountant
        // TODO: Return false if status is not Draft
        return true;
    }

    /**
     * Determine if the user can send an invoice.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        // TODO: Only when invoice status is Draft
        // TODO: Only Admin, Manager, or Accountant
        // TODO: Return false if status is not Draft
        return true;
    }

    /**
     * Determine if the user can cancel an invoice.
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        // TODO: Only when status is Draft or Sent
        // TODO: Only Admin or Accountant
        // TODO: Return false if status is Paid or Cancelled already
        return true;
    }

    /**
     * Determine if the user can record a payment on an invoice.
     */
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        // TODO: Only Admin or Accountant
        // TODO: Only when invoice status is Sent, Viewed, PartiallyPaid, or Overdue
        // TODO: Return false for Draft or Cancelled statuses
        return true;
    }
}
