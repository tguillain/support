<?php

namespace Functional\Tickets\Livewire;

use App\Models\User;
use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Actions\CreateTicket;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Exceptions\IllegalTicketTransitionException;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class TicketForm extends Component
{
    use AuthorizesRequests;

    /**
     * The fields the form itself owns. Assignment is a transition, not a field,
     * so it is validated and applied on its own.
     *
     * @var list<string>
     */
    private const FORM_FIELDS = ['title', 'description', 'priority'];

    public ?Ticket $ticket = null;

    public string $title = '';

    public string $description = '';

    public string $priority = '';

    public ?int $technicianId = null;

    /**
     * Held in component state rather than flashed: every action re-renders in
     * place, so there is no redirect for a flash to survive.
     */
    public ?string $statusMessage = null;

    public function mount(?Ticket $ticket = null): void
    {
        if ($ticket === null || ! $ticket->exists) {
            $this->authorize('create', Ticket::class);
            $this->priority = TicketPriority::Normal->value;

            return;
        }

        $this->authorize('update', $ticket);

        $this->ticket = $ticket;
        $this->title = $ticket->title;
        $this->description = $ticket->description;
        $this->priority = $ticket->priority->value;
        $this->technicianId = $ticket->assigned_technician_id;
    }

    /**
     * Every rule this component enforces, declared once. The save and assign
     * paths select from this list rather than restating any of it.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'technicianId' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        /** Nested in the language file so each message stays addressable;
         * flattened here because the validator wants "field.rule" keys. */
        return Arr::dot(__('tickets::form.validation'));
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return __('tickets::form.attributes');
    }

    public function save(): void
    {
        $this->statusMessage = null;

        $attributes = $this->validate(Arr::only($this->rules(), self::FORM_FIELDS));

        if ($this->ticket === null) {
            $this->authorize('create', Ticket::class);

            $this->ticket = app(CreateTicket::class)(
                requester: auth()->user(),
                title: $attributes['title'],
                description: $attributes['description'],
                priority: TicketPriority::from($attributes['priority']),
            );

            $this->statusMessage = __('tickets::form.flash.created');

            return;
        }

        $this->authorize('update', $this->ticket);

        $this->ticket->update([
            'title' => $attributes['title'],
            'description' => $attributes['description'],
            'priority' => TicketPriority::from($attributes['priority']),
        ]);

        $this->statusMessage = __('tickets::form.flash.updated');
    }

    public function assign(): void
    {
        abort_unless($this->ticket !== null, 404);

        $this->statusMessage = null;

        $this->authorize('update', $this->ticket);
        $this->validateOnly('technicianId');

        /**
         * NoTryCatchRule forbids try/catch, so the translation goes through
         * rescue(): the failure closure reports the refusal and answers false,
         * and reportRefusal rethrows anything that is not the domain exception
         * so a real fault never comes back dressed as a business message.
         * report: false because this failure is handled, not logged twice.
         */
        $assigned = rescue(
            fn (): bool => $this->applyAssignment(),
            fn (Throwable $exception): bool => $this->reportRefusal($exception),
            report: false,
        );

        if (! $assigned) {
            return;
        }

        $this->statusMessage = __('tickets::form.flash.assigned');
    }

    private function applyAssignment(): bool
    {
        app(AssignTicket::class)(
            $this->ticket,
            User::findOrFail($this->technicianId),
        );

        return true;
    }

    /**
     * @throws Throwable
     */
    private function reportRefusal(Throwable $exception): bool
    {
        if (! $exception instanceof IllegalTicketTransitionException) {
            throw $exception;
        }

        $this->addError('transition', __('tickets::form.errors.illegal_transition', [
            'from' => $exception->from()->label(),
            'to' => $exception->to()->label(),
        ]));

        return false;
    }

    public function render(): View
    {
        return view('tickets::livewire.ticket-form', [
            'priorities' => TicketPriority::cases(),
            'technicians' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
