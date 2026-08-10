<div class="flex flex-col gap-6">

    <x-page-header
        :title="__('dashboard.greeting', ['name' => auth()->user()->name])"
        :description="__('dashboard.subtitle')"
    >
        <x-slot:actions>
            @can(\App\Support\Permissions::DOCUMENTS_CREATE)
                <flux:button icon="arrow-up-tray" variant="primary" :href="route('documents.index')" wire:navigate>
                    {{ __('common.actions.upload') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <flux:callout icon="beaker" variant="secondary">
        <flux:callout.text>{{ __('common.prototype_notice') }}</flux:callout.text>
    </flux:callout>

    {{--
        KPI row (§17). Two columns on mobile so the numbers stay readable
        without horizontal scroll, four from `lg` (§42).
    --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat-card
            :label="__('dashboard.stats.projects')"
            :value="$stats['projects']"
            icon="folder"
            tone="brand"
            :href="route('projects.index')"
        />
        <x-stat-card
            :label="__('dashboard.stats.documents')"
            :value="$stats['documents']"
            icon="document-text"
            tone="brand"
            :href="route('documents.index')"
        />
        <x-stat-card
            :label="__('dashboard.stats.pending_reviews')"
            :value="$stats['pending_reviews']"
            icon="eye"
            tone="info"
            :href="route('reviews.index')"
        />
        <x-stat-card
            :label="__('dashboard.stats.pending_approvals')"
            :value="$stats['pending_approvals']"
            icon="check-badge"
            tone="info"
            :href="route('approvals.index')"
        />
        <x-stat-card
            :label="__('dashboard.stats.approved_documents')"
            :value="$stats['approved_documents']"
            icon="check-circle"
            tone="success"
        />
        <x-stat-card
            :label="__('dashboard.stats.needs_revision')"
            :value="$stats['needs_revision']"
            icon="arrow-path"
            tone="warning"
        />
        <x-stat-card
            :label="__('dashboard.stats.overdue_reviews')"
            :value="$stats['overdue_reviews']"
            icon="clock"
            tone="danger"
        />
        <x-stat-card
            :label="__('dashboard.stats.overdue_approvals')"
            :value="$stats['overdue_approvals']"
            icon="exclamation-triangle"
            tone="danger"
        />
    </div>

    {{-- Main sections: single column on mobile, 2/3 + 1/3 split from `xl`. --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <div class="flex flex-col gap-6 xl:col-span-2">
            <x-panel :title="__('dashboard.sections.recent_documents')" icon="document-text" :padded="false">
                <x-slot:actions>
                    <flux:button size="xs" variant="ghost" :href="route('documents.index')" wire:navigate>
                        {{ __('common.actions.view_all') }}
                    </flux:button>
                </x-slot:actions>

                <div class="p-4">
                    <x-empty-state
                        icon="document-text"
                        :title="__('dashboard.empty.documents')"
                        :description="__('dashboard.empty.documents_hint')"
                        compact
                    />
                </div>
            </x-panel>

            <x-panel :title="__('dashboard.sections.pending_reviews')" icon="eye" :padded="false">
                <x-slot:actions>
                    <flux:button size="xs" variant="ghost" :href="route('reviews.index')" wire:navigate>
                        {{ __('common.actions.view_all') }}
                    </flux:button>
                </x-slot:actions>

                <div class="p-4">
                    <x-empty-state
                        icon="eye"
                        :title="__('dashboard.empty.reviews')"
                        :description="__('dashboard.empty.reviews_hint')"
                        compact
                    />
                </div>
            </x-panel>
        </div>

        <div class="flex flex-col gap-6">
            <x-panel :title="__('dashboard.sections.upcoming_deadlines')" icon="calendar-days" :padded="false">
                <div class="p-4">
                    <x-empty-state
                        icon="calendar-days"
                        :title="__('dashboard.empty.deadlines')"
                        :description="__('dashboard.empty.deadlines_hint')"
                        compact
                    />
                </div>
            </x-panel>

            <x-panel :title="__('dashboard.sections.recent_activity')" icon="clock" :padded="false">
                <div class="p-4">
                    <x-empty-state
                        icon="clock"
                        :title="__('dashboard.empty.activity')"
                        :description="__('dashboard.empty.activity_hint')"
                        compact
                    />
                </div>
            </x-panel>
        </div>
    </div>
</div>
