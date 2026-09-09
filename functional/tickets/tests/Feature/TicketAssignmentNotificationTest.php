<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Listeners\NotifyAssignedTechnician;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketAssignedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

class TicketAssignmentNotificationTest extends TicketLifecycleTestCase
{
    public function test_assignment_dispatches_the_event_with_the_ticket_and_the_technician(): void
    {
        Event::fake([TicketAssigned::class]);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        app(AssignTicket::class)($ticket, $this->technician);

        Event::assertDispatched(
            TicketAssigned::class,
            fn (TicketAssigned $event): bool => $event->ticket->is($ticket)
                && $event->technician->is($this->technician),
        );
        Event::assertListening(TicketAssigned::class, NotifyAssignedTechnician::class);
    }

    public function test_the_listener_notifies_the_assigned_technician(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        app(AssignTicket::class)($ticket, $this->technician);

        Notification::assertSentTo(
            $this->technician,
            TicketAssignedNotification::class,
            fn (TicketAssignedNotification $notification): bool => $notification->ticket->is($ticket),
        );
    }

    public function test_the_notification_body_comes_from_the_translation_files(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Open,
            'title' => 'Écran noir',
            'priority' => TicketPriority::Critical,
        ]);

        $mail = (new TicketAssignedNotification($ticket))->toMail($this->technician);

        $this->assertSame(
            __('tickets::notifications.assigned.subject', ['title' => 'Écran noir']),
            $mail->subject,
        );
        $this->assertStringContainsString('Écran noir', $mail->subject);
        $this->assertContains(
            __('tickets::notifications.assigned.sla', ['hours' => TicketPriority::Critical->slaHours()]),
            $mail->introLines,
        );
    }

    public function test_the_notification_subject_is_translated(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open, 'title' => 'Écran noir']);

        $english = (new TicketAssignedNotification($ticket))->toMail($this->technician)->subject;

        $this->app->setLocale('fr');

        $french = (new TicketAssignedNotification($ticket))->toMail($this->technician)->subject;

        $this->assertNotSame($english, $french);
        $this->assertStringContainsString('assigné', $french);
    }
}
