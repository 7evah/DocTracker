<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PasswordReset) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <flux:heading size="xl" level="1">{{ __('auth.reset_password.title') }}</flux:heading>
    <flux:subheading class="mt-1 mb-6">{{ __('auth.forgot_password.intro') }}</flux:subheading>

    <form wire:submit="resetPassword" class="flex flex-col gap-5">
        <flux:input
            wire:model="email"
            :label="__('auth.login.email')"
            type="email"
            required
            autofocus
            autocomplete="username"
        />

        <flux:input
            wire:model="password"
            :label="__('auth.reset_password.password')"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            :label="__('auth.reset_password.confirm')"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('auth.reset_password.submit') }}
        </flux:button>
    </form>
</div>
