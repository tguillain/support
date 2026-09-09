<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Models\Attachment;
use Illuminate\Support\Facades\Storage;

/**
 * Removes an attachment and the file behind it.
 *
 * The row goes last: a deleted row pointing at a file still on disk leaks
 * storage silently, whereas a failed delete leaves the pair consistent.
 */
class DetachTicketAttachment
{
    public function __invoke(Attachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);

        $attachment->delete();
    }
}
