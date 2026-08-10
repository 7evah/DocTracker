<?php

namespace App\Enums;

use App\Enums\Contracts\BadgeEnum;

enum ProjectStatus: string implements BadgeEnum
{
    case Planning = 'planning';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("enums.project_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Planning => 'zinc',
            self::Active => 'green',
            self::OnHold => 'amber',
            self::Completed => 'sky',
            self::Cancelled => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Planning => 'pencil-square',
            self::Active => 'play-circle',
            self::OnHold => 'pause-circle',
            self::Completed => 'check-circle',
            self::Cancelled => 'x-circle',
        };
    }

    /** Projects still consuming engineering effort — counted on the dashboard. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Planning, self::Active, self::OnHold], true);
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->isOpen()),
        );
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
