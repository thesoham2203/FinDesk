<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttachmentController
{
    /**
     * Serve a private attachment file as a streamed download.
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        // Authorize using policy: This calls AttachmentPolicy::view().
        // If the user cannot see the ticket, they cannot download its attachments.
        // Gate::authorize() throws a 403 automatically on failure.
        Gate::authorize('view', $attachment);

        // The DB record exists but the file might have been manually deleted.
        // Always check before serving to avoid internal server errors.
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404, 'File not found on disk.');

        // download() streams the file through Laravel with the original filename as the download name.
        // The real storage path is never exposed to the browser.
        return Storage::disk($attachment->disk)
            ->download(
                $attachment->path,
                $attachment->original_name,
                ['Content-Type' => $attachment->mime_type]
            );
    }
}
