<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Livewire\TicketList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

abstract class TicketListTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TicketsAccessSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    protected function countQueriesRenderingTheList(): int
    {
        $count = 0;

        DB::listen(function ($query) use (&$count): void {
            if (str_contains($query->sql, 'tickets') || str_contains($query->sql, 'users') || str_contains($query->sql, 'comments')) {
                $count++;
            }
        });

        Livewire::actingAs($this->manager)->test(TicketList::class)->assertOk();

        DB::flushQueryLog();

        return $count;
    }
}
