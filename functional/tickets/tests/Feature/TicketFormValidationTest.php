<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Livewire\TicketForm;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Arr;
use Livewire\Livewire;

class TicketFormValidationTest extends TicketFormTestCase
{
    public function test_it_refuses_an_empty_form_with_translated_messages(): void
    {
        Livewire::actingAs($this->requester())
            ->test(TicketForm::class)
            ->set('title', '')
            ->set('description', '')
            ->set('priority', '')
            ->call('save')
            ->assertHasErrors(['title' => 'required', 'description' => 'required', 'priority' => 'required'])
            ->assertSee(__('tickets::form.validation.title.required'))
            ->assertSee(__('tickets::form.validation.description.required'));
    }

    public function test_it_refuses_a_priority_that_is_not_in_the_enum(): void
    {
        Livewire::actingAs($this->requester())
            ->test(TicketForm::class)
            ->set('title', 'Un sujet valable')
            ->set('description', 'Une description suffisamment longue.')
            ->set('priority', 'catastrophic')
            ->call('save')
            ->assertHasErrors('priority')
            ->assertSee(__('tickets::form.validation.priority.enum'));

        $this->assertSame(0, Ticket::count());
    }

    public function test_every_enum_case_is_accepted(): void
    {
        foreach (TicketPriority::cases() as $priority) {
            Livewire::actingAs($this->requester())
                ->test(TicketForm::class)
                ->set('title', 'Sujet '.$priority->value)
                ->set('description', 'Une description suffisamment longue.')
                ->set('priority', $priority->value)
                ->call('save')
                ->assertHasNoErrors();
        }

        $this->assertSame(count(TicketPriority::cases()), Ticket::count());
    }

    public function test_the_validation_messages_all_come_from_the_language_files(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $this->app->setLocale($locale);

            $messages = Arr::dot(__('tickets::form.validation'));

            $this->assertIsArray(__('tickets::form.attributes'), $locale);
            $this->assertSame(
                [
                    'title.required', 'title.max',
                    'description.required', 'description.min',
                    'priority.required', 'priority.enum',
                    'technicianId.required', 'technicianId.exists',
                ],
                array_keys($messages),
                $locale,
            );

            foreach ($messages as $messageKey => $message) {
                $this->assertIsString($message, $locale.' / '.$messageKey);
                $this->assertStringNotContainsString('tickets::', $message);
            }
        }
    }
}
