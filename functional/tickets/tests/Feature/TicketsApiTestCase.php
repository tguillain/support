<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Enums\TicketPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lomkit\Rest\Http\Requests\DestroyRequest;
use Lomkit\Rest\Http\Requests\MutateRequest;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Requests\SearchRequest;
use Tests\TestCase;

abstract class TicketsApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected const SEARCH = '/api/v1/tickets/search';

    protected const MUTATE = '/api/v1/tickets/mutate';

    protected User $actor;

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
    protected function actingAsProfile(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    /**
     * Open a ticket through the API and hand back its id.
     *
     * The requester profile is the only one holding "create tickets", so the
     * caller is left authenticated as a requester.
     */
    protected function openTicketThroughTheApi(string $title): int
    {
        $requester = $this->actingAsProfile('requester');

        $created = $this->postJson(self::MUTATE, [
            'mutate' => [
                [
                    'operation' => 'create',
                    'attributes' => [
                        'title' => $title,
                        'description' => 'Le poste ne dépasse pas le logo constructeur.',
                        'priority' => TicketPriority::High->value,
                    ],
                    'relations' => [
                        'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                    ],
                ],
            ],
        ])->assertOk();

        $this->forgetRestRequests();

        return (int) $created->json('created.0');
    }

    /**
     * The package registers every Rest request class as a container singleton
     * (RestServiceProvider::register). A real HTTP request gets a fresh
     * container so that is harmless in production, but a feature test reuses
     * one application across calls: without this reset, a second call to the
     * same endpoint replays the first payload.
     */
    protected function forgetRestRequests(): void
    {
        foreach ([RestRequest::class, SearchRequest::class, MutateRequest::class, DestroyRequest::class] as $request) {
            $this->app->forgetInstance($request);
        }
    }
}
