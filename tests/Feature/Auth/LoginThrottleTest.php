<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

class LoginThrottleTest extends AuthTestCase
{
    public function test_attempts_are_rate_limited(): void
    {
        Event::fake([Lockout::class]);

        $user = $this->user();

        foreach (range(1, 5) as $ignored) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'not-the-password')
                ->call('authenticate')
                ->assertHasErrors('email');
        }

        $message = Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->errors()
            ->first('email');

        $this->assertStringContainsString(__('auth.throttle', ['seconds' => 0, 'minutes' => 0])[0], $message);
        $this->assertGuest();

        Event::assertDispatched(Lockout::class);
    }

    public function test_a_successful_sign_in_clears_the_rate_limiter(): void
    {
        $user = $this->user();

        foreach (range(1, 3) as $ignored) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'not-the-password')
                ->call('authenticate');
        }

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }
}
