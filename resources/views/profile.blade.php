{{--
    Account self-service only. Deleting a user is an administrative action
    handled in Administration (§29) — an engineer must not be able to remove
    an account that owns document and approval history (§34 audit trail).
--}}

<x-layouts.app :title="__('auth.profile.title')">
    <x-page-header :title="__('auth.profile.title')" />

    <div class="mt-6 flex flex-col gap-6">
        <livewire:profile.update-profile-information-form />
        <livewire:profile.update-password-form />
    </div>
</x-layouts.app>
