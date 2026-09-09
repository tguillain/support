<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

class TicketsApiExportTest extends TicketsApiTestCase
{
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
        $this->assertSame((string) $inScope->getKey(), explode(',', $rows[1])[0]);

        $this->assertStringNotContainsString($lastMonth->title, $response->streamedContent());
        $this->assertStringNotContainsString($stillOpen->title, $response->streamedContent());
    }
}
