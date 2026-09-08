<?php

namespace Functional\Tickets\Database\Factories;

use App\Models\User;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'author_id' => User::factory(),
            'body' => faker()->paragraphs(2),
        ];
    }

    public function authoredBy(?User $author = null): static
    {
        return $this->state(fn (): array => [
            'author_id' => $author ?? User::factory(),
        ]);
    }

    public function onTicket(Ticket $ticket): static
    {
        return $this->state(fn (): array => [
            'ticket_id' => $ticket->getKey(),
            'author_id' => $ticket->requester_id,
        ]);
    }
}
