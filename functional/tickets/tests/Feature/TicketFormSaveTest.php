<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Livewire\TicketForm;
use Functional\Tickets\Models\Ticket;
use Livewire\Livewire;

class TicketFormSaveTest extends TicketFormTestCase
{
    public function test_the_create_page_renders(): void
    {
        $this->actingAs($this->requester())
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('wire:name="tickets.ticket-form"', escape: false)
            ->assertSee(__('tickets::form.fields.title'));
    }

    public function test_the_edit_page_renders_with_the_existing_values(): void
    {
        $ticket = Ticket::factory()->create(['title' => 'Écran noir', 'description' => 'Bloque au logo constructeur.']);

        $this->actingAs($this->manager)
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertSee('wire:name="tickets.ticket-form"', escape: false)
            ->assertSee('Écran noir', escape: false)
            ->assertSee('Bloque au logo constructeur.', escape: false);
    }

    public function test_it_creates_a_ticket(): void
    {
        $requester = $this->requester();

        Livewire::actingAs($requester)
            ->test(TicketForm::class)
            ->set('title', 'Imprimante hors service')
            ->set('description', 'Bourrage papier permanent depuis ce matin.')
            ->set('priority', TicketPriority::High->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('statusMessage', __('tickets::form.flash.created'));

        $this->assertDatabaseHas('tickets', [
            'title' => 'Imprimante hors service',
            'priority' => TicketPriority::High->value,
            'requester_id' => $requester->getKey(),
            'status' => TicketStatus::initial()->value,
        ]);
    }

    public function test_a_created_ticket_starts_at_the_initial_status(): void
    {
        Livewire::actingAs($this->requester())
            ->test(TicketForm::class)
            ->set('title', 'Poste bloqué')
            ->set('description', 'Ne démarre plus du tout depuis la mise à jour.')
            ->set('priority', TicketPriority::Low->value)
            ->call('save');

        $this->assertSame(TicketStatus::initial(), Ticket::latest('id')->first()->status);
    }

    public function test_it_updates_an_existing_ticket(): void
    {
        $ticket = Ticket::factory()->create(['title' => 'Avant']);

        Livewire::actingAs($this->manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->assertSet('title', 'Avant')
            ->set('title', 'Après')
            ->set('description', 'Une description suffisamment longue.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('statusMessage', __('tickets::form.flash.updated'));

        $this->assertSame('Après', $ticket->fresh()->title);
    }

    public function test_saving_does_not_require_a_technician(): void
    {
        $ticket = Ticket::factory()->create(['assigned_technician_id' => null]);

        Livewire::actingAs($this->manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('title', 'Sans technicien')
            ->set('description', 'Une description suffisamment longue.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Sans technicien', $ticket->fresh()->title);
    }

    public function test_a_user_without_the_create_permission_cannot_open_the_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('tickets.create'))
            ->assertForbidden();
    }

    public function test_a_requester_cannot_edit_someone_elses_ticket(): void
    {
        $this->actingAs($this->requester())
            ->get(route('tickets.edit', Ticket::factory()->create()))
            ->assertForbidden();
    }
}
