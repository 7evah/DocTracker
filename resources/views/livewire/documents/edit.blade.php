<div class="flex flex-col gap-6">

    <div class="flex flex-col gap-2">
        <flux:button :href="route('documents.show', $document)" icon="arrow-left" variant="ghost" size="sm" class="self-start" wire:navigate>
            {{ $document->document_number }}
        </flux:button>

        <x-page-header :title="__('documents.edit_heading', ['number' => $document->document_number])" />
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <x-panel :title="__('documents.singular')" icon="document-text">
            <div class="grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
                <flux:input
                    wire:model="document_number"
                    :label="__('documents.fields.document_number')"
                    :description="__('documents.hints.document_number')"
                    class="font-mono"
                    required
                />

                <flux:select wire:model="discipline_id" :label="__('documents.fields.discipline')" required>
                    @foreach ($disciplines as $id => $label)
                        <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="title" :label="__('documents.fields.title')" field:class="sm:col-span-2" required />

                <flux:textarea wire:model="description" :label="__('documents.fields.description')" rows="4" field:class="sm:col-span-2" />
            </div>

            {{-- Make the immutability rule explicit rather than leaving the
                 user to wonder where the file field went (§37). --}}
            <flux:callout icon="information-circle" variant="secondary" class="mt-5 max-w-3xl">
                <flux:callout.text>
                    {{ __('Le fichier ne peut pas être remplacé ici. Pour modifier le contenu, ajoutez une nouvelle révision depuis la fiche du document.') }}
                </flux:callout.text>
            </flux:callout>
        </x-panel>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary" icon="check">
                <span wire:loading.remove wire:target="save">{{ __('common.actions.save') }}</span>
                <span wire:loading wire:target="save">{{ __('common.states.saving') }}</span>
            </flux:button>

            <flux:button :href="route('documents.show', $document)" variant="ghost" wire:navigate>
                {{ __('common.actions.cancel') }}
            </flux:button>
        </div>
    </form>
</div>
