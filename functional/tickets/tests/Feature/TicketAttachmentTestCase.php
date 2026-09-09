<?php

namespace Functional\Tickets\Tests\Feature;

use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class TicketAttachmentTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(TicketsAccessSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    protected function pdf(string $name = 'facture.pdf', int $kilobytes = 64): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
    }
}
