<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lomkit\Rest\Http\Requests\DestroyRequest;
use Lomkit\Rest\Http\Requests\MutateRequest;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Requests\SearchRequest;
use Tests\TestCase;

class TicketsApiTest extends TestCase
{
    use RefreshDatabase;

    private const SEARCH = '/api/v1/tickets/search';

    private const MUTATE = '/api/v1/tickets/mutate';

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TicketsAccessSeeder::class);

        $this->actor = $this->actingAsProfile('manager');
    }

    /**
     * The perimeters read permissions, and permissions are carried by roles,
     * so an actor needs a role assigned. Nothing in the code under test ever
     * looks at the role name — only at the permissions it groups.
     */
    private function actingAsProfile(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    /**
     * The package registers every Rest request class as a container singleton
     * (RestServiceProvider::register). A real HTTP request gets a fresh
     * container so that is harmless in production, but a feature test reuses
     * one application across calls: without this reset, a second call to the
     * same endpoint replays the first payload.
     */
    private function forgetRestRequests(): void
    {
        foreach ([RestRequest::class, SearchRequest::class, MutateRequest::class, DestroyRequest::class] as $request) {
            $this->app->forgetInstance($request);
        }
    }

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

    public function test_it_creates_updates_and_deletes_a_ticket(): void
    {
        $requester = $this->actingAsProfile('requester');

        $created = $this->postJson(self::MUTATE, [
            'mutate' => [
                [
                    'operation' => 'create',
                    'attributes' => [
                        'title' => 'Écran noir au démarrage',
                        'description' => 'Le poste ne dépasse pas le logo constructeur.',
                        'priority' => TicketPriority::High->value,
                    ],
                    'relations' => [
                        'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                    ],
                ],
            ],
        ])->assertOk();

        $id = $created->json('created.0');

        $this->assertDatabaseHas('tickets', [
            'id' => $id,
            'title' => 'Écran noir au démarrage',
            'requester_id' => $requester->getKey(),
        ]);

        /** A new ticket always starts at the beginning of the lifecycle. */
        $this->assertDatabaseHas('tickets', ['id' => $id, 'status' => TicketStatus::Open->value]);

        $this->forgetRestRequests();

        /** Deleting needs "close tickets", which a requester does not hold. */
        $this->actingAsProfile('manager');

        $this->postJson(self::MUTATE, [
            'mutate' => [
                [
                    'operation' => 'update',
                    'key' => $id,
                    'attributes' => ['title' => 'Écran noir au démarrage (poste 42)'],
                ],
            ],
        ])->assertOk()->assertJsonPath('updated.0', $id);

        $this->assertDatabaseHas('tickets', ['id' => $id, 'title' => 'Écran noir au démarrage (poste 42)']);

        $this->forgetRestRequests();

        $this->deleteJson('/api/v1/tickets', ['resources' => [$id]])->assertOk();

        $this->assertSoftDeleted('tickets', ['id' => $id]);
    }

    public function test_it_validates_the_payload_without_any_form_request(): void
    {
        $requester = $this->actingAsProfile('requester');

        $this->postJson(self::MUTATE, [
            'mutate' => [
                [
                    'operation' => 'create',
                    'attributes' => ['title' => 'Sans description ni priorité'],
                    'relations' => [
                        'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                    ],
                ],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'mutate.0.attributes.description',
            'mutate.0.attributes.priority',
        ]);
    }

    public function test_it_refuses_a_field_that_is_not_declared_on_the_resource(): void
    {
        $this->postJson(self::MUTATE, [
            'mutate' => [
                [
                    'operation' => 'update',
                    'key' => Ticket::factory()->create(['requester_id' => $this->actor->getKey()])->getKey(),
                    'attributes' => ['requester_id' => 999],
                ],
            ],
        ])->assertUnprocessable();
    }

    public function test_it_denies_updating_without_a_write_permission(): void
    {
        $requester = $this->actingAsProfile('requester');
        $ticket = Ticket::factory()->create(['requester_id' => $requester->getKey()]);

        $this->postJson(self::MUTATE, [
            'mutate' => [
                ['operation' => 'update', 'key' => $ticket->getKey(), 'attributes' => ['title' => 'Détourné']],
            ],
        ])->assertForbidden();
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

    public function test_it_exports_the_resolved_tickets_of_the_month_as_csv(): void
    {
        $inScope = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'created_at' => now()->startOfMonth(),
            'resolved_at' => now()->startOfMonth()->addHours(5),
        ]);

        $lastMonth = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'resolved_at' => now()->subMonth()->startOfMonth(),
        ]);

        $stillOpen = Ticket::factory()->create(['status' => TicketStatus::Open, 'resolved_at' => null]);

        $response = $this->get('/api/v1/tickets/exports/resolved-this-month')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $rows = array_filter(explode("\n", $response->streamedContent()));

        $this->assertStringStartsWith(
            'id,title,priority,requester,technician,created_at,resolved_at,resolution_hours',
            $rows[0],
        );
        $this->assertCount(2, $rows);
        $this->assertStringContainsString(',5', $rows[1]);
        $this->assertStringStartsWith((string) $inScope->getKey(), $rows[1]);

        $this->assertStringNotContainsString($lastMonth->title, $response->streamedContent());
        $this->assertStringNotContainsString($stillOpen->title, $response->streamedContent());
    }
}
