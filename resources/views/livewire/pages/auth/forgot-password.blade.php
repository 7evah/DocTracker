<?php

use App\Models\User;
use App\Notifications\TemporaryPasswordIssued;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $email = '';

    /**
     * Mail a one-off password to the address, if it belongs to an account.
     *
     * Deliberately not Password::sendResetLink(): the flow here hands out a
     * temporary password rather than a tokenised link (§4). It is stored
     * beside the real password, never in place of it, so submitting somebody
     * else's address cannot cost them access to their own account.
     */
    public function sendTemporaryPassword(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->email)->first();

        // Inactive accounts get nothing: a temporary password they could not
        // use anyway would only be a credential in an inbox (§29).
        if ($user && $user->status->canLogin()) {
            $user->notify(new TemporaryPasswordIssued($user->issueTemporaryPassword()));
        }

        RateLimiter::hit($this->throttleKey());

        /*
        | Identical response either way, and phrased as "if an account
        | exists": the form must not become a way of discovering who holds
        | one (§39).
        */
        $this->reset('email');

        session()->flash('status', __('passwords.sent'));
    }

    /** Issuing credentials by e-mail is worth throttling harder than a login. */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('passwords.throttled'),
        ]);
    }

    protected function throttleKey(): string
    {
        return 'temp-password|'.Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div>
    <flux:heading size="xl" level="1">{{ __('auth.forgot_password.title') }}</flux:heading>
    <flux:subheading class="mt-1 mb-6">{{ __('auth.forgot_password.intro') }}</flux:subheading>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="sendTemporaryPassword" class="flex flex-col gap-5">
        <flux:input
            wire:model="email"
            :label="__('auth.login.email')"
            type="email"
            name="email"
            error:name="email"
            required
            autofocus
            autocomplete="username"
        />

        <flux:button type="submit" variant="primary" class="w-full">
            <span wire:loading.remove wire:target="sendTemporaryPassword">
                {{ __('auth.forgot_password.submit') }}
            </span>
            <span wire:loading wire:target="sendTemporaryPassword">{{ __('common.states.loading') }}</span>
        </flux:button>

        <flux:link :href="route('login')" variant="subtle" class="text-center text-sm" wire:navigate>
            {{ __('common.actions.back') }}
        </flux:link>
    </form>
</div>
