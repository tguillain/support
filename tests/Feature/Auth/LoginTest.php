<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Livewire\Livewire;

class LoginTest extends AuthTestCase
{
    public function test_the_login_page_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('wire:name="auth.login"', escape: false)
            ->assertSee(__('auth.login.heading'))
            ->assertSee(__('auth.login.fields.email'));
    }

    public function test_a_guest_reaching_a_protected_page_is_sent_to_the_login_screen(): void
    {
        $this->get('/tickets')->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_is_sent_away_from_the_login_screen(): void
    {
        $this->actingAs($this->user())
            ->get(route('login'))
            ->assertRedirect('/tickets');
    }

    public function test_valid_credentials_sign_the_user_in(): void
    {
        $user = $this->user();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('tickets.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_refused_with_a_translated_message(): void
    {
        $user = $this->user();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'not-the-password')
            ->call('authenticate')
            ->assertHasErrors('email')
            ->assertSee(__('auth.failed'));

        $this->assertGuest();
    }

    public function test_an_unknown_email_gives_exactly_the_same_message(): void
    {
        $this->user();

        $unknown = Livewire::test(Login::class)
            ->set('email', 'nobody@support.test')
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->errors()
            ->first('email');

        $wrongPassword = Livewire::test(Login::class)
            ->set('email', 'manager@support.test')
            ->set('password', 'not-the-password')
            ->call('authenticate')
            ->errors()
            ->first('email');

        /** Same wording either way: the form must not reveal which accounts exist. */
        $this->assertSame($unknown, $wrongPassword);
        $this->assertSame(__('auth.failed'), $unknown);
    }

    public function test_an_empty_form_shows_the_translated_validation_messages(): void
    {
        Livewire::test(Login::class)
            ->call('authenticate')
            ->assertHasErrors(['email' => 'required', 'password' => 'required'])
            ->assertSee(__('auth.validation.email.required'));

        $this->assertGuest();
    }

    public function test_a_malformed_email_is_refused(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'not-an-email')
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->assertHasErrors(['email' => 'email'])
            ->assertSee(__('auth.validation.email.email'));
    }

    public function test_signing_out_invalidates_the_session(): void
    {
        $this->actingAs($this->user())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_signed_in_manager_actually_reaches_the_ticket_list(): void
    {
        $this->seed(TicketsAccessSeeder::class);

        $manager = User::firstWhere('email', TicketsAccessSeeder::DEMO_MANAGER_EMAIL);

        Livewire::test(Login::class)
            ->set('email', $manager->email)
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->assertRedirect(route('tickets.index'));

        $this->actingAs($manager)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee(__('tickets::list.columns.requester'))
            ->assertSee(__('auth.logout'));
    }
}
