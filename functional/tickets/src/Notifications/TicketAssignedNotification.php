<?php

namespace Functional\Tickets\Notifications;

use Functional\Tickets\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Ticket $ticket) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('tickets::notifications.assigned.subject', ['title' => $this->ticket->title]))
            ->greeting(__('tickets::notifications.assigned.greeting', ['name' => $notifiable->name]))
            ->line(__('tickets::notifications.assigned.intro', [
                'title' => $this->ticket->title,
                'priority' => __('tickets::priorities.'.$this->ticket->priority->value),
            ]))
            ->line(__('tickets::notifications.assigned.sla', [
                'hours' => $this->ticket->priority->slaHours(),
            ]))
            ->line($this->ticket->description);
    }
}
