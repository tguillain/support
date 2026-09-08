<?php

namespace Functional\Tickets\Tests\Feature;

use Tests\TestCase;

class TicketsTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
