<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<div>
    <flux:heading size="xl" level="1">{{ __('auth.verify_email.title') }}</flux:heading>
    <flux:subheading class="mt-1 mb-6">{{ __('auth.verify_email.intro') }}</flux:subheading>

    @if (session('status') === 'verification-link-sent')
        <flux:callout variant="success" icon="check-circle" class="mb-4">
            <flux:callout.text>{{ __('auth.verify_email.sent') }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="flex flex-col gap-3">
        <flux:button wire:click="sendVerification" variant="primary" class="w-full">
            {{ __('auth.verify_email.resend') }}
        </flux:button>

        <flux:button wire:click="logout" variant="ghost" class="w-full">
            {{ __('navigation.logout') }}
        </flux:button>
    </div>
</div>
