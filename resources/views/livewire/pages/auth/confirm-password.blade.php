<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <flux:heading size="xl" level="1">{{ __('auth.confirm_password.title') }}</flux:heading>
    <flux:subheading class="mt-1 mb-6">{{ __('auth.confirm_password.intro') }}</flux:subheading>

    <form wire:submit="confirmPassword" class="flex flex-col gap-5">
        <flux:input
            wire:model="password"
            :label="__('auth.login.password')"
            type="password"
            required
            autofocus
            autocomplete="current-password"
            viewable
        />

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('auth.confirm_password.submit') }}
        </flux:button>
    </form>
</div>
