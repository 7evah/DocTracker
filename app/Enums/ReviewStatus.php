<?php

namespace App\Enums;

use App\Enums\Contracts\BadgeEnum;

/**
 * Outcome of one reviewer's pass over a document revision (§23).
 *
 * Distinct from ApprovalStatus: a review is technical verification, an
 * approval is a signature on a workflow step.
 */
enum ReviewStatus: string implements BadgeEnum
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case RevisionRequested = 'revision_requested';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("enums.review_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::InProgress => 'sky',
            self::Approved => 'green',
            self::RevisionRequested => 'amber',
            self::Rejected => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::InProgress => 'eye',
            self::Approved => 'check-circle',
            self::RevisionRequested => 'arrow-path',
            self::Rejected => 'x-circle',
        };
    }

    /** Still awaiting the reviewer — drives "pending"/"overdue" counters. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::InProgress], true);
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return [self::Pending->value, self::InProgress->value];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
