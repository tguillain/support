<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Models\Attachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves the bytes of one attachment.
 *
 * A plain controller rather than a Resource: it returns a file, not a
 * projection of a model, so there is nothing for a Resource to declare. The
 * disk is private, which is why every download passes the policy first.
 */
class TicketAttachmentDownloadController
{
    public function __invoke(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
        );
    }
}
