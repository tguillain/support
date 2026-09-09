<?php

namespace Functional\Tickets\Actions;

use App\Models\User;
use Functional\Tickets\Exceptions\AttachmentStorageFailedException;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Http\UploadedFile;

/**
 * Stores an uploaded file and records it against a ticket.
 *
 * The bytes land first: the row is only written once the disk confirms the
 * write, so a listed attachment always has a file behind it.
 */
class AttachFileToTicket
{
    private const DISK = 'local';

    private const DIRECTORY = 'ticket-attachments';

    /**
     * @throws AttachmentStorageFailedException
     */
    public function __invoke(Ticket $ticket, UploadedFile $file, User $uploader): Attachment
    {
        $path = $file->store(self::DIRECTORY.'/'.$ticket->getKey(), self::DISK);
        $size = $file->getSize();

        /** Both answer false when the upload could not be read. */
        if ($path === false || $size === false) {
            throw AttachmentStorageFailedException::forFile($file->getClientOriginalName());
        }

        return Attachment::create([
            'ticket_id' => $ticket->getKey(),
            'uploader_id' => $uploader->getKey(),
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $size,
        ]);
    }
}
