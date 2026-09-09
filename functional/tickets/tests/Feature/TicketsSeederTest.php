<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\CommentsSeeder;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Database\Seeders\TicketsSeeder;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The seeders ship with the feature, so they are covered like the feature.
 */
class TicketsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_ticket_seeder_covers_every_status_and_priority(): void
    {
        $this->seed(TicketsAccessSeeder::class);
        $this->seed(TicketsSeeder::class);

        foreach (TicketStatus::cases() as $status) {
            foreach (TicketPriority::cases() as $priority) {
                $this->assertDatabaseHas('tickets', [
                    'status' => $status->value,
                    'priority' => $priority->value,
                ]);
            }
        }
    }

    public function test_the_ticket_seeder_gives_each_demo_profile_rows_in_its_own_perimeter(): void
    {
        $this->seed(TicketsAccessSeeder::class);
        $this->seed(TicketsSeeder::class);

        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);
        $technician = User::firstWhere('email', TicketsAccessSeeder::DEMO_TECHNICIAN_EMAIL);

        $this->assertSame(3, Ticket::where('requester_id', $requester->getKey())->count());
        $this->assertSame(4, Ticket::where('assigned_technician_id', $technician->getKey())->count());
    }

    public function test_the_comment_seeder_gives_every_ticket_two_comments(): void
    {
        $this->seed(TicketsAccessSeeder::class);
        $this->seed(TicketsSeeder::class);
        $this->seed(CommentsSeeder::class);

        $this->assertSame(Ticket::count() * 2, Comment::count());

        foreach (Ticket::all() as $ticket) {
            $this->assertSame(2, $ticket->comments()->count(), 'ticket '.$ticket->getKey());
        }
    }

    public function test_the_comment_seeder_provisions_authors_when_run_alone(): void
    {
        $this->seed(TicketsAccessSeeder::class);
        $this->seed(CommentsSeeder::class);

        /** No tickets, so no comments — but the seeder must not blow up. */
        $this->assertSame(0, Comment::count());
    }

    public function test_a_seeded_comment_exposes_its_relations(): void
    {
        $this->seed(TicketsAccessSeeder::class);
        $this->seed(TicketsSeeder::class);
        $this->seed(CommentsSeeder::class);

        $comment = Comment::query()->firstOrFail();

        $this->assertTrue($comment->ticket->is(Ticket::findOrFail($comment->ticket_id)));
        $this->assertSame($comment->author_id, $comment->author->getKey());
    }
}
