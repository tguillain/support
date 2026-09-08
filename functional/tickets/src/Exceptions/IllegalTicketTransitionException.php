<?php

namespace Functional\Tickets\Exceptions;

use DomainException;
use Functional\Tickets\Enums\TicketStatus;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Raised when a caller asks for a transition the lifecycle does not allow.
 *
 * Implementing HttpExceptionInterface is what lets Laravel's handler render
 * this as a 409 on its own: refusing a transition is a conflict with the
 * ticket's current state, and no action or controller writes that response.
 */
class IllegalTicketTransitionException extends DomainException implements HttpExceptionInterface
{
    private function __construct(
        private readonly TicketStatus $from,
        private readonly TicketStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(TicketStatus $from, TicketStatus $to): self
    {
        return new self($from, $to, sprintf(
            'A ticket cannot move from [%s] to [%s]. Allowed from [%s]: [%s].',
            $from->value,
            $to->value,
            $from->value,
            implode(', ', array_map(
                static fn (TicketStatus $status): string => $status->value,
                $from->allowedTransitions(),
            )) ?: 'none',
        ));
    }

    public function from(): TicketStatus
    {
        return $this->from;
    }

    public function to(): TicketStatus
    {
        return $this->to;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];
    }
}
