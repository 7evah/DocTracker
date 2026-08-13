<div>
    <flux:dropdown position="bottom" align="end">
        {{-- The count is in the accessible name, not only the badge, so a
             screen reader announces "3 unread" rather than just "Notifications". --}}
        <flux:button
            wire:click="toggle"
            variant="subtle"
            icon="bell"
            :aria-label="$unread > 0
                ? trans_choice('notifications.bell_label', $unread, ['count' => $unread])
                : __('notifications.bell_label_none')"
        >
            @if ($unread > 0)
                <flux:badge size="sm" color="red" class="absolute -end-1 -top-1">
                    {{ $unread > 9 ? '9+' : $unread }}
                </flux:badge>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            <div class="flex items-center justify-between px-2 py-1.5">
                <span class="text-sm font-medium text-zinc-900 dark:text-white">
                    {{ __('notifications.recent') }}
                </span>

                @if ($unread > 0)
                    <flux:button wire:click="markAllRead" size="xs" variant="ghost">
                        {{ __('notifications.mark_all_read') }}
                    </flux:button>
                @endif
            </div>

            <flux:menu.separator />

            @if ($recent->isEmpty())
                <div class="px-2 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('notifications.empty') }}
                </div>
            @else
                <ul class="max-h-80 overflow-y-auto">
                    @foreach ($recent as $notification)
                        <x-notification-item
                            :notification="$notification"
                            :last="$loop->last"
                            compact
                            wire:key="bell-{{ $notification->id }}"
                            role="button"
                            tabindex="0"
                            wire:click="open('{{ $notification->id }}')"
                            class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800"
                        />
                    @endforeach
                </ul>
            @endif

            <flux:menu.separator />

            <flux:menu.item icon="inbox" :href="route('notifications.index')" wire:navigate>
                {{ __('notifications.view_all') }}
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</div>
