<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class TicketLifecycleTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TicketsAccessSeeder::class);

        $this->technician = User::firstWhere('email', TicketsAccessSeeder::DEMO_TECHNICIAN_EMAIL);

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);
    }

    protected function action(string $uriKey): string
    {
        return '/api/v1/tickets/actions/'.$uriKey;
    }
}
