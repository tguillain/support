<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;

class LoginSessionTest extends AuthTestCase
{
    /*
     * Session-fixation protection (session()->regenerate() in Login::authenticate)
     * is deliberately NOT asserted here.
     *
     * Three attempts, none of which can fail for the right reason:
     *  - comparing session ids around Livewire::test() — the harness builds a
     *    fresh session per call, so the ids differ with the call removed too;
     *  - Session::partialMock()->shouldReceive('regenerate') — asserts the
     *    interaction, but the mock breaks view rendering for the whole class;
     *  - driving POST /livewire/update over real HTTP — the endpoint answers
     *    404 for a hand-built payload.
     *
     * A green test that cannot go red is worse than none, so the gap is stated
     * instead of papered over.
     */

    public function test_remember_me_queues_the_recaller_cookie(): void
    {
        $user = $this->user();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', self::PASSWORD)
            ->set('isRemembered', true)
            ->call('authenticate')
            ->assertHasNoErrors();

        $names = array_map(
            fn ($cookie): string => $cookie->getName(),
            Cookie::getQueuedCookies(),
        );

        $this->assertNotEmpty(array_filter(
            $names,
            fn (string $name): bool => str_starts_with($name, 'remember_'),
        ), 'No recaller cookie was queued: '.implode(', ', $names));
    }

    public function test_signing_in_without_remember_me_queues_no_recaller(): void
    {
        $user = $this->user();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->assertHasNoErrors();

        foreach (Cookie::getQueuedCookies() as $cookie) {
            $this->assertStringStartsNotWith('remember_', $cookie->getName());
        }
    }
}
