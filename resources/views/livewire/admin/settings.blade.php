<div class="flex flex-col gap-6">

    <x-page-header :title="__('admin.settings.title')" :description="__('admin.settings.subtitle')" />

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <form wire:submit="save" class="flex flex-col gap-6 xl:col-span-2">
            <x-panel :title="__('admin.settings.title')" icon="cog-6-tooth">
                <div class="flex max-w-xl flex-col gap-5">
                    @foreach ($schema as $key => $definition)
                        @if ($definition['type'] === 'bool')
                            <flux:checkbox
                                wire:model="values.{{ $key }}"
                                :label="__('admin.settings.fields.'.$key)"
                                :description="Illuminate\Support\Facades\Lang::has('admin.settings.hints.'.$key)
                                    ? __('admin.settings.hints.'.$key)
                                    : null"
                                wire:key="set-{{ $key }}"
                            />
                        @else
                            <flux:input
                                wire:model="values.{{ $key }}"
                                type="number"
                                :label="__('admin.settings.fields.'.$key)"
                                :description="Illuminate\Support\Facades\Lang::has('admin.settings.hints.'.$key)
                                    ? __('admin.settings.hints.'.$key)
                                    : null"
                                wire:key="set-{{ $key }}"
                            />
                        @endif

                        <flux:error name="values.{{ $key }}" />
                    @endforeach
                </div>

                <div class="mt-6">
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ __('common.actions.save') }}
                    </flux:button>
                </div>
            </x-panel>
        </form>

        <div class="flex flex-col gap-6">
            <x-panel :title="__('admin.settings.system')" icon="information-circle">
                <dl class="flex flex-col gap-3 text-sm">
                    @foreach ($info as $caption => $value)
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ $caption }}</dt>
                            <dd class="text-end font-mono text-xs">{{ $value }}</dd>
                        </div>

                        @unless ($loop->last)
                            <flux:separator variant="subtle" />
                        @endunless
                    @endforeach
                </dl>
            </x-panel>

            <flux:callout icon="lock-closed" variant="secondary">
                <flux:callout.text>{{ __('admin.settings.env_note') }}</flux:callout.text>
            </flux:callout>
        </div>
    </div>
</div>
