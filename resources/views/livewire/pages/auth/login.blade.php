<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <flux:heading size="xl" level="1">{{ __('auth.login.heading') }}</flux:heading>
    <flux:subheading class="mt-1 mb-6">{{ __('auth.login.subheading') }}</flux:subheading>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="login" class="flex flex-col gap-5">
        <flux:input
            wire:model="form.email"
            :label="__('auth.login.email')"
            type="email"
            name="email"
            required
            autofocus
            autocomplete="username"
            placeholder="prenom.nom@jesa.com"
        />

        <flux:field>
            <div class="flex items-center justify-between">
                <flux:label>{{ __('auth.login.password') }}</flux:label>

                @if (Route::has('password.request'))
                    <flux:link :href="route('password.request')" variant="subtle" class="text-xs" wire:navigate>
                        {{ __('auth.login.forgot') }}
                    </flux:link>
                @endif
            </div>

            <flux:input
                wire:model="form.password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                viewable
            />

            <flux:error name="form.password" />
        </flux:field>

        <flux:checkbox wire:model="form.remember" :label="__('auth.login.remember')" />

        <flux:button type="submit" variant="primary" class="w-full">
            <span wire:loading.remove wire:target="login">{{ __('auth.login.submit') }}</span>
            <span wire:loading wire:target="login">{{ __('common.states.loading') }}</span>
        </flux:button>
    </form>

    {{-- Demo credentials are seeded in local only; surfacing them here keeps
         the prototype walkthrough friction-free (§55). --}}
    @if (app()->environment('local'))
        <div class="mt-8 rounded-lg border border-dashed border-zinc-300 p-3 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Comptes de démonstration') }}</p>
            <p class="mt-1">admin@docflow.test · chef.projet@docflow.test · ingenieur1@docflow.test</p>
            <p>verificateur1@docflow.test · approbateur@docflow.test · lecteur@docflow.test</p>
            <p class="mt-1">{{ __('Mot de passe') }} : <code class="font-mono">password</code></p>
        </div>
    @endif
</div>
