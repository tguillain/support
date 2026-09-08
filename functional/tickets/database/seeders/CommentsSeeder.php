<?php

namespace Functional\Tickets\Database\Seeders;

use App\Models\User;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Seeder;

class CommentsSeeder extends Seeder
{
    public function run(): void
    {
        $authors = User::all();

        if ($authors->isEmpty()) {
            $authors = User::factory()->count(3)->create();
        }

        $tickets = Ticket::all();

        foreach ($tickets as $ticket) {
            Comment::factory()->count(2)->create([
                'ticket_id' => $ticket->id,
                'author_id' => $authors->random()->id,
            ]);
        }
    }
}
