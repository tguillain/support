<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Livewire\TicketList;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class TicketListFilterSortTest extends TicketListTestCase
{
    public function test_it_filters_on_status(): void
    {
        Ticket::factory()->count(2)->create(['status' => TicketStatus::Open]);
        Ticket::factory()->count(4)->create(['status' => TicketStatus::Closed]);

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->set('status', TicketStatus::Closed->value)
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->total() === 4);
    }

    public function test_it_filters_on_priority(): void
    {
        Ticket::factory()->count(3)->create(['priority' => TicketPriority::Low]);
        Ticket::factory()->count(1)->create(['priority' => TicketPriority::Critical]);

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->set('priority', TicketPriority::Critical->value)
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->total() === 1);
    }

    public function test_changing_a_filter_returns_to_the_first_page(): void
    {
        Ticket::factory()->count(30)->create(['status' => TicketStatus::Open]);

        /** WithPagination keeps the page in $paginators, not in a $page property. */
        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('status', TicketStatus::Open->value)
            ->assertSet('paginators.page', 1);
    }

    public function test_changing_the_priority_filter_also_returns_to_the_first_page(): void
    {
        Ticket::factory()->count(30)->create(['priority' => TicketPriority::Low]);

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('priority', TicketPriority::Low->value)
            ->assertSet('paginators.page', 1);
    }

    public function test_clicking_the_same_column_twice_inverts_the_direction(): void
    {
        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->call('sortBy', 'title')
            ->assertSet('sortColumn', 'title')
            ->assertSet('sortDirection', 'asc')
            ->call('sortBy', 'title')
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'title')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_switching_column_restarts_ascending(): void
    {
        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->call('sortBy', 'title')
            ->call('sortBy', 'title')
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'priority')
            ->assertSet('sortColumn', 'priority')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_the_sort_actually_orders_the_rows(): void
    {
        Ticket::factory()->create(['title' => 'C dernier']);
        Ticket::factory()->create(['title' => 'A premier']);
        Ticket::factory()->create(['title' => 'B milieu']);

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->call('sortBy', 'title')
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->pluck('title')->all() === ['A premier', 'B milieu', 'C dernier']);
    }

    public function test_it_sorts_on_the_comment_count(): void
    {
        $few = Ticket::factory()->create();
        $many = Ticket::factory()->create();
        Comment::factory()->count(1)->create(['ticket_id' => $few->getKey()]);
        Comment::factory()->count(5)->create(['ticket_id' => $many->getKey()]);

        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->call('sortBy', 'comments_count')
            ->call('sortBy', 'comments_count')
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->first()->is($many));
    }

    public function test_an_arbitrary_column_name_is_ignored_by_the_action(): void
    {
        Livewire::actingAs($this->manager)
            ->test(TicketList::class)
            ->call('sortBy', 'password')
            ->assertSet('sortColumn', 'created_at')
            ->call('sortBy', 'users.email')
            ->assertSet('sortColumn', 'created_at');
    }

    public function test_an_arbitrary_column_name_never_reaches_the_query(): void
    {
        Ticket::factory()->count(2)->create();

        $statements = [];
        DB::listen(function ($query) use (&$statements): void {
            $statements[] = $query->sql;
        });

        /** The property is bound to the URL, so it can arrive already tampered with. */
        Livewire::actingAs($this->manager)
            ->test(TicketList::class, ['sortColumn' => 'password'])
            ->assertOk();

        foreach ($statements as $sql) {
            $this->assertStringNotContainsString('password', $sql);
        }

        $this->assertNotEmpty(array_filter(
            $statements,
            fn (string $sql): bool => str_contains($sql, 'order by') && str_contains($sql, 'created_at'),
        ));
    }
}
