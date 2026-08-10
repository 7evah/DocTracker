<?php

namespace App\Enums;

use App\Enums\Contracts\BadgeEnum;

/** State of a single step in an approval workflow (§8). */
enum ApprovalStatus: string implements BadgeEnum
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __("enums.approval_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::InProgress => 'sky',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Skipped => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::InProgress => 'ellipsis-horizontal-circle',
            self::Approved => 'check-circle',
            self::Rejected => 'x-circle',
            self::Skipped => 'forward',
        };
    }

    /**
     * Marker glyph for the approval stepper (§24), which uses ✓ / ● / ○ so
     * the sequence reads without relying on colour.
     */
    public function marker(): string
    {
        return match ($this) {
            self::Approved => '✓',
            self::Rejected => '✕',
            self::InProgress => '●',
            self::Skipped => '–',
            self::Pending => '○',
        };
    }

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
