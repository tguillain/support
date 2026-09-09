<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::guest')]
class Login extends Component
{
    private const MAX_ATTEMPTS = 5;

    public string $email = '';

    public string $password = '';

    public bool $isRemembered = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return Arr::dot(__('auth.validation'));
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return __('auth.attributes');
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->isRemembered)) {
            RateLimiter::hit($this->throttleKey());

            /** Deliberately the same message whether or not the account exists. */
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());

        /** Prevents session fixation: the pre-login id must not stay valid. */
        session()->regenerate();

        $this->redirectIntended(route('tickets.index'));
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        Event::dispatch(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
