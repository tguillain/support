<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Requests\SearchRequest;
use Tests\TestCase;

/**
 * The perimeters, checked through the API and through the query builder.
 *
 * Both paths must agree: the API adds no scoping of its own, it inherits the
 * Control through TicketResource::searchQuery().
 */
class TicketsAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private const SEARCH = '/api/v1/tickets/search';

    private User $requester;

    private User $technician;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TicketsAccessSeeder::class);

        $this->requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);
        $this->technician = User::firstWhere('email', TicketsAccessSeeder::DEMO_TECHNICIAN_EMAIL);
        $this->manager = User::firstWhere('email', TicketsAccessSeeder::DEMO_MANAGER_EMAIL);

        Ticket::factory()->count(3)->create(['requester_id' => $this->requester->getKey()]);
        Ticket::factory()->count(4)->create(['assigned_technician_id' => $this->technician->getKey()]);
        Ticket::factory()->count(5)->create();
    }

    public function test_the_seeder_provisions_every_profile_with_its_permissions(): void
    {
        $this->assertSame(
            ['create tickets', 'view own tickets'],
            $this->sortedPermissionsOf($this->requester),
        );
        $this->assertSame(
            ['view assigned tickets'],
            $this->sortedPermissionsOf($this->technician),
        );
        $this->assertSame(
            ['assign tickets', 'close tickets', 'view all tickets'],
            $this->sortedPermissionsOf($this->manager),
        );
    }

    public function test_a_requester_only_retrieves_their_own_tickets(): void
    {
        $this->assertVisibleThroughApi($this->requester, 3);
    }

    public function test_a_technician_only_retrieves_the_tickets_assigned_to_them(): void
    {
        $this->assertVisibleThroughApi($this->technician, 4);
    }

    public function test_a_manager_retrieves_every_ticket(): void
    {
        $this->assertVisibleThroughApi($this->manager, 12);
    }

    public function test_a_user_holding_no_view_permission_is_denied_outright(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(self::SEARCH)->assertForbidden();
    }

    public function test_a_requester_cannot_read_another_requesters_ticket(): void
    {
        $foreign = Ticket::factory()->create();

        $this->actingAs($this->requester);

        $ids = array_column($this->postJson(self::SEARCH)->assertOk()->json('data'), 'id');

        $this->assertNotContains($foreign->getKey(), $ids);
    }

    public function test_holding_both_view_permissions_returns_the_union(): void
    {
        $hybrid = User::factory()->create();
        $hybrid->assignRole(['requester', 'technician']);

        Ticket::factory()->count(2)->create(['requester_id' => $hybrid->getKey()]);
        Ticket::factory()->count(3)->create(['assigned_technician_id' => $hybrid->getKey()]);

        $this->assertVisibleThroughApi($hybrid, 5);
    }

    public function test_the_restriction_is_applied_in_sql_and_not_in_php(): void
    {
        $this->actingAs($this->requester);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->postJson(self::SEARCH)->assertOk();

        $selects = array_values(array_filter(
            $queries,
            fn (string $sql): bool => str_starts_with($sql, 'select') && str_contains($sql, '`tickets`'),
        ));

        $this->assertNotEmpty($selects, 'No select ran against the tickets table.');

        foreach ($selects as $sql) {
            $this->assertStringContainsString(
                '`requester_id` = ?',
                $sql,
                'The perimeter must narrow the SQL, not filter the collection afterwards.',
            );
        }
    }

    public function test_a_denied_profile_produces_an_impossible_query_rather_than_an_open_one(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertStringContainsString('0=1', Ticket::controlled()->toSql());
    }

    public function test_the_api_and_the_query_builder_agree(): void
    {
        foreach ([$this->requester, $this->technician, $this->manager] as $user) {
            $this->actingAs($user);

            $throughBuilder = Ticket::controlled()->count();
            $throughApi = $this->postJson(self::SEARCH)->assertOk()->json('total');

            $this->assertSame(
                $throughBuilder,
                $throughApi,
                sprintf('%s sees %d rows in Eloquent but %d through the API.', $user->email, $throughBuilder, $throughApi),
            );

            $this->app->forgetInstance(SearchRequest::class);
            $this->app->forgetInstance(RestRequest::class);
        }
    }

    private function assertVisibleThroughApi(User $user, int $expected): void
    {
        $this->actingAs($user);

        $this->postJson(self::SEARCH)->assertOk()->assertJsonPath('total', $expected);

        $this->assertSame($expected, Ticket::controlled()->count());
    }

    /**
     * @return list<string>
     */
    private function sortedPermissionsOf(User $user): array
    {
        $permissions = $user->getAllPermissions()->pluck('name')->all();
        sort($permissions);

        return $permissions;
    }
}
