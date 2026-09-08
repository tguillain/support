<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Xefi\LaravelOSDD\SeederRegistry;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(private readonly SeederRegistry $seederRegistry) {}

    /**
     * Seed the application's database, then every seeder registered by an OSDD layer.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call($this->seederRegistry->seeders());
    }
}
