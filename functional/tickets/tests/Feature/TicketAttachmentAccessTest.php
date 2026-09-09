<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Actions\DetachTicketAttachment;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Livewire\TicketAttachments;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class TicketAttachmentAccessTest extends TicketAttachmentTestCase
{
    public function test_the_download_serves_the_stored_file_under_its_original_name(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('upload', $this->pdf('rapport.pdf'))
            ->call('attach');

        $attachment = Attachment::query()->sole();

        $this->actingAs($this->manager)
            ->get(route('tickets.attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('rapport.pdf');
    }

    public function test_a_guest_cannot_download_an_attachment(): void
    {
        $attachment = Attachment::factory()->create();

        $this->get(route('tickets.attachments.download', $attachment))
            ->assertRedirect(route('login'));
    }

    public function test_a_requester_cannot_download_an_attachment_of_another_ticket(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);
        $attachment = Attachment::factory()->create();

        $this->actingAs($requester)
            ->get(route('tickets.attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_a_requester_can_download_an_attachment_of_their_own_ticket(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->getKey()]);

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('upload', $this->pdf('devis.pdf'))
            ->call('attach');

        $this->actingAs($requester)
            ->get(route('tickets.attachments.download', Attachment::query()->sole()))
            ->assertOk();
    }

    public function test_a_user_without_write_access_cannot_attach(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->getKey()]);

        Livewire::actingAs($requester)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('upload', $this->pdf())
            ->call('attach')
            ->assertForbidden();

        $this->assertSame(0, Attachment::count());
    }

    public function test_removing_an_attachment_deletes_the_row_and_the_file(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('upload', $this->pdf())
            ->call('attach');

        $attachment = Attachment::query()->sole();
        $path = $attachment->path;

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->call('detach', $attachment->getKey())
            ->assertSet('statusMessage', __('tickets::attachments.flash.detached'));

        $this->assertSame(0, Attachment::count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_the_detach_action_refuses_an_attachment_of_another_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $foreign = Attachment::factory()->create();

        /** Scoped by ticket_id before the policy even runs, so it is not found. */
        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->call('detach', $foreign->getKey());
    }

    public function test_the_create_ability_never_grants_more_than_the_upload_path(): void
    {
        $requester = User::firstWhere('email', TicketsAccessSeeder::DEMO_REQUESTER_EMAIL);

        /** No ticket in scope, so this ability cannot be the one that decides. */
        foreach ([$this->manager, $requester] as $actor) {
            $this->assertFalse(
                Gate::forUser($actor)->allows('create', Attachment::class),
                $actor->email,
            );
        }
    }

    public function test_the_detach_action_leaves_the_pair_consistent(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('upload', $this->pdf())
            ->call('attach');

        $attachment = Attachment::query()->sole();

        app(DetachTicketAttachment::class)($attachment);

        $this->assertDatabaseMissing('ticket_attachments', ['id' => $attachment->getKey()]);
        Storage::disk('local')->assertMissing($attachment->path);
    }
}
