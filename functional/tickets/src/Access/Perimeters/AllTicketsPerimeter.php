<?php

namespace Functional\Tickets\Access\Perimeters;

use Lomkit\Access\Perimeters\Perimeter;

/**
 * Unrestricted perimeter. Declared first and non-overlay, so a holder of the
 * "view all tickets" permission short-circuits the narrower perimeters instead
 * of being OR-ed with them.
 */
class AllTicketsPerimeter extends Perimeter
{
    //
}
