<?php

namespace Functional\Tickets\Database\Seeders;

use App\Models\User;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketsSeeder extends Seeder
{
    public function run(): void
    {
        $requesters = User::factory()->count(5)->create();
        $technicians = User::factory()->count(3)->create();

        foreach (TicketStatus::cases() as $status) {
            foreach (TicketPriority::cases() as $priority) {
                Ticket::factory()->create([
                    'requester_id' => $requesters->random()->id,
                    'assigned_technician_id' => $status === TicketStatus::Open
                        ? null
                        : $technicians->random()->id,
                    'status' => $status,
                    'priority' => $priority,
                    'resolved_at' => in_array($status, [TicketStatus::Resolved, TicketStatus::Closed])
                        ? now()
                        : null,
                ]);
            }
        }

        $this->seedDemoProfileTickets();
    }

    /**
     * Guarantee that each demo profile owns rows inside its own perimeter, so
     * the scoping is observable without hunting for a matching ticket.
     */
    private function seedDemoProfileTickets(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);
        $technician = User::firstWhere('email', TicketsAccessSeeder::DEMO_TECHNICIAN_EMAIL);

        if ($requester === null || $technician === null) {
            return;
        }

        Ticket::factory()->count(3)->create(['requester_id' => $requester->getKey()]);
        Ticket::factory()->count(4)->create(['assigned_technician_id' => $technician->getKey()]);
    }
}
