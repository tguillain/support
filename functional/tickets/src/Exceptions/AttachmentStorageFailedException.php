<?php

namespace Functional\Tickets\Exceptions;

use RuntimeException;

/**
 * Raised when a file reaches the domain but cannot be written to its disk.
 *
 * It refuses rather than recording a row: an attachment whose bytes are not on
 * the disk is a broken download link waiting to happen.
 */
class AttachmentStorageFailedException extends RuntimeException
{
    public static function forFile(string $originalName): self
    {
        return new self(sprintf('Could not store the attachment [%s] on its disk.', $originalName));
    }
}
