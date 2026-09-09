<?php

namespace Functional\Tickets\Database\Factories;

use App\Models\User;
use Functional\Tickets\Attachments\AttachmentConstraints;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = faker()->words(2).'.pdf';

        return [
            'ticket_id' => Ticket::factory(),
            'uploader_id' => User::factory(),
            'disk' => 'local',
            'path' => 'ticket-attachments/'.faker()->ulid().'.pdf',
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size_bytes' => faker()->number(1024, AttachmentConstraints::maxBytes()),
        ];
    }

    public function uploadedBy(User $uploader): static
    {
        return $this->state(fn (): array => [
            'uploader_id' => $uploader->getKey(),
        ]);
    }

    public function onTicket(Ticket $ticket): static
    {
        return $this->state(fn (): array => [
            'ticket_id' => $ticket->getKey(),
        ]);
    }
}
