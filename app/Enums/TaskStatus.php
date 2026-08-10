<?php

namespace App\Enums;

use App\Enums\Contracts\BadgeEnum;

enum TaskStatus: string implements BadgeEnum
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("enums.task_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'sky',
            self::InProgress => 'amber',
            self::Completed => 'green',
            self::Cancelled => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Open => 'inbox',
            self::InProgress => 'play-circle',
            self::Completed => 'check-circle',
            self::Cancelled => 'x-circle',
        };
    }

    /** Open tasks can fall overdue; completed and cancelled cannot. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::InProgress], true);
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return [self::Open->value, self::InProgress->value];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
