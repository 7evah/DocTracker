<?php

namespace App\Enums;

use App\Enums\Contracts\BadgeEnum;

/**
 * Lifecycle of a logical document (§9).
 *
 * The status lives on the document, not on the revision: a document is
 * "Approved" when its current revision has cleared every approval step.
 */
enum DocumentStatus: string implements BadgeEnum
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case NeedsRevision = 'needs_revision';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return __("enums.document_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::UnderReview => 'sky',
            self::NeedsRevision => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Archived => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil-square',
            self::UnderReview => 'eye',
            self::NeedsRevision => 'arrow-path',
            self::Approved => 'check-circle',
            self::Rejected => 'x-circle',
            self::Archived => 'archive-box',
        };
    }

    /** Statuses that still require action from someone. */
    public static function open(): array
    {
        return [self::Draft, self::UnderReview, self::NeedsRevision];
    }

    /** A document in this status accepts a new revision upload. */
    public function acceptsNewRevision(): bool
    {
        return in_array($this, [self::Draft, self::NeedsRevision, self::Rejected], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Archived], true);
    }

    /** @return array<string, string> value => label, for select inputs. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
