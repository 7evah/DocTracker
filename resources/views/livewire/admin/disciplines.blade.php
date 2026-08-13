<div class="flex flex-col gap-6">

    <x-page-header :title="__('admin.disciplines.title')" :description="__('admin.disciplines.subtitle')">
        <x-slot:actions>
            <flux:button icon="plus" variant="primary" wire:click="startNew">
                {{ __('admin.disciplines.create') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    @if ($disciplines->isEmpty())
        <x-empty-state
            icon="squares-2x2"
            :title="__('admin.disciplines.empty.title')"
            :description="__('admin.disciplines.empty.description')"
        >
            <flux:button wire:click="startNew" variant="primary" size="sm" icon="plus">
                {{ __('admin.disciplines.create') }}
            </flux:button>
        </x-empty-state>
    @else
        <x-panel :padded="false">
            <ul class="flex flex-col">
                @foreach ($disciplines as $discipline)
                    <li
                        wire:key="disc-{{ $discipline->id }}"
                        @class([
                            'flex flex-col gap-3 p-4 sm:flex-row sm:items-center',
                            'border-b border-zinc-200 dark:border-zinc-700' => ! $loop->last,
                        ])
                    >
                        <span class="grid size-10 shrink-0 place-items-center rounded-md bg-zinc-100 font-mono text-sm font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ $discipline->code }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $discipline->name }}</p>
                            @if ($discipline->description)
                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $discipline->description }}</p>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ trans_choice('admin.disciplines.documents_count', $discipline->documents_count, ['count' => $discipline->documents_count]) }}
                            </span>

                            @if ($discipline->is_active)
                                <flux:badge size="sm" color="green" icon="check-circle">
                                    {{ __('admin.disciplines.fields.is_active') }}
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" icon="pause-circle">
                                    {{ __('enums.user_status.inactive') }}
                                </flux:badge>
                            @endif

                            <flux:button
                                wire:click="edit({{ $discipline->id }})"
                                size="xs"
                                variant="ghost"
                                icon="pencil-square"
                                :aria-label="__('common.actions.edit')"
                            />

                            {{-- Disabled with an explanation when in use: the FK is
                                 restrictOnDelete, so this would fail at the database. --}}
                            <flux:button
                                wire:click="delete({{ $discipline->id }})"
                                wire:confirm="{{ __('common.confirm.irreversible') }}"
                                size="xs"
                                variant="ghost"
                                icon="trash"
                                :disabled="$discipline->documents_count > 0"
                                :tooltip="$discipline->documents_count > 0 ? __('admin.disciplines.messages.in_use') : null"
                                :aria-label="__('common.actions.delete')"
                            />
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-panel>
    @endif

    <flux:modal name="discipline-form" class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:heading size="lg">
                {{ $editing ? __('admin.disciplines.edit_heading') : __('admin.disciplines.create_heading') }}
            </flux:heading>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model="code"
                    :label="__('admin.disciplines.fields.code')"
                    :description="__('admin.disciplines.hints.code')"
                    class="font-mono"
                    required
                />

                <flux:input
                    wire:model="sort_order"
                    type="number"
                    :label="__('admin.disciplines.fields.sort_order')"
                    min="0"
                />

                <flux:input
                    wire:model="name"
                    :label="__('admin.disciplines.fields.name')"
                    class="sm:col-span-2"
                    required
                />

                <flux:textarea
                    wire:model="description"
                    :label="__('admin.disciplines.fields.description')"
                    rows="2"
                    class="sm:col-span-2"
                />
            </div>

            <flux:checkbox
                wire:model="is_active"
                :label="__('admin.disciplines.fields.is_active')"
                :description="__('admin.disciplines.hints.is_active')"
            />

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
