<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Attachments\AttachmentConstraints;
use Functional\Tickets\Livewire\TicketAttachments;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class TicketAttachmentUploadTest extends TicketAttachmentTestCase
{
    public function test_it_stores_the_file_and_records_it_against_the_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('upload', $this->pdf('facture.pdf'))
            ->call('attach')
            ->assertHasNoErrors()
            ->assertSet('statusMessage', __('tickets::attachments.flash.attached'));

        $attachment = Attachment::query()->sole();

        $this->assertSame($ticket->getKey(), $attachment->ticket_id);
        $this->assertSame($this->manager->getKey(), $attachment->uploader_id);
        $this->assertSame('facture.pdf', $attachment->original_name);
        $this->assertSame('application/pdf', $attachment->mime_type);

        Storage::disk($attachment->disk)->assertExists($attachment->path);
    }

    public function test_the_stored_path_is_scoped_to_the_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('upload', $this->pdf())
            ->call('attach');

        $this->assertStringStartsWith(
            'ticket-attachments/'.$ticket->getKey().'/',
            Attachment::query()->sole()->path,
        );
    }

    public function test_it_refuses_a_file_over_the_declared_limit(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('upload', $this->pdf('enorme.pdf', AttachmentConstraints::maxKilobytes() + 1))
            ->call('attach')
            ->assertHasErrors(['upload' => 'max'])
            ->assertSee(__('tickets::attachments.validation.max', ['megabytes' => 10]));

        $this->assertSame(0, Attachment::count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_it_refuses_a_type_outside_the_allow_list(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('upload', UploadedFile::fake()->create('payload.exe', 8, 'application/x-msdownload'))
            ->call('attach')
            ->assertHasErrors(['upload' => 'mimetypes'])
            ->assertSee(__('tickets::attachments.validation.mimetypes'));

        $this->assertSame(0, Attachment::count());
    }

    public function test_it_refuses_an_empty_submission(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->call('attach')
            ->assertHasErrors(['upload' => 'required'])
            ->assertSee(__('tickets::attachments.validation.required'));
    }

    public function test_the_listing_loads_the_uploader_without_a_query_per_row(): void
    {
        $ticket = Ticket::factory()->create();
        Attachment::factory()->count(5)->onTicket($ticket)->create();

        Model::preventLazyLoading();

        Livewire::actingAs($this->manager)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->assertOk();
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);

        parent::tearDown();
    }
}
