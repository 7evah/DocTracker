<div class="flex flex-col gap-6">

    <x-page-header :title="__('admin.workflows.title')" :description="__('admin.workflows.subtitle')">
        <x-slot:actions>
            <flux:button icon="plus" variant="primary" wire:click="startNew">
                {{ __('admin.workflows.create') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    @if ($workflows->isEmpty())
        <x-empty-state
            icon="check-badge"
            :title="__('admin.workflows.empty.title')"
            :description="__('admin.workflows.empty.description')"
        >
            <flux:button wire:click="startNew" variant="primary" size="sm" icon="plus">
                {{ __('admin.workflows.create') }}
            </flux:button>
        </x-empty-state>
    @else
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @foreach ($workflows as $workflow)
                <x-panel wire:key="wf-{{ $workflow->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $workflow->name }}</h3>

                                @if ($workflow->is_default)
                                    <flux:badge size="sm" color="sky">{{ __('admin.workflows.fields.is_default') }}</flux:badge>
                                @endif

                                @if (! $workflow->is_active)
                                    <flux:badge size="sm" color="zinc" icon="pause-circle">
                                        {{ __('enums.user_status.inactive') }}
                                    </flux:badge>
                                @endif
                            </div>

                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $workflow->project?->project_code ?? __('admin.workflows.global') }}
                                · {{ trans_choice('admin.workflows.step_count', $workflow->steps->count(), ['count' => $workflow->steps->count()]) }}
                            </p>

                            @if ($workflow->description)
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $workflow->description }}</p>
                            @endif
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <flux:button
                                wire:click="edit({{ $workflow->id }})"
                                size="xs"
                                variant="ghost"
                                icon="pencil-square"
                                :aria-label="__('common.actions.edit')"
                            />
                            <flux:button
                                wire:click="delete({{ $workflow->id }})"
                                wire:confirm="{{ __('common.confirm.irreversible') }}"
                                size="xs"
                                variant="ghost"
                                icon="trash"
                                :aria-label="__('common.actions.delete')"
                            />
                        </div>
                    </div>

                    <ol class="mt-4 flex flex-col gap-2">
                        @foreach ($workflow->steps as $step)
                            <li class="flex items-center gap-3 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-semibold text-white">
                                    {{ $step->step_order }}
                                </span>
                                <span class="min-w-0 flex-1 truncate text-sm">{{ $step->displayLabel() }}</span>
                                <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $step->turnaround_days ?? 3 }} j
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </x-panel>
            @endforeach
        </div>
    @endif

    <flux:modal name="workflow-form" class="max-w-2xl">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:heading size="lg">
                {{ $editing ? __('admin.workflows.edit_heading') : __('admin.workflows.create_heading') }}
            </flux:heading>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('admin.workflows.fields.name')" required />

                <flux:select
                    wire:model="project_id"
                    :label="__('admin.workflows.fields.project')"
                    :description="__('admin.workflows.hints.project')"
                >
                    <flux:select.option value="">{{ __('admin.workflows.global') }}</flux:select.option>
                    @foreach ($projects as $id => $code)
                        <flux:select.option :value="$id">{{ $code }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea
                    wire:model="description"
                    :label="__('admin.workflows.fields.description')"
                    rows="2"
                    field:class="sm:col-span-2"
                />
            </div>

            <div class="flex flex-wrap gap-6">
                <flux:checkbox wire:model="is_active" :label="__('admin.workflows.fields.is_active')" />
                <flux:checkbox
                    wire:model="is_default"
                    :label="__('admin.workflows.fields.is_default')"
                    :description="__('admin.workflows.hints.is_default')"
                />
            </div>

            <flux:separator />

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium">{{ __('admin.workflows.steps') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('admin.workflows.hints.steps') }}</p>
                    </div>

                    <flux:button wire:click="addStep" type="button" size="xs" variant="ghost" icon="plus">
                        {{ __('admin.workflows.add_step') }}
                    </flux:button>
                </div>

                <div class="flex flex-col gap-4">
                    @foreach ($steps as $index => $step)
                        <div
                            wire:key="step-{{ $index }}"
                            class="flex flex-col gap-4 rounded-lg border border-zinc-200 p-4 sm:flex-row sm:items-center dark:border-zinc-700"
                        >
                            {{-- Step number as a badge, echoing the approval
                                 stepper's own visual language, rather than
                                 leaving "which step is this" to a plain
                                 number input buried in the grid (§43). Live
                                 so it tracks the field if renumbered. --}}
                            <span
                                class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-600 text-sm font-semibold text-white dark:bg-brand-500"
                                aria-hidden="true"
                            >
                                {{ $step['step_order'] }}
                            </span>

                            <div class="grid flex-1 grid-cols-2 items-end gap-x-4 gap-y-5 sm:grid-cols-12">
                                <flux:input
                                    wire:model.live="steps.{{ $index }}.step_order"
                                    type="number"
                                    min="1"
                                    :label="__('admin.workflows.fields.step_order')"
                                    field:class="col-span-2 sm:col-span-2"
                                />

                                <flux:select
                                    wire:model="steps.{{ $index }}.role"
                                    :label="__('admin.workflows.fields.role')"
                                    field:class="col-span-2 sm:col-span-4"
                                >
                                    @foreach ($roles as $value => $label)
                                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:input
                                    wire:model="steps.{{ $index }}.label"
                                    :label="__('admin.workflows.fields.label')"
                                    field:class="col-span-2 sm:col-span-4"
                                />

                                <flux:input
                                    wire:model="steps.{{ $index }}.turnaround_days"
                                    type="number"
                                    min="1"
                                    :label="__('admin.workflows.fields.turnaround_days')"
                                    field:class="col-span-2 sm:col-span-2"
                                />
                            </div>

                            <flux:button
                                wire:click="removeStep({{ $index }})"
                                type="button"
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                :disabled="count($steps) <= 1"
                                :aria-label="__('admin.workflows.remove_step')"
                                class="self-end sm:self-center"
                            />
                        </div>
                    @endforeach
                </div>

                <flux:error name="steps" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('common.actions.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="check">
                    {{ __('common.actions.save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
