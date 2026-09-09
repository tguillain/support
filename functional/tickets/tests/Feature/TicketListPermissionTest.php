<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Livewire\TicketList;
use Functional\Tickets\Models\Ticket;
use Livewire\Livewire;

class TicketListPermissionTest extends TicketListTestCase
{
    public function test_a_requester_only_sees_their_own_tickets(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);

        Ticket::factory()->count(3)->create(['requester_id' => $requester->getKey()]);
        Ticket::factory()->count(7)->create();

        Livewire::actingAs($requester)
            ->test(TicketList::class)
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->total() === 3);
    }

    public function test_a_technician_only_sees_the_tickets_assigned_to_them(): void
    {
        $technician = User::firstWhere('email', TicketsAccessSeeder::DEMO_TECHNICIAN_EMAIL);

        Ticket::factory()->count(4)->create(['assigned_technician_id' => $technician->getKey()]);
        Ticket::factory()->count(6)->create();

        Livewire::actingAs($technician)
            ->test(TicketList::class)
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->total() === 4);
    }

    public function test_a_manager_sees_every_ticket(): void
    {
        Ticket::factory()->count(9)->create();

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->total() === 9);
    }

    public function test_the_create_button_is_only_offered_to_whoever_may_create(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);
        $technician = User::firstWhere('email', TicketsAccessSeeder::DEMO_TECHNICIAN_EMAIL);

        Livewire::actingAs($requester)
            ->test(TicketList::class)
            ->assertSee(__('tickets::list.create'));

        /** The manager and the technician hold no "create tickets" permission. */
        foreach ([$technician, $this->manager] as $user) {
            Livewire::actingAs($user)
                ->test(TicketList::class)
                ->assertDontSee(__('tickets::list.create'));
        }
    }

    public function test_the_edit_link_is_only_offered_to_whoever_may_update(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->getKey()]);

        $label = __('tickets::list.edit', ['title' => $ticket->title]);

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->assertSee($label, escape: false);

        /** A requester may read their own ticket but never write to it. */
        Livewire::actingAs($requester)
            ->test(TicketList::class)
            ->assertSee($ticket->title)
            ->assertDontSee($label, escape: false);
    }
}
