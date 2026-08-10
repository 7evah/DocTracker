<div class="flex flex-col gap-6">

    <div class="flex flex-col gap-2">
        <flux:button
            :href="$this->isEditing() ? route('projects.show', $project) : route('projects.index')"
            icon="arrow-left"
            variant="ghost"
            size="sm"
            class="self-start"
            wire:navigate
        >
            {{ __('common.actions.back') }}
        </flux:button>

        <x-page-header
            :title="$this->isEditing() ? __('projects.edit_heading', ['name' => $project->name]) : __('projects.create_heading')"
        />
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <x-panel :title="__('projects.singular')" icon="folder">
            {{-- Single column on mobile, two from `sm` (§42 stacked forms). --}}
            <div class="grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
                <flux:input
                    wire:model="project_code"
                    :label="__('projects.fields.project_code')"
                    :description="__('projects.hints.project_code')"
                    required
                    autofocus
                    class="font-mono"
                />

                <flux:select wire:model="status" :label="__('projects.fields.status')" required>
                    @foreach ($statuses as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="name"
                    :label="__('projects.fields.name')"
                    required
                    class="sm:col-span-2"
                />

                <flux:input wire:model="client" :label="__('projects.fields.client')" />

                <flux:input wire:model="location" :label="__('projects.fields.location')" />

                <flux:select
                    wire:model="manager_id"
                    :label="__('projects.fields.manager')"
                    :description="__('projects.hints.manager')"
                    class="sm:col-span-2"
                >
                    <flux:select.option value="">{{ __('projects.no_manager') }}</flux:select.option>
                    @foreach ($managers as $id => $managerName)
                        <flux:select.option :value="$id">{{ $managerName }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="start_date"
                    :label="__('projects.fields.start_date')"
                    type="date"
                />

                <flux:input
                    wire:model="end_date"
                    :label="__('projects.fields.end_date')"
                    :description="__('projects.hints.dates')"
                    type="date"
                />

                <flux:textarea
                    wire:model="description"
                    :label="__('projects.fields.description')"
                    rows="4"
                    class="sm:col-span-2"
                />
            </div>
        </x-panel>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary" icon="check">
                <span wire:loading.remove wire:target="save">{{ __('common.actions.save') }}</span>
                <span wire:loading wire:target="save">{{ __('common.states.saving') }}</span>
            </flux:button>

            <flux:button
                :href="$this->isEditing() ? route('projects.show', $project) : route('projects.index')"
                variant="ghost"
                wire:navigate
            >
                {{ __('common.actions.cancel') }}
            </flux:button>
        </div>
    </form>
</div>
