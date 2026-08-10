<?php

namespace App\Enums;

/**
 * The six DocFlow roles (§2).
 *
 * Names are stored in the Spatie `roles` table; this enum exists so code and
 * seeders never spell them by hand. Labels are translated for display.
 */
enum UserRole: string
{
    case Administrator = 'administrator';
    case ProjectManager = 'project_manager';
    case Engineer = 'engineer';
    case Reviewer = 'reviewer';
    case Approver = 'approver';
    case Viewer = 'viewer';

    public function label(): string
    {
        return __("enums.role.{$this->value}");
    }

    public function description(): string
    {
        return __("enums.role_description.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Administrator => 'red',
            self::ProjectManager => 'violet',
            self::Engineer => 'sky',
            self::Reviewer => 'amber',
            self::Approver => 'green',
            self::Viewer => 'zinc',
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
