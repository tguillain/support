<?php

namespace Functional\Tickets\Exceptions;

use RuntimeException;

class TicketExportFailedException extends RuntimeException
{
    public static function streamUnavailable(): self
    {
        return new self('Could not open the output stream to write the ticket export.');
    }
}
