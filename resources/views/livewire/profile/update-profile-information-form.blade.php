<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $department = '';

    public string $job_title = '';

    public string $phone = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->department = $user->department ?? '';
        $this->job_title = $user->job_title ?? '';
        $this->phone = $user->phone ?? '';
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'department' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);

        Flux::toast(text: __('auth.profile.saved'), variant: 'success');
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<x-panel :title="__('auth.profile.information')" icon="user-circle">
    <p class="mb-5 text-sm text-zinc-500 dark:text-zinc-400">{{ __('auth.profile.information_hint') }}</p>

    <form wire:submit="updateProfileInformation" class="flex max-w-xl flex-col gap-5">
        <flux:input wire:model="name" :label="__('common.labels.name')" required autocomplete="name" />

        <flux:input wire:model="email" :label="__('common.labels.email')" type="email" required autocomplete="username" />

        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.text>
                    {{ __('Votre adresse e-mail n’est pas vérifiée.') }}
                    <flux:callout.link href="#" wire:click.prevent="sendVerification">
                        {{ __('Renvoyer le lien de vérification') }}
                    </flux:callout.link>
                </flux:callout.text>
            </flux:callout>

            @if (session('status') === 'verification-link-sent')
                <flux:text class="text-green-600">{{ __('auth.verify_email.sent') }}</flux:text>
            @endif
        @endif

        {{-- Single column on mobile, two from `sm` (§42). --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <flux:input wire:model="department" :label="__('common.labels.department')" />
            <flux:input wire:model="job_title" :label="__('Fonction')" />
        </div>

        <flux:input wire:model="phone" :label="__('common.labels.phone')" type="tel" />

        {{-- Role is administrative: shown read-only, changed only in admin (§29). --}}
        @if ($role = auth()->user()->primaryRole())
            <flux:field>
                <flux:label>{{ __('common.labels.role') }}</flux:label>
                <div><flux:badge color="zinc">{{ __('enums.role.'.$role) }}</flux:badge></div>
                <flux:description>{{ __('enums.role_description.'.$role) }}</flux:description>
            </flux:field>
        @endif

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">{{ __('common.actions.save') }}</flux:button>

            <flux:text
                x-data="{ shown: false }"
                x-on:profile-updated.window="shown = true; setTimeout(() => shown = false, 2500)"
                x-show="shown"
                x-cloak
                class="text-sm text-green-600"
            >
                {{ __('auth.profile.saved') }}
            </flux:text>
        </div>
    </form>
</x-panel>
