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
        {{--
            error:name is not redundant with wire:model here. Flux derives the
            field it shows errors for from wire:model — but only as a default,
            and the explicit name="email" (which password managers and browser
            autofill rely on) overrides it. Livewire registers the failure under
            "form.email" while Flux would look under "email", so a wrong
            password produced no message at all: the form simply sat there.
        --}}
        <flux:input
            wire:model="form.email"
            :label="__('auth.login.email')"
            type="email"
            name="email"
            error:name="form.email"
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
            <p class="mt-1">adminjesa@yopmail.com · chef.projet@yopmail.com</p>
            <p>ingenieur1@yopmail.com · verificateur1@yopmail.com</p>
            <p>approbateur@yopmail.com · lecteurjesa@yopmail.com</p>
            <p class="mt-1">{{ __('Mot de passe') }} : <code class="font-mono">password</code></p>
        </div>
    @endif
</div>
