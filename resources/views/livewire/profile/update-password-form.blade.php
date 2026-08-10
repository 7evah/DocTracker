<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');

        Flux::toast(text: __('auth.profile.saved'), variant: 'success');
    }
}; ?>

<x-panel :title="__('auth.profile.password')" icon="lock-closed">
    <p class="mb-5 text-sm text-zinc-500 dark:text-zinc-400">{{ __('auth.profile.password_hint') }}</p>

    <form wire:submit="updatePassword" class="flex max-w-xl flex-col gap-5">
        <flux:input
            wire:model="current_password"
            :label="__('auth.profile.current_password')"
            type="password"
            autocomplete="current-password"
            viewable
        />

        <flux:input
            wire:model="password"
            :label="__('auth.profile.new_password')"
            type="password"
            autocomplete="new-password"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            :label="__('auth.reset_password.confirm')"
            type="password"
            autocomplete="new-password"
            viewable
        />

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">{{ __('common.actions.save') }}</flux:button>

            <flux:text
                x-data="{ shown: false }"
                x-on:password-updated.window="shown = true; setTimeout(() => shown = false, 2500)"
                x-show="shown"
                x-cloak
                class="text-sm text-green-600"
            >
                {{ __('auth.profile.saved') }}
            </flux:text>
        </div>
    </form>
</x-panel>
