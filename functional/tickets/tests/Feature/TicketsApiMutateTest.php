<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

class TicketsApiMutateTest extends TicketsApiTestCase
{
    public function test_it_creates_a_ticket(): void
    {
        $requester = $this->actingAsProfile('requester');

        $created = $this->postJson(self::MUTATE, [
            'mutate' => [
                [
                    'operation' => 'create',
                    'attributes' => [
                        'title' => 'Écran noir au démarrage',
                        'description' => 'Le poste ne dépasse pas le logo constructeur.',
                        'priority' => TicketPriority::High->value,
                    ],
                    'relations' => [
                        'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $created->json('created.0'),
            'title' => 'Écran noir au démarrage',
            'requester_id' => $requester->getKey(),
            /** A new ticket always starts at the beginning of the lifecycle. */
            'status' => TicketStatus::Open->value,
        ]);
    }

    public function test_it_updates_a_ticket(): void
    {
        $id = $this->openTicketThroughTheApi('Écran noir au démarrage');

        $this->actingAsProfile('manager');

        $this->postJson(self::MUTATE, [
            'mutate' => [
                [
                    'operation' => 'update',
                    'key' => $id,
                    'attributes' => ['title' => 'Écran noir au démarrage (poste 42)'],
                ],
            ],
        ])->assertOk()->assertJsonPath('updated.0', $id);

        $this->assertDatabaseHas('tickets', ['id' => $id, 'title' => 'Écran noir au démarrage (poste 42)']);
    }

    public function test_it_soft_deletes_a_ticket(): void
    {
        $id = $this->openTicketThroughTheApi('Écran noir au démarrage');

        /** Deleting needs "close tickets", which a requester does not hold. */
        $this->actingAsProfile('manager');

        $this->deleteJson('/api/v1/tickets', ['resources' => [$id]])->assertOk();

        $this->assertSoftDeleted('tickets', ['id' => $id]);
    }

    public function test_it_validates_the_payload_without_any_form_request(): void
    {
        $requester = $this->actingAsProfile('requester');

        $this->postJson(self::MUTATE, [
            'mutate' => [
                [
                    'operation' => 'create',
                    'attributes' => ['title' => 'Sans description ni priorité'],
                    'relations' => [
                        'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                    ],
                ],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'mutate.0.attributes.description',
            'mutate.0.attributes.priority',
        ]);
    }

    public function test_it_refuses_a_field_that_is_not_declared_on_the_resource(): void
    {
        $this->postJson(self::MUTATE, [
            'mutate' => [
                [
                    'operation' => 'update',
                    'key' => Ticket::factory()->create(['requester_id' => $this->actor->getKey()])->getKey(),
                    'attributes' => ['requester_id' => 999],
                ],
            ],
        ])->assertUnprocessable();
    }

    public function test_it_denies_updating_without_a_write_permission(): void
    {
        $requester = $this->actingAsProfile('requester');
        $ticket = Ticket::factory()->create(['requester_id' => $requester->getKey()]);

        $this->postJson(self::MUTATE, [
            'mutate' => [
                ['operation' => 'update', 'key' => $ticket->getKey(), 'attributes' => ['title' => 'Détourné']],
            ],
        ])->assertForbidden();
    }
}
