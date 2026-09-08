<?php

namespace Functional\Tickets\Access\Perimeters;

use Lomkit\Access\Perimeters\OverlayPerimeter;

/**
 * Tickets assigned to the user. An overlay so that someone who both handles
 * and opens tickets sees the union of the two sets rather than whichever
 * perimeter happens to be declared first.
 */
class AssignedTicketsPerimeter extends OverlayPerimeter
{
    //
}
