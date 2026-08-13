<div>
    <flux:modal name="task-form" class="max-w-xl">
        <form wire:submit="save" class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">
                    {{ $this->isEditing() ? __('tasks.edit_heading') : __('tasks.create_heading') }}
                </flux:heading>
            </div>

            <flux:input wire:model="title" :label="__('tasks.fields.title')" required />

            <flux:textarea wire:model="description" :label="__('tasks.fields.description')" rows="3" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @if ($lockedProjectId)
                    {{-- Locked by the host page; shown read-only so the
                         context is still visible rather than mysteriously absent. --}}
                    <flux:field>
                        <flux:label>{{ __('tasks.fields.project') }}</flux:label>
                        <flux:input value="{{ $projects[$lockedProjectId] ?? '' }}" readonly />
                    </flux:field>
                @else
                    <flux:select wire:model.live="project_id" :label="__('tasks.fields.project')" required>
                        <flux:select.option value="">—</flux:select.option>
                        @foreach ($projects as $id => $label)
                            <flux:select.option :value="$id">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                @if ($lockedDocumentId)
                    <flux:field>
                        <flux:label>{{ __('tasks.fields.document') }}</flux:label>
                        <flux:input value="{{ $documents[$lockedDocumentId] ?? '' }}" readonly />
                    </flux:field>
                @else
                    <flux:select
                        wire:model="document_id"
                        :label="__('tasks.fields.document')"
                        :description="__('tasks.hints.document')"
                    >
                        <flux:select.option value="">—</flux:select.option>
                        @foreach ($documents as $id => $number)
                            <flux:select.option :value="$id">{{ $number }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:select wire:model="assigned_to" :label="__('tasks.fields.assigned_to')">
                    <flux:select.option value="">{{ __('tasks.unassigned') }}</flux:select.option>
                    @foreach ($assignees as $id => $name)
                        <flux:select.option :value="$id">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="due_date"
                    type="date"
                    :label="__('tasks.fields.due_date')"
                    :description="__('tasks.hints.due_date')"
                />

                <flux:select wire:model="priority" :label="__('tasks.fields.priority')">
                    @foreach ($priorities as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="status" :label="__('tasks.fields.status')">
                    @foreach ($statuses as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
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
