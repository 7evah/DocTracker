<div class="flex flex-col gap-6">

    <div class="flex flex-col gap-2">
        <flux:button :href="route('documents.index')" icon="arrow-left" variant="ghost" size="sm" class="self-start" wire:navigate>
            {{ __('documents.title') }}
        </flux:button>

        <x-page-header :title="__('documents.create_heading')" />
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">

        <x-panel :title="__('documents.singular')" icon="document-text">
            <div class="grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
                <flux:select wire:model.live="project_id" :label="__('documents.fields.project')" required>
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($projects as $id => $label)
                        <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="discipline_id" :label="__('documents.fields.discipline')" required>
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($disciplines as $id => $label)
                        <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="document_number"
                    :label="__('documents.fields.document_number')"
                    :description="__('documents.hints.document_number')"
                    class="font-mono"
                    required
                />

                <flux:input
                    wire:model="revision"
                    :label="__('documents.fields.revision')"
                    :description="__('documents.hints.revision')"
                    class="font-mono"
                    required
                />

                <flux:input
                    wire:model="title"
                    :label="__('documents.fields.title')"
                    field:class="sm:col-span-2"
                    required
                />

                <flux:textarea
                    wire:model="description"
                    :label="__('documents.fields.description')"
                    rows="3"
                    field:class="sm:col-span-2"
                />
            </div>
        </x-panel>

        <x-panel :title="__('documents.fields.file')" icon="paper-clip">
            <div class="flex max-w-3xl flex-col gap-5">
                <flux:field>
                    <flux:label>{{ __('documents.fields.file') }}</flux:label>

                    <flux:input type="file" wire:model="file" />

                    <flux:description>
                        {{ __('documents.hints.file', [
                            'formats' => strtoupper(implode(', ', config('documents.allowed_extensions'))),
                            'size' => Number::fileSize(config('documents.max_size_kb') * 1024),
                        ]) }}
                    </flux:description>

                    <flux:error name="file" />
                </flux:field>

                {{-- Upload progress: Livewire emits these events natively (§20). --}}
                <div
                    x-data="{ uploading: false, progress: 0 }"
                    x-on:livewire-upload-start="uploading = true; progress = 0"
                    x-on:livewire-upload-finish="uploading = false"
                    x-on:livewire-upload-cancel="uploading = false"
                    x-on:livewire-upload-error="uploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                    x-show="uploading"
                    x-cloak
                    class="flex items-center gap-3"
                >
                    <div
                        class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700"
                        role="progressbar"
                        aria-label="{{ __('common.actions.upload') }}"
                        x-bind:aria-valuenow="progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div class="h-full rounded-full bg-brand-600 transition-[width]" x-bind:style="`width: ${progress}%`"></div>
                    </div>
                    <span class="text-xs tabular-nums text-zinc-500" x-text="`${progress}%`"></span>
                </div>

                @if ($file && ! $errors->has('file'))
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon name="document" class="shrink-0 text-zinc-400" aria-hidden="true" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $file->getClientOriginalName() }}</p>
                            <p class="text-xs text-zinc-500">{{ Number::fileSize($file->getSize()) }}</p>
                        </div>
                        <flux:icon name="check-circle" variant="micro" class="shrink-0 text-green-600" aria-hidden="true" />
                    </div>
                @endif

                <flux:textarea
                    wire:model="version_notes"
                    :label="__('documents.fields.version_notes')"
                    :description="__('documents.hints.version_notes')"
                    rows="3"
                />
            </div>
        </x-panel>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary" icon="arrow-up-tray" wire:target="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ __('documents.create') }}</span>
                <span wire:loading wire:target="save">{{ __('common.states.saving') }}</span>
            </flux:button>

            <flux:button :href="route('documents.index')" variant="ghost" wire:navigate>
                {{ __('common.actions.cancel') }}
            </flux:button>
        </div>
    </form>
</div>
