<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;

class TicketsApiSearchTest extends TicketsApiTestCase
{
    public function test_it_requires_authentication(): void
    {
        auth()->forgetGuards();

        $this->postJson(self::SEARCH)->assertUnauthorized();
    }

    public function test_it_returns_a_paginated_list(): void
    {
        Ticket::factory()->count(30)->create();

        $response = $this->postJson(self::SEARCH, ['search' => ['limit' => 10, 'page' => 2]]);

        $response->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('total', 30)
            ->assertJsonCount(10, 'data');
    }

    public function test_it_filters_on_a_given_status(): void
    {
        Ticket::factory()->count(4)->create(['status' => TicketStatus::Open]);
        Ticket::factory()->count(6)->create(['status' => TicketStatus::Closed]);

        $response = $this->postJson(self::SEARCH, [
            'search' => [
                'filters' => [
                    ['field' => 'status', 'operator' => '=', 'value' => TicketStatus::Closed->value],
                ],
            ],
        ]);

        $response->assertOk()->assertJsonPath('total', 6);

        foreach ($response->json('data') as $ticket) {
            $this->assertSame(TicketStatus::Closed->value, $ticket['status']);
        }
    }

    public function test_it_sorts_by_creation_date_descending(): void
    {
        Ticket::factory()->create(['created_at' => now()->subDays(3)]);
        Ticket::factory()->create(['created_at' => now()->subDay()]);
        Ticket::factory()->create(['created_at' => now()->subDays(2)]);

        $response = $this->postJson(self::SEARCH, [
            'search' => ['sorts' => [['field' => 'created_at', 'direction' => 'desc']]],
        ]);

        $dates = array_column($response->assertOk()->json('data'), 'created_at');

        $sorted = $dates;
        rsort($sorted);

        $this->assertSame($sorted, $dates);
    }

    public function test_it_loads_the_requester_in_the_response(): void
    {
        $requester = User::factory()->create(['name' => 'Ada Lovelace']);
        Ticket::factory()->create(['requester_id' => $requester->getKey()]);

        $response = $this->postJson(self::SEARCH, [
            'search' => ['includes' => [['relation' => 'requester']]],
        ]);

        $response->assertOk()->assertJsonPath('data.0.requester.name', 'Ada Lovelace');
    }

    public function test_it_loads_the_comments_in_the_response(): void
    {
        $ticket = Ticket::factory()->create();
        Comment::factory()->count(3)->create(['ticket_id' => $ticket->getKey()]);

        $response = $this->postJson(self::SEARCH, [
            'search' => ['includes' => [['relation' => 'comments']]],
        ]);

        $response->assertOk()->assertJsonCount(3, 'data.0.comments');
    }

    public function test_it_searches_the_title_through_the_instruction(): void
    {
        Ticket::factory()->create(['title' => 'Imprimante hors service']);
        Ticket::factory()->create(['title' => 'Accès VPN refusé']);

        $response = $this->postJson(self::SEARCH, [
            'search' => [
                'instructions' => [
                    [
                        'name' => 'search-title',
                        'fields' => [['name' => 'value', 'value' => 'imprimante']],
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.title', 'Imprimante hors service');
    }

    public function test_it_rejects_a_filter_on_a_field_that_is_not_queryable(): void
    {
        $this->postJson(self::SEARCH, [
            'search' => ['filters' => [['field' => 'title', 'operator' => 'like', 'value' => '%vpn%']]],
        ])->assertUnprocessable()->assertJsonValidationErrors('search.filters');
    }

    public function test_it_rejects_a_sort_on_a_field_that_is_not_queryable(): void
    {
        $this->postJson(self::SEARCH, [
            'search' => ['sorts' => [['field' => 'title', 'direction' => 'asc']]],
        ])->assertUnprocessable()->assertJsonValidationErrors('search.sorts');
    }

    public function test_it_rejects_a_filter_hidden_inside_a_nested_group(): void
    {
        $this->postJson(self::SEARCH, [
            'search' => [
                'filters' => [
                    ['nested' => [['field' => 'description', 'operator' => 'like', 'value' => '%x%']]],
                ],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('search.filters');
    }

    public function test_it_never_exposes_internal_fields(): void
    {
        Ticket::factory()->create();

        $ticket = $this->postJson(self::SEARCH)->assertOk()->json('data.0');

        $keys = array_keys($ticket);
        sort($keys);

        $this->assertSame(
            ['created_at', 'description', 'id', 'priority', 'resolved_at', 'status', 'title'],
            $keys,
        );
    }

    public function test_it_describes_its_own_schema(): void
    {
        $response = $this->getJson('/api/v1/tickets')->assertOk();

        $this->assertSame(
            ['id', 'title', 'description', 'status', 'priority', 'created_at', 'resolved_at'],
            $response->json('data.fields'),
        );
        $this->assertSame(
            ['requester', 'assignedTechnician', 'comments'],
            array_column($response->json('data.relations'), 'relation'),
        );
        $this->assertSame(
            ['search-title'],
            array_column($response->json('data.instructions'), 'uriKey'),
        );
    }
}
