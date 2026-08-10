@php
    /*
    | Shared stand-in for modules that have not landed yet. Keeps every sidebar
    | link reachable so the shell is demonstrable end-to-end, while being honest
    | that the module is not built (§47 "Coming Soon").
    */
    $label = Illuminate\Support\Facades\Lang::has('navigation.'.$module)
        ? __('navigation.'.$module)
        : __('navigation.admin.'.$module);
@endphp

<x-layouts.app :title="$label">
    <x-page-header :title="$label" />

    <div class="mt-6">
        <x-empty-state
            :icon="$icon"
            :title="__('dashboard.coming_soon')"
            :description="__('Ce module sera livré dans une phase ultérieure. La navigation, les permissions et la mise en page sont déjà en place.')"
        >
            <flux:button :href="route('dashboard')" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
                {{ __('navigation.dashboard') }}
            </flux:button>
        </x-empty-state>
    </div>
</x-layouts.app>
