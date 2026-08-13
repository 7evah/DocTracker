<div class="flex flex-col gap-6">

    <x-page-header :title="__('admin.roles.title')" :description="__('admin.roles.subtitle')">
        <x-slot:actions>
            <flux:button wire:click="save" variant="primary" icon="check">
                {{ __('common.actions.save') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <flux:callout icon="information-circle" variant="secondary">
        <flux:callout.text>{{ __('admin.roles.administrator_note') }}</flux:callout.text>
    </flux:callout>

    {{-- Matrix: permissions down, roles across. Scrolls inside its own
         container so the page never scrolls sideways (§16). --}}
    <x-panel :padded="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-max border-collapse text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="sticky start-0 z-10 bg-white p-3 text-start font-medium dark:bg-zinc-900">
                            {{ __('admin.roles.permissions') }}
                        </th>

                        @foreach ($roles as $role)
                            <th class="p-3 text-center font-medium">
                                <div class="flex flex-col items-center gap-1">
                                    <span>{{ __('enums.role.'.$role->name) }}</span>
                                    <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                        {{ trans_choice('admin.roles.users_count', $role->users_count, ['count' => $role->users_count]) }}
                                    </span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($groups as $group => $permissions)
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                            <td
                                class="sticky start-0 z-10 bg-zinc-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400"
                                colspan="{{ $roles->count() + 1 }}"
                            >
                                {{ __('admin.roles.groups.'.$group) }}
                            </td>
                        </tr>

                        @foreach ($permissions as $permission)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="sticky start-0 z-10 bg-white p-3 dark:bg-zinc-900">
                                    <code class="font-mono text-xs text-zinc-600 dark:text-zinc-300">{{ $permission }}</code>
                                </td>

                                @foreach ($roles as $role)
                                    @php $isAdmin = $role->name === App\Enums\UserRole::Administrator->value; @endphp

                                    <td class="p-3 text-center">
                                        <flux:checkbox
                                            wire:model="matrix.{{ $role->name }}"
                                            :value="$permission"
                                            :disabled="$isAdmin"
                                            :aria-label="__('enums.role.'.$role->name).' — '.$permission"
                                            wire:key="perm-{{ $role->id }}-{{ $permission }}"
                                        />
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-panel>

    <div>
        <flux:button wire:click="save" variant="primary" icon="check">
            {{ __('common.actions.save') }}
        </flux:button>
    </div>
</div>
