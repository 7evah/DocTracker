<div>
    <flux:modal.trigger name="assign-reviewers">
        <flux:button icon="user-plus" variant="outline">
            {{ $this->document->latestVersion?->reviews()->exists()
                ? __('reviews.actions.reassign')
                : __('reviews.actions.assign') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="assign-reviewers" class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">{{ __('reviews.assign.heading') }}</flux:heading>
                <flux:subheading class="mt-1">{{ __('reviews.assign.intro') }}</flux:subheading>
            </div>

            @if ($candidates->isEmpty())
                <x-empty-state
                    icon="user-plus"
                    :title="__('reviews.assign.no_candidates')"
                    compact
                />
            @else
                {{--
                    Flux's default checkbox variant renders only its own
                    indicator — it ignores rich slot content — so each
                    candidate's name/role goes through the `label` and
                    `description` props rather than a custom avatar row.
                --}}
                <flux:checkbox.group
                    wire:model="reviewers"
                    :label="__('reviews.assign.reviewers')"
                    :description="__('reviews.assign.reviewers_hint')"
                >
                    @foreach ($candidates as $candidate)
                        <flux:checkbox
                            :value="(string) $candidate->id"
                            :label="$candidate->name"
                            :description="$candidate->department ?: $candidate->primaryRole()"
                            wire:key="cand-{{ $candidate->id }}"
                        />
                    @endforeach
                </flux:checkbox.group>

                <flux:error name="reviewers" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model.live="priority" :label="__('reviews.fields.priority')">
                        @foreach ($priorities as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model="deadline"
                        type="date"
                        :label="__('reviews.fields.deadline')"
                        :description="__('reviews.assign.deadline_hint')"
                    />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">{{ __('common.actions.cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary" icon="paper-airplane">
                        {{ __('reviews.assign.submit') }}
                    </flux:button>
                </div>
            @endif
        </form>
    </flux:modal>
</div>
