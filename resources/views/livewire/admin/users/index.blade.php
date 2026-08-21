<div class="flex flex-col gap-6">

    <x-page-header :title="__('admin.users.title')" :description="__('admin.users.subtitle')">
        <x-slot:actions>
            <flux:button icon="plus" variant="primary" wire:click="$dispatch('new-user')">
                {{ __('admin.users.create') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :placeholder="__('admin.users.search')"
            icon="magnifying-glass"
            clearable
            class="lg:max-w-sm"
            :label="__('common.actions.search')"
            label:class="sr-only"
        />

        <div class="grid grid-cols-2 gap-3 lg:flex lg:items-center">
            <flux:select wire:model.live="role" class="lg:w-48">
                <flux:select.option value="">{{ __('admin.users.filters.role') }}</flux:select.option>
                @foreach ($roles as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" class="lg:w-44">
                <flux:select.option value="">{{ __('admin.users.filters.status') }}</flux:select.option>
                @foreach ($statuses as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->hasFilters())
                <flux:button wire:click="resetFilters" variant="subtle" icon="x-mark" size="sm" class="max-lg:col-span-2">
                    {{ __('common.actions.reset_filters') }}
                </flux:button>
            @endif
        </div>
    </div>

    @if ($users->isEmpty())
        <x-empty-state
            icon="users"
            :title="$this->hasFilters() ? __('admin.users.empty.filtered_title') : __('admin.users.empty.title')"
            :description="$this->hasFilters() ? __('admin.users.empty.filtered_description') : null"
        >
            @if ($this->hasFilters())
                <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                    {{ __('common.actions.reset_filters') }}
                </flux:button>
            @endif
        </x-empty-state>
    @else
        {{-- Desktop table --}}
        <div class="max-lg:hidden" wire:loading.class="opacity-60">
            <x-panel :padded="false">
                {{--
                    Flux's table is table-fixed, so column widths come from this
                    header row alone — and on a wide screen six columns of short
                    content always leave slack somewhere. The only real question
                    is where that slack goes, and the answer is: spread thinly
                    across the columns, never pooled.

                    Letting the table size to its content (no w-full) pools it
                    into one large void to the right of the table, inside the
                    panel's own border — which reads as a broken layout rather
                    than a deliberate margin. Percentages plus w-full spread it
                    instead, so each column simply has a little trailing room
                    and the row still reaches the panel edge.

                    The percentages are weighted by how long each column's
                    content actually runs — Nom (name + email) and the two
                    free-text columns carry the most, Statut holds a fixed-width
                    badge and needs the least — so no single column ends up
                    conspicuously empty. min-width still forces genuine overflow
                    (and therefore a real scrollbar via Flux's own
                    <ui-table-scroll-area>; see app.css) once the viewport is
                    too narrow for even these shares (§16, §42).
                --}}
                {{--
                    Flux zeroes the outer padding of the first and last columns
                    (first:ps-0 / last:pe-0) so a table can sit flush inside a
                    container that already has its own padding. This panel is
                    :padded="false", so nothing reinstates it and the avatar
                    ends up pressed against the panel border. Padding both edges
                    back in here indents the row to match the padding every
                    other panel in the app uses.

                    Targeting the cells from the table (rather than adding
                    first:ps-4 to each column and cell) keeps it in one place
                    and wins on specificity — a descendant selector outranks
                    Flux's own first:/last: utilities regardless of source order.
                --}}
                <flux:table
                    class="w-full min-w-175
                        [&_th:first-child]:ps-4 [&_td:first-child]:ps-4
                        [&_th:last-child]:pe-4 [&_td:last-child]:pe-4"
                >
                    <flux:table.columns>
                        <flux:table.column class="w-[26%]">{{ __('common.labels.name') }}</flux:table.column>
                        <flux:table.column class="w-[20%]">{{ __('common.labels.department') }}</flux:table.column>
                        <flux:table.column class="w-[16%]">{{ __('admin.users.roles') }}</flux:table.column>
                        <flux:table.column class="w-[11%]">{{ __('common.labels.status') }}</flux:table.column>
                        <flux:table.column class="w-[20%]">{{ __('admin.users.last_activity') }}</flux:table.column>
                        <flux:table.column align="end" class="w-[7%]"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-2.5">
                                        <x-user-avatar :user="$user" size="sm" />
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium">
                                                {{ $user->name }}
                                                @if ($user->is(auth()->user()))
                                                    <span class="text-xs font-normal text-zinc-400">({{ __('Vous') }})</span>
                                                @endif
                                            </p>
                                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                {{-- truncate, not just a text colour: table-fixed
                                     genuinely bounds this cell's width now, so a
                                     long department name needs a clean ellipsis
                                     instead of bleeding into the next column. --}}
                                <flux:table.cell class="truncate text-zinc-500 dark:text-zinc-400">
                                    {{ $user->department ?: '—' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    @forelse ($user->roles as $role)
                                        <flux:badge size="sm" color="zinc" class="me-1">
                                            {{ __('enums.role.'.$role->name) }}
                                        </flux:badge>
                                    @empty
                                        <span class="text-xs text-zinc-400">{{ __('admin.users.no_role') }}</span>
                                    @endforelse
                                </flux:table.cell>

                                <flux:table.cell>
                                    <x-badge :status="$user->status" />
                                </flux:table.cell>

                                <flux:table.cell class="truncate text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $user->last_active_at?->diffForHumans() ?? __('admin.users.never_connected') }}
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button icon="ellipsis-horizontal" variant="ghost" size="xs" :aria-label="__('common.actions.edit')" />

                                        <flux:menu>
                                            <flux:menu.item
                                                icon="pencil-square"
                                                wire:click="$dispatch('edit-user', { userId: {{ $user->id }} })"
                                            >
                                                {{ __('common.actions.edit') }}
                                            </flux:menu.item>

                                            {{-- Disabled with a reason rather than hidden, so the
                                                 rule is discoverable rather than mysterious (§37). --}}
                                            <flux:menu.item
                                                :icon="$user->status === App\Enums\UserStatus::Active ? 'pause-circle' : 'check-circle'"
                                                wire:click="toggleStatus({{ $user->id }})"
                                                :disabled="! $user->canHaveStatusChangedBy(auth()->user())"
                                            >
                                                {{ $user->status === App\Enums\UserStatus::Active
                                                    ? __('admin.users.actions.deactivate')
                                                    : __('admin.users.actions.activate') }}
                                            </flux:menu.item>

                                            <flux:menu.separator />

                                            <flux:menu.item
                                                icon="trash"
                                                variant="danger"
                                                wire:click="delete({{ $user->id }})"
                                                wire:confirm="{{ __('common.confirm.irreversible') }}"
                                                :disabled="! $user->canBeDeletedBy(auth()->user())"
                                            >
                                                {{ __('common.actions.delete') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-panel>
        </div>

        {{-- Mobile cards --}}
        <div class="flex flex-col gap-3 lg:hidden" wire:loading.class="opacity-60">
            @foreach ($users as $user)
                <div
                    wire:key="user-card-{{ $user->id }}"
                    class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <div class="flex items-start gap-3">
                        <x-user-avatar :user="$user" size="sm" class="shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                        </div>
                        <x-badge :status="$user->status" />
                    </div>

                    <div class="flex flex-wrap items-center gap-1">
                        @foreach ($user->roles as $role)
                            <flux:badge size="sm" color="zinc">{{ __('enums.role.'.$role->name) }}</flux:badge>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:button
                            size="xs"
                            variant="ghost"
                            icon="pencil-square"
                            wire:click="$dispatch('edit-user', { userId: {{ $user->id }} })"
                        >
                            {{ __('common.actions.edit') }}
                        </flux:button>

                        <flux:button
                            size="xs"
                            variant="ghost"
                            :icon="$user->status === App\Enums\UserStatus::Active ? 'pause-circle' : 'check-circle'"
                            wire:click="toggleStatus({{ $user->id }})"
                            :disabled="! $user->canHaveStatusChangedBy(auth()->user())"
                        >
                            {{ $user->status === App\Enums\UserStatus::Active
                                ? __('admin.users.actions.deactivate')
                                : __('admin.users.actions.activate') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>

        <flux:pagination :paginator="$users" />
    @endif

    <livewire:admin.users.form />
</div>
