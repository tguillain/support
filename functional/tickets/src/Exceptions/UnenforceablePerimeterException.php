<?php

namespace Functional\Tickets\Exceptions;

use RuntimeException;

/**
 * Raised when the ticket perimeter cannot be applied to a query.
 *
 * It refuses rather than returning the query untouched: an unrestricted read
 * would leak every ticket, so a perimeter that cannot be enforced has to stop
 * the request instead of quietly widening it.
 */
class UnenforceablePerimeterException extends RuntimeException
{
    public static function forBuilder(string $builderClass): self
    {
        return new self(sprintf(
            'The ticket perimeter needs an Eloquent builder to constrain, got [%s].',
            $builderClass,
        ));
    }

    public static function forMissingActor(): self
    {
        return new self('The ticket perimeter needs an authenticated user to resolve.');
    }
}
