<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        /*
        | Always report success, even for unknown addresses, so the form is not
        | an account-enumeration oracle (§39).
        */
        $this->reset('email');

        session()->flash('status', __('passwords.sent'));
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

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-5">
        <flux:input
            wire:model="email"
            :label="__('auth.login.email')"
            type="email"
            required
            autofocus
            autocomplete="username"
        />

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('auth.forgot_password.submit') }}
        </flux:button>

        <flux:link :href="route('login')" variant="subtle" class="text-center text-sm" wire:navigate>
            {{ __('common.actions.back') }}
        </flux:link>
    </form>
</div>
