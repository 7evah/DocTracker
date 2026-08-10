<?php

namespace App\Enums;

use App\Enums\Contracts\BadgeEnum;

enum UserStatus: string implements BadgeEnum
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return __("enums.user_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Inactive => 'zinc',
            self::Suspended => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'check-circle',
            self::Inactive => 'pause-circle',
            self::Suspended => 'no-symbol',
        };
    }

    /** Only active users may authenticate. */
    public function canLogin(): bool
    {
        return $this === self::Active;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
