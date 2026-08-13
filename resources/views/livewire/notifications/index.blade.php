<div class="flex flex-col gap-6">

    <x-page-header :title="__('notifications.title')" :description="__('notifications.subtitle')">
        <x-slot:actions>
            @if ($unread > 0)
                <flux:button wire:click="markAllReadWithToast" icon="check" variant="outline">
                    {{ __('notifications.mark_all_read') }}
                </flux:button>
            @endif

            @if ($hasRead)
                <flux:button
                    wire:click="deleteRead"
                    wire:confirm="{{ __('notifications.delete_read') }} ?"
                    icon="trash"
                    variant="ghost"
                >
                    <span class="max-sm:hidden">{{ __('notifications.delete_read') }}</span>
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Filter as a segmented control: three mutually exclusive states,
         which reads better than a select for so few options. --}}
    <flux:radio.group wire:model.live="filter" variant="segmented" class="self-start">
        <flux:radio value="all" :label="__('notifications.all')" />
        <flux:radio value="unread" :label="__('notifications.unread').($unread > 0 ? ' ('.$unread.')' : '')" />
        <flux:radio value="read" :label="__('notifications.read')" />
    </flux:radio.group>

    @if ($notifications->isEmpty())
        <x-empty-state
            icon="bell"
            :title="$filter === 'unread' ? __('notifications.empty_unread') : __('notifications.empty')"
            :description="$filter === 'unread' ? __('notifications.empty_unread_hint') : __('notifications.empty_hint')"
        />
    @else
        <x-panel :padded="false" wire:loading.class="opacity-60">
            <ul class="flex flex-col">
                @foreach ($notifications as $notification)
                    <x-notification-item
                        :notification="$notification"
                        :last="$loop->last"
                        wire:key="notif-{{ $notification->id }}"
                    >
                        @if (($notification->data['url'] ?? null))
                            <flux:button
                                wire:click="open('{{ $notification->id }}')"
                                size="xs"
                                variant="ghost"
                                icon="arrow-right"
                            >
                                {{ __('notifications.open') }}
                            </flux:button>
                        @endif

                        @if ($notification->read_at === null)
                            <flux:button
                                wire:click="markReadWithToast('{{ $notification->id }}')"
                                size="xs"
                                variant="ghost"
                                icon="check"
                            >
                                {{ __('notifications.mark_read') }}
                            </flux:button>
                        @else
                            <flux:button
                                wire:click="markUnread('{{ $notification->id }}')"
                                size="xs"
                                variant="ghost"
                                icon="arrow-uturn-left"
                            >
                                {{ __('notifications.mark_unread') }}
                            </flux:button>
                        @endif

                        <flux:button
                            wire:click="delete('{{ $notification->id }}')"
                            size="xs"
                            variant="ghost"
                            icon="trash"
                            :aria-label="__('notifications.delete')"
                        />
                    </x-notification-item>
                @endforeach
            </ul>
        </x-panel>

        <flux:pagination :paginator="$notifications" />
    @endif
</div>
