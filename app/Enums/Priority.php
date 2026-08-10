<?php

namespace App\Enums;

use App\Enums\Contracts\BadgeEnum;

/** Shared by reviews and tasks (§27). */
enum Priority: string implements BadgeEnum
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return __("enums.priority.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'zinc',
            self::Medium => 'sky',
            self::High => 'amber',
            self::Critical => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Low => 'chevron-down',
            self::Medium => 'minus',
            self::High => 'chevron-up',
            self::Critical => 'exclamation-triangle',
        };
    }

    /** Default review turnaround in working days, used to pre-fill deadlines. */
    public function defaultTurnaroundDays(): int
    {
        return match ($this) {
            self::Low => 10,
            self::Medium => 5,
            self::High => 3,
            self::Critical => 1,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
