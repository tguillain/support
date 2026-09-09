<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketAttachmentApiTest extends TicketAttachmentTestCase
{
    private const SEARCH = '/api/v1/ticket-attachments/search';

    public function test_it_lists_the_attachments_of_the_visible_tickets(): void
    {
        Attachment::factory()->count(3)->create();

        $this->actingAs($this->manager)
            ->postJson(self::SEARCH)
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_it_exposes_metadata_and_never_the_storage_location(): void
    {
        Attachment::factory()->create(['original_name' => 'facture.pdf']);

        $attachment = $this->actingAs($this->manager)
            ->postJson(self::SEARCH)
            ->assertOk()
            ->json('data.0');

        $this->assertSame(
            ['id', 'original_name', 'mime_type', 'size_bytes', 'created_at'],
            array_keys($attachment),
        );
        $this->assertSame('facture.pdf', $attachment['original_name']);
    }

    public function test_a_requester_only_sees_the_attachments_of_their_own_tickets(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);

        Attachment::factory()->count(2)->onTicket(
            Ticket::factory()->create(['requester_id' => $requester->getKey()]),
        )->create();
        Attachment::factory()->count(5)->create();

        $this->actingAs($requester)
            ->postJson(self::SEARCH)
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_a_technician_only_sees_the_attachments_of_their_assigned_tickets(): void
    {
        $technician = User::firstWhere('email', TicketsAccessSeeder::DEMO_TECHNICIAN_EMAIL);

        Attachment::factory()->count(4)->onTicket(
            Ticket::factory()->create(['assigned_technician_id' => $technician->getKey()]),
        )->create();
        Attachment::factory()->count(3)->create();

        $this->actingAs($technician)
            ->postJson(self::SEARCH)
            ->assertOk()
            ->assertJsonPath('total', 4);
    }

    public function test_the_restriction_is_a_subquery_on_the_visible_tickets(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);
        Attachment::factory()->create();

        $statements = [];
        DB::listen(function ($query) use (&$statements): void {
            $statements[] = $query->sql;
        });

        $this->actingAs($requester)->postJson(self::SEARCH)->assertOk();

        $selects = array_values(array_filter(
            $statements,
            fn (string $sql): bool => str_contains($sql, 'ticket_attachments') && str_starts_with($sql, 'select'),
        ));

        $this->assertNotEmpty($selects);

        foreach ($selects as $sql) {
            $this->assertStringContainsString('requester_id', $sql);
        }
    }

    public function test_a_guest_is_refused(): void
    {
        $this->postJson(self::SEARCH)->assertUnauthorized();
    }

    public function test_the_attachments_are_includable_from_a_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        Attachment::factory()->count(2)->onTicket($ticket)->create();

        $this->actingAs($this->manager)
            ->postJson('/api/v1/tickets/search', [
                'search' => ['includes' => [['relation' => 'attachments']]],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data.0.attachments');
    }
}
