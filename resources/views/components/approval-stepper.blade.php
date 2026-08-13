@props([
    'approvals' => null,
])

{{--
    The approval circuit as a vertical timeline (§24, §43 ApprovalStepper).

    Each step carries a glyph (✓ / ● / ○ / ✕ / –) alongside its colour and a
    written status, so the sequence is readable without relying on colour
    (§38). Vertical rather than horizontal because step labels are role names
    that would truncate badly on a phone (§42).
--}}

@php $total = $approvals?->count() ?? 0; @endphp

@if ($total === 0)
    <x-empty-state
        icon="check-badge"
        :title="__('approvals.stepper.not_started')"
        :description="__('approvals.stepper.not_started_hint')"
        compact
    />
@else
    <ol {{ $attributes->class('flex flex-col') }}>
        @foreach ($approvals as $approval)
            @php
                $status = $approval->status;
                $isCurrent = $status === App\Enums\ApprovalStatus::InProgress;
            @endphp

            <li wire:key="step-{{ $approval->id }}" class="flex gap-3">
                {{-- Marker column + connecting rail --}}
                <div class="flex flex-col items-center">
                    <span
                        @class([
                            'grid size-8 shrink-0 place-items-center rounded-full border-2 text-sm font-semibold',
                            'border-green-600 bg-green-600 text-white' => $status === App\Enums\ApprovalStatus::Approved,
                            'border-red-600 bg-red-600 text-white' => $status === App\Enums\ApprovalStatus::Rejected,
                            'border-brand-600 bg-brand-600 text-white' => $isCurrent,
                            'border-zinc-300 bg-white text-zinc-400 dark:border-zinc-600 dark:bg-zinc-900' => $status === App\Enums\ApprovalStatus::Pending,
                            'border-zinc-200 bg-zinc-100 text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800' => $status === App\Enums\ApprovalStatus::Skipped,
                        ])
                        aria-hidden="true"
                    >
                        {{ $status->marker() }}
                    </span>

                    @unless ($loop->last)
                        <span
                            @class([
                                'w-0.5 flex-1 min-h-8',
                                'bg-green-600' => $status === App\Enums\ApprovalStatus::Approved,
                                'bg-zinc-200 dark:bg-zinc-700' => $status !== App\Enums\ApprovalStatus::Approved,
                            ])
                            aria-hidden="true"
                        ></span>
                    @endunless
                </div>

                <div @class(['min-w-0 flex-1', 'pb-6' => ! $loop->last, 'pb-1' => $loop->last])>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $approval->role ? __('enums.role.'.$approval->role) : __('approvals.fields.approver') }}
                        </span>

                        <x-badge :status="$status" />

                        @if ($isCurrent)
                            <flux:badge size="sm" color="sky">{{ __('approvals.stepper.current') }}</flux:badge>
                        @endif

                        @if ($approval->isOverdue())
                            <flux:badge size="sm" color="red" icon="exclamation-triangle">
                                {{ __('approvals.overdue') }}
                            </flux:badge>
                        @endif
                    </div>

                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('approvals.stepper.step_of', ['current' => $approval->step, 'total' => $total]) }}
                        @if ($approval->approver)
                            · {{ $approval->approver->name }}
                        @endif
                        @if ($approval->approved_at)
                            · {{ $approval->approved_at->translatedFormat('d M Y') }}
                        @elseif ($approval->rejected_at)
                            · {{ $approval->rejected_at->translatedFormat('d M Y') }}
                        @elseif ($approval->deadline)
                            · {{ $approval->deadline->translatedFormat('d M Y') }}
                        @endif
                    </p>

                    @if (filled($approval->comment))
                        <p class="mt-2 rounded-lg bg-zinc-50 p-2.5 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ $approval->comment }}
                        </p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
