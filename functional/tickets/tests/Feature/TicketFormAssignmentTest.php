<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Livewire\TicketForm;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketAssignedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

class TicketFormAssignmentTest extends TicketFormTestCase
{
    public function test_a_legal_assignment_succeeds_with_a_translated_message(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        Livewire::actingAs($this->manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('technicianId', $this->technician->getKey())
            ->call('assign')
            ->assertHasNoErrors()
            ->assertSet('statusMessage', __('tickets::form.flash.assigned'));

        $this->assertSame(TicketStatus::Assigned, $ticket->fresh()->status);
        $this->assertSame($this->technician->getKey(), $ticket->fresh()->assigned_technician_id);

        Notification::assertSentTo($this->technician, TicketAssignedNotification::class);
    }

    public function test_an_illegal_assignment_shows_a_translated_error_and_changes_nothing(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        Livewire::actingAs($this->manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('technicianId', $this->technician->getKey())
            ->call('assign')
            ->assertHasErrors('transition')
            ->assertSee(__('tickets::form.errors.illegal_transition', [
                'from' => TicketStatus::Closed->label(),
                'to' => TicketStatus::Assigned->label(),
            ]))
            ->assertSet('statusMessage', null);

        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->assigned_technician_id);
    }

    public function test_an_illegal_assignment_does_not_notify_anyone(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        Livewire::actingAs($this->manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('technicianId', $this->technician->getKey())
            ->call('assign');

        Notification::assertNothingSent();
    }

    public function test_assigning_without_a_technician_is_a_field_error(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        Livewire::actingAs($this->manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('technicianId', null)
            ->call('assign')
            ->assertHasErrors(['technicianId' => 'required'])
            ->assertSee(__('tickets::form.validation.technicianId.required'));

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_assigning_an_unknown_technician_is_a_field_error(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        Livewire::actingAs($this->manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('technicianId', 99999)
            ->call('assign')
            ->assertHasErrors(['technicianId' => 'exists']);
    }
}
