<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

abstract class AuthTestCase extends TestCase
{
    use RefreshDatabase;

    protected const PASSWORD = 'password';

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');
    }

    protected function user(): User
    {
        return User::factory()->create(['email' => 'manager@support.test']);
    }
}
