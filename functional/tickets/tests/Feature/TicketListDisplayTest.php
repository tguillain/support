<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Livewire\TicketList;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

class TicketListDisplayTest extends TicketListTestCase
{
    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);

        parent::tearDown();
    }

    public function test_the_page_requires_authentication(): void
    {
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
    }

    public function test_it_renders_the_page_for_an_authenticated_user(): void
    {
        Ticket::factory()->create(['title' => 'Imprimante hors service']);

        $this->actingAs($this->manager)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('wire:name="tickets.ticket-list"', escape: false)
            ->assertSee('Imprimante hors service');
    }

    public function test_it_paginates_twenty_five_per_page(): void
    {
        Ticket::factory()->count(30)->create();

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->perPage() === 25
                && $tickets->total() === 30
                && $tickets->count() === 25);
    }

    public function test_it_shows_every_specified_column(): void
    {
        $requester = User::factory()->create(['name' => 'Ada Lovelace']);
        $technician = User::factory()->create(['name' => 'Grace Hopper']);

        $ticket = Ticket::factory()->create([
            'title' => 'Écran noir',
            'requester_id' => $requester->getKey(),
            'assigned_technician_id' => $technician->getKey(),
            'status' => TicketStatus::InProgress,
            'priority' => TicketPriority::Critical,
        ]);
        Comment::factory()->count(3)->create(['ticket_id' => $ticket->getKey()]);

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->assertSee('Écran noir')
            ->assertSee('Ada Lovelace')
            ->assertSee('Grace Hopper')
            ->assertSee(TicketStatus::InProgress->label())
            ->assertSee(TicketPriority::Critical->label())
            ->assertSee($ticket->created_at->isoFormat('LL'));
    }

    public function test_it_displays_the_comment_count_from_an_aggregate(): void
    {
        $ticket = Ticket::factory()->create();
        Comment::factory()->count(3)->create(['ticket_id' => $ticket->getKey()]);

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->first()->comments_count === 3);
    }

    public function test_it_loads_every_relation_the_row_reads(): void
    {
        /**
         * Turns a lazy load into an exception, so dropping an eager load from
         * the query fails here instead of merely costing queries.
         */
        Model::preventLazyLoading();

        $ticket = Ticket::factory()->create(['assigned_technician_id' => User::factory()->create()->getKey()]);
        Comment::factory()->count(2)->create(['ticket_id' => $ticket->getKey()]);

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->assertOk();
    }

    public function test_the_query_count_does_not_grow_with_the_number_of_rows(): void
    {
        Ticket::factory()->count(5)->create()
            ->each(fn (Ticket $ticket) => Comment::factory()->count(2)->create(['ticket_id' => $ticket->getKey()]));

        $withFive = $this->countQueriesRenderingTheList();

        Ticket::factory()->count(20)->create()
            ->each(fn (Ticket $ticket) => Comment::factory()->count(2)->create(['ticket_id' => $ticket->getKey()]));

        $withTwentyFive = $this->countQueriesRenderingTheList();

        $this->assertSame(
            $withFive,
            $withTwentyFive,
            sprintf('5 rows took %d queries, 25 rows took %d — the list is N+1.', $withFive, $withTwentyFive),
        );
    }

    public function test_the_empty_state_is_translated(): void
    {
        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->assertSee(__('tickets::list.empty'));
    }
}
