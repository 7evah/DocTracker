<?php

namespace App\Enums\Contracts;

/**
 * Implemented by every enum rendered through <x-badge>.
 *
 * Requiring an icon alongside the colour is deliberate: §38 forbids
 * communicating state through colour alone, so the badge component can
 * always render a non-colour cue.
 */
interface BadgeEnum
{
    /** Translated, human-facing label. */
    public function label(): string;

    /** Flux colour name (zinc, sky, amber, green, red, …). */
    public function color(): string;

    /** Heroicon name rendered inside the badge. */
    public function icon(): string;
}
