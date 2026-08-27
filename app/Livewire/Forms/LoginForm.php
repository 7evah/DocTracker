<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            /*
            | The real password did not match — try the temporary one issued
            | by the forgot-password form before giving up. Both are live at
            | once by design: mailing a password must not invalidate the one
            | the account holder may still be using (§4).
            */
            if (! $this->attemptTemporaryPassword()) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'form.email' => trans('auth.failed'),
                ]);
            }
        }

        /*
        | Deactivated and suspended accounts must not hold a session (§29).
        | Checked after the credential check so this never reveals whether an
        | address exists — a wrong password and a disabled account are
        | indistinguishable to an attacker until the password is correct.
        */
        if (! Auth::user()->status->canLogin()) {
            Auth::guard('web')->logout();

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.inactive'),
            ]);
        }

        Auth::user()->forceFill(['last_active_at' => now()])->saveQuietly();

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Sign in with a mailed temporary password, if one is live.
     *
     * Flags the account so the next request is diverted to the change-password
     * screen, and clears the temporary password immediately: it buys one
     * sign-in, not a second credential that lingers.
     */
    protected function attemptTemporaryPassword(): bool
    {
        $user = User::where('email', $this->email)->first();

        if (! $user || ! $user->matchesTemporaryPassword($this->password)) {
            return false;
        }

        Auth::login($user, $this->remember);

        $user->forceFill(['must_change_password' => true])->save();
        $user->clearTemporaryPassword();

        return true;
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
