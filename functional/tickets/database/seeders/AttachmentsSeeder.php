<?php

namespace Functional\Tickets\Database\Seeders;

use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Puts one attachment on every resolved ticket, so the download and removal
 * paths have something to work with after a fresh seed.
 *
 * The placeholder bytes are written too: a row with no file behind it makes the
 * download endpoint answer 404 on a freshly seeded environment, which reads as
 * a broken feature rather than as seeded data.
 */
class AttachmentsSeeder extends Seeder
{
    public function run(): void
    {
        $tickets = Ticket::query()
            ->whereNotNull('resolved_at')
            ->with('requester:id')
            ->get();

        foreach ($tickets as $ticket) {
            $attachment = Attachment::factory()
                ->onTicket($ticket)
                ->create(['uploader_id' => $ticket->requester_id]);

            Storage::disk($attachment->disk)->put(
                $attachment->path,
                self::placeholderFor($ticket->title),
            );
        }
    }

    private static function placeholderFor(string $ticketTitle): string
    {
        return sprintf("%%PDF-1.4\n%% seeded placeholder for: %s\n", $ticketTitle);
    }
}
