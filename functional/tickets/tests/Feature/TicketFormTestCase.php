<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class TicketFormTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TicketsAccessSeeder::class);

        $this->technician = User::firstWhere('email', TicketsAccessSeeder::DEMO_TECHNICIAN_EMAIL);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    protected function requester(): User
    {
        $requester = User::factory()->create();
        $requester->assignRole('requester');

        return $requester;
    }
}
