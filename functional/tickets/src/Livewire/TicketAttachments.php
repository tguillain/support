<?php

namespace Functional\Tickets\Livewire;

use Functional\Tickets\Actions\AttachFileToTicket;
use Functional\Tickets\Actions\DetachTicketAttachment;
use Functional\Tickets\Attachments\AttachmentConstraints;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketAttachments extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Ticket $ticket;

    public ?UploadedFile $upload = null;

    public ?string $statusMessage = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'upload' => ['required', ...AttachmentConstraints::rules()],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'upload.required' => __('tickets::attachments.validation.required'),
            'upload.max' => __('tickets::attachments.validation.max', [
                'megabytes' => (int) round(AttachmentConstraints::maxKilobytes() / 1024),
            ]),
            'upload.mimetypes' => __('tickets::attachments.validation.mimetypes'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['upload' => __('tickets::attachments.field')];
    }

    public function attach(): void
    {
        $this->statusMessage = null;

        $this->authorize('update', $this->ticket);
        $this->validate();

        app(AttachFileToTicket::class)($this->ticket, $this->upload, auth()->user());

        $this->reset('upload');

        $this->statusMessage = __('tickets::attachments.flash.attached');
    }

    public function detach(int $attachmentId): void
    {
        $this->statusMessage = null;

        $attachment = Attachment::query()
            ->where('ticket_id', $this->ticket->getKey())
            ->findOrFail($attachmentId);

        $this->authorize('delete', $attachment);

        app(DetachTicketAttachment::class)($attachment);

        $this->statusMessage = __('tickets::attachments.flash.detached');
    }

    public function render(): View
    {
        $attachments = $this->ticket
            ->attachments()
            ->with('uploader:id,name')
            ->latest()
            ->get();

        /**
         * The row template asks the policy whether the attachment may be
         * removed, and the policy reads the attachment's ticket. Every row here
         * belongs to the ticket we already hold, so it is handed over instead
         * of being fetched once per row.
         */
        $attachments->each(
            fn (Attachment $attachment): Attachment => $attachment->setRelation('ticket', $this->ticket),
        );

        return view('tickets::livewire.ticket-attachments', [
            'maxKilobytes' => AttachmentConstraints::maxKilobytes(),
            'attachments' => $attachments,
        ]);
    }
}
