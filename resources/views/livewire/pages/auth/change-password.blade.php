<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Replace the temporary password with one the user chose (§4).
     *
     * Clearing must_change_password is what releases the account from
     * EnsurePasswordIsChanged, so it happens only once a real password is
     * stored — never on merely visiting this page.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ], attributes: [
            'password' => __('passwords.change.password'),
        ]);

        $user = auth()->user();

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        // Belt and braces: the temporary password is already cleared when it
        // is used to sign in, but a second one could have been issued since.
        $user->clearTemporaryPassword();

        // The password changed, so every other session for this account is
        // now holding a credential the user has just replaced.
        auth()->logoutOtherDevices($validated['password']);

        session()->flash('toast', __('passwords.change.done'));

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <flux:heading size="xl" level="1">{{ __('passwords.change.heading') }}</flux:heading>
    <flux:subheading class="mt-1 mb-6">{{ __('passwords.change.intro') }}</flux:subheading>

    <form wire:submit="save" class="flex flex-col gap-5">
        <flux:input
            wire:model="password"
            :label="__('passwords.change.password')"
            type="password"
            name="password"
            error:name="password"
            required
            autofocus
            autocomplete="new-password"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            :label="__('passwords.change.confirmation')"
            type="password"
            name="password_confirmation"
            error:name="password_confirmation"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:button type="submit" variant="primary" class="w-full">
            <span wire:loading.remove wire:target="save">{{ __('passwords.change.submit') }}</span>
            <span wire:loading wire:target="save">{{ __('common.states.loading') }}</span>
        </flux:button>

    </form>

    {{-- Outside the form above: a form cannot be nested in another. Logout
         stays reachable so somebody who opened this by mistake is not stuck
         on it (see EnsurePasswordIsChanged::ALLOWED). --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
        @csrf
        <flux:button type="submit" variant="subtle" size="sm">
            {{ __('navigation.logout') }}
        </flux:button>
    </form>
</div>
