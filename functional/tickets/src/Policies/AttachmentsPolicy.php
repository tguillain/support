<?php

namespace Functional\Tickets\Policies;

use App\Models\User;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Gate;

/**
 * An attachment carries no access rule of its own: it is readable by whoever
 * may read its ticket, and writable by whoever may write to it. Delegating
 * keeps the ticket perimeters the single source of truth.
 */
class AttachmentsPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', Ticket::class);
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return Gate::forUser($user)->allows('view', $attachment->ticket);
    }

    /**
     * Refused outright: this ability carries no ticket to authorize against,
     * and attaching is gated on write access to a specific ticket. The upload
     * path checks `update` on that ticket instead, so answering anything else
     * here would be a second, looser rule for the same operation.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Attachment $attachment): bool
    {
        return Gate::forUser($user)->allows('update', $attachment->ticket);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return Gate::forUser($user)->allows('update', $attachment->ticket);
    }
}
