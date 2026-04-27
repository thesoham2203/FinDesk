<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

final class AttachmentPolicy
{
    /**
     * Determine if the user can view the attachment.
     *
     * Users can download attachments if they can view the parent attachable model.
     * For example, an attachment on an Expense can be viewed if the user can view that Expense.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        // Get the parent model (e.g., Expense, InvoiceLineItem)
        $parent = $attachment->attachable;

        if ($parent === null) {
            return false;
        }

        // Check if the user can view the parent model using its policy
        return $user->can('view', $parent);
    }
}
